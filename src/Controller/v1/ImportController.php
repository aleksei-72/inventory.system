<?php


namespace App\Controller\v1;

use App\Entity\User;
use App\Entity\Room;
use App\Entity\Category;
use App\Entity\Profile;
use App\Entity\Item;
use App\Entity\Department;

use App\ErrorList;
use \shuchkin\SimpleXLSX;
use \Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class ImportController extends AbstractController
{

    /**
     * @Route("/file/import", methods={"POST"})
     * @return JsonResponse
     */
    public function importFile(): JsonResponse {

        ini_set('max_execution_time', 2*60); 

        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();

        $reader->setReadDataOnly(true);
        $spreadSheet = $reader->load('../../eXcel/Inventory_2020.xlsx');

        $sheetNames = $spreadSheet->getSheetNames();

        $json = array('items' => array(), 'count' => 0);

        foreach ($sheetNames as $sheet) {

            $activeSheet = $spreadSheet->getSheetByName($sheet);
            $items = $this->parseSheet($activeSheet);

            $json['items'] = array_merge($json['items'], $items['items']);

            $json['count'] += (int)$items['count'];
        }
    

        $this->replicationEntitiesInDB($json['items']);

        return $this->json($json['count']);
    }


    private function parseSheet($sheet): array {

        $content = $sheet->toArray();

        $columnInHeader = ['title' => 'наименован',
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
        
        $doctrine = $this->getDoctrine();
        $manager = $doctrine->getManager();
        

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

                $room = $doctrine->getRepository(Room::class)
                ->findBy(['number' => $item['room']]);

                

                if (count($room) !== 0) {
                    $room = new Room();
                    $room->setNumber($item['room']);
                    $room->setDepartment($doctrine->getRepository(Department::class)->find(1));

                    $manager->persist($room);
                }

                $newItem->addRoom($room);
            }

            if (!empty($item['number'])) {
                $newItem->setNumber($item['number']);
            }

            if (!empty($item['count'])) {
                $newItem->setCount($item['count']);
            }

            if (!empty($item['price'])) {
                $newItem->setPrice($item['price']);
            }

            $manager->persist($newItem);
        }


       $manager->flush();
    }

}