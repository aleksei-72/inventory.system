<?php


namespace App\Command;


use App\Entity\Category;
use App\Entity\Department;
use App\Entity\ImportTransaction;
use App\Entity\Item;
use App\Entity\Profile;
use App\Entity\Room;
use App\Entity\User;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ImportFileCommand extends Command
{
    private $doctrine;

    public function __construct(ContainerInterface $container, string $name = null)
    {
        ini_set('max_execution_time', 25);

        $this->doctrine = $container->get('doctrine');
        parent::__construct($name);
    }

    protected function configure()
    {
        $this->setName('excel:import')
            ->setDescription('import items from excel files');

        $this->addArgument('fileName', InputArgument::REQUIRED, 'imported File');
        $this->addArgument('userId', InputArgument::REQUIRED, 'user');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $manager = $this->doctrine->getManager();


        $targetUser = $this->doctrine->getRepository(User::class)->find($input->getArgument('userId'));

        if (!$targetUser) {
            return Command::FAILURE;
        }

        $fileName = $input->getArgument('fileName');

        $import = new ImportTransaction();
        $import->setDateTime(new \DateTime());

        $import->setTargetUser($targetUser);
        $import->setFileName($fileName);



        $start = microtime(true);

        register_shutdown_function(function () use ($start, $import, $manager){

            $import->setCountItems(0);
            $import->setStatus(false);

            $import->setDescription('Maximum execution time is exceeded');

            $end = microtime(true);

            $import->setExecTime(round(($end - $start)* 1000));

            $manager->persist($import);

            $manager->flush();

        });

        try {
            $json = $this->parseFile(__DIR__. '/../../storage/'. $fileName);

            if ($json['count'] === 0) {
                throw new \Exception("not found items in file");
            }
            $this->replicationEntitiesInDB($json['items']);

            $import->setCountItems($json['count']);
            $import->setStatus(true);

            $import->setDescription('');

        } catch (\Exception $e) {
            $import->setCountItems(0);
            $import->setStatus(false);

            $import->setDescription($e->getMessage());
        }

        $end = microtime(true);

        $import->setExecTime(round(($end - $start)* 1000));

        $manager->persist($import);

        $manager->flush();

        return Command::SUCCESS;
    }





    private function parseFile($file): array {

        if (!is_file($file)) {
            throw new \Exception('cannot read file');
        }

        $spreadSheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);

        $sheetNames = $spreadSheet->getSheetNames();

        if (count($sheetNames) === 0) {
            throw new \Exception("Пустой файл");
        }

        $json = array('items' => array(), 'count' => 0);

        foreach ($sheetNames as $sheet) {

            $activeSheet = $spreadSheet->getSheetByName($sheet);

            $items = $this->parseSheet($activeSheet);

            $json['items'] = array_merge($json['items'], $items['items']);
            $json['count'] += (int)$items['count'];
        }

        return $json;
    }

    private function parseSheet($sheet): array {

        $content = $sheet->toArray();

        $columnInHeader = ['title' => 'актив',
            'number' => 'номер',
            'room' => 'мест',
            'countUnit' => 'единиц',
            'countValue' => 'колич',
            'profile' => 'лицо',
            'price' => 'цена',
            'category' => 'категор',
            'comment' => 'коммент',
            'department' => 'корпус'];


        $indexForSearchColumn = array();


        $firstItemRow = 0;
        $lastItemRow = $sheet->getHighestRow();

        //Чтение шапки таблицы
        for ($rowNumber = 0; $rowNumber < min(15, $lastItemRow); $rowNumber ++) {

            foreach ($content[$rowNumber] as $headerCellIndex => $headerCellValue) {

                $cellContent = str_replace([' ', '-', '-', "\t", "\n", '(', ')',
                    ':', '\'', '"'], '', $headerCellValue);


                //Поиск номеров необходимых колонок
                foreach ($columnInHeader as $searchColumnTitle => $searchColumnText) {

                    if (str_contains(mb_strtolower($cellContent), mb_strtolower($searchColumnText))) {
                        $indexForSearchColumn[$searchColumnTitle] = $headerCellIndex;
                        $firstItemRow = $rowNumber + 2;
                    }
                }

            }
        }

        $json = array('items' => array(), 'count' => 0);


        //Не возможно прочитать шапку таблицы (она не найдена)
        if (count($indexForSearchColumn) === 0) {
            return $json;
        }




        //Чтение списка item'ов
        for ($currentRow = $firstItemRow; $currentRow < $lastItemRow; $currentRow++) {

            $item = array();

            //Получение значенй из колонок таблицы
            foreach ($columnInHeader as $columnTitle => $columnIndex) {


                if (isset($indexForSearchColumn[$columnTitle])) {

                    $index = $indexForSearchColumn[$columnTitle];
                    $item[$columnTitle] = (string)($content[$currentRow][$index]) ?? null;
                } else {
                    $item[$columnTitle] = null;
                }
            }



            $itemCountWithUnit = ((int)$item['countValue'] ?? 1) . ' '. ($item['countUnit'] ?? 'шт');


            $item ['count'] = $itemCountWithUnit;

            array_push($json['items'], $item);
        }


        $json['count'] = count($json['items']);

        return $json;
    }


    private function replicationEntitiesInDB(array $items) {

        $manager = $this->doctrine->getManager();

        $createdResources = ['rooms' => array(), 'profiles' => array(), 'departments' => array(), 'categories' => array()];

        foreach ($items as $item) {

            if (!$item) {
                continue;
            }


            $newItem = new Item();

            $newItem->setCreatedAt(new \DateTime());
            $newItem->setUpdatedAt(new \DateTime());

            if (!empty($item['title'])) {
                $newItem->setTitle($item['title']);
            } else {
                $newItem->setTitle('');
            }


            if (!empty($item['department'])) {

                $department = $this->findEntityInDataBaseAndResource(['title' => $item['department']], $createdResources['departments'],
                    Department::class);

                if(!$department) {
                    //создание нового корпуса
                    $department = new Department();
                    $department->setTitle($item['department']);
                    $department->setAddress('');

                    array_push($createdResources['departments'], $department);
                    $manager->persist($department);
                }

            }

            if (!empty($item['room'])) {

                $room = $this->findEntityInDataBaseAndResource(['number' => $item['room']], $createdResources['rooms'],
                    Room::class);

                if(!$room) {
                    //создание новой аудитории
                    $room = new Room();
                    $room->setNumber($item['room']);

                    if (isset($department)) {
                        $room->setDepartment($department);
                    } else {
                        $room->setDepartment($this->doctrine->getRepository(Department::class)->find(1));
                    }


                    array_push($createdResources['rooms'], $room);
                    $manager->persist($room);
                }

                $newItem->addRoom($room);
            }

            if (!empty($item['profile'])) {

                $profile = $this->findEntityInDataBaseAndResource(['name' => $item['profile']], $createdResources['profiles'],
                    Profile::class);

                if(!$profile) {
                    //создание нового профиля
                    $profile = new Profile();
                    $profile->setName($item['profile']);

                    array_push($createdResources['profiles'], $profile);
                    $manager->persist($profile);
                }

                $newItem->setProfile($profile);
            }


            if (!empty($item['category'])) {

                $category = $this->findEntityInDataBaseAndResource(['title' => $item['category']], $createdResources['categories'],
                    Category::class);

                if(!$category) {
                    //создание новой категории
                    $category = new Category();
                    $category->setTitle($item['category']);

                    array_push($createdResources['categories'], $category);
                    $manager->persist($category);
                }

                $newItem->setCategory($category);
            }


            if (!empty($item['number'])) {
                $newItem->setNumber($item['number']);
            } else {
                $newItem->setNumber('0');
            }

            if (!empty($item['count'])) {
                $newItem->setCount($item['count']);
            } else {
                $newItem->setCount('0');
            }

            if (!empty($item['price'])) {
                $newItem->setPrice((float)str_replace(',', '', $item['price']));
            }

            if (!empty($item['comment'])) {
                $newItem->setComment($item['comment']);
            }

            $manager->persist($newItem);
        }


        $manager->flush();
    }


    private function findEntityInDataBaseAndResource(array $criterias, array $resource, $className): mixed {

        //поиск в базе
        $entityInDB = $this->doctrine->getRepository($className)
            ->findBy($criterias);

        if (count($entityInDB) !== 0) {
            return $entityInDB[0];
        }

        //поиск в списке созданных ресурсов
        foreach ($resource as $entity) {

            $entityJSON = $entity->toJSON();

            foreach ($criterias as $key => $value) {
                if ($entityJSON[$key] !== $value) {
                    continue 2;
                }
            }
            return $entity;
        }

        return null;
    }
}