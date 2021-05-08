<?php
namespace App\Command;

use App\Entity\Department;
use App\Entity\ImportTransaction;
use App\Entity\Item;
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

        try {
            $json = $this->parseFile(__DIR__. '/../../storage/'. $fileName);
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
            'profile' => -1,
            'price' => -1,
            'category' => -1];


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


        //Не найдена колонка с наименованиями
        if (!isset($indexForSearchColumn['title'])) {
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


            if (empty($item['title'])) {
                continue;
            }

            $itemCountWithUnit = ($item['countValue'] ?? 1) . ' '. ($item['countUnit'] ?? 'шт');


            $item ['count'] = $itemCountWithUnit;

            array_push($json['items'], $item);
        }


        $json['count'] = count($json['items']);

        return $json;
    }


    private function replicationEntitiesInDB(array $items) {

        $manager = $this->doctrine->getManager();


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
                continue;
            }

            if (!empty($item['room'])) {

                $room = $this->doctrine->getRepository(Room::class)
                    ->findBy(['number' => $item['room']]);

                if (count($room) !== 0) {
                    $room = $room[0];
                } else {
                    //создание новой аудитории
                    $room = new Room();
                    $room->setNumber($item['room']);
                    $room->setDepartment($this->doctrine->getRepository(Department::class)->find(1));

                    $manager->persist($room);
                }


                $newItem->addRoom($room);
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
                $newItem->setPrice($item['price']);
            } else {
                $newItem->setPrice('0');
            }

            $manager->persist($newItem);
        }


        $manager->flush();
    }
}