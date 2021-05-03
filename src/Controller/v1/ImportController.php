<?php


namespace App\Controller\v1;

use App\Entity\User;
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

        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();

        $reader->setReadDataOnly(true);
        $spreadSheet = $reader->load('Inventory_2020.xlsx');

        $sheetNames = $spreadSheet->getSheetNames();

        $json = array('items' => array(), 'count' => 0);

        foreach ($sheetNames as $sheet) {

            $activeSheet = $spreadSheet->getSheetByName($sheet);
            $items = $this->parseSheet($activeSheet);

            array_push($json['items'], $items['items']);
            $json['count'] += (int)$items['count'];
        }



        return $this->json($json);
    }


    private function parseSheet($sheet): array {

        $content = $sheet->toArray();

        $columnInHeader = ['title' => 2,
        'number' => 3, 
        'room' => 6, 
        'countUnit' => 4, 
        'countValue' => 5,
        'profile' => -1,
        'price' => -1,
        'category' => -1];

        $indexForSearchColumn = array();

            
        $firstItemRow = -1;
        $lastItemRow = $sheet->getHighestRow();


        //Чтение шапки таблицы
        for ($rowNumber = 0; $rowNumber < min(15, $lastItemRow); $rowNumber ++) {

            foreach ($content[$rowNumber] as $headerIndex => $headerValue) {
                //Поиск номеров необходимых колонок
                foreach ($columnInHeader as $searchColumnTitle => $searchColumnIndex) {

                    if ($headerValue == $searchColumnIndex) {
                        $indexForSearchColumn[$searchColumnTitle] = $headerIndex;
                    }
                }
                
            }


            //Найден первый item. Шапка табицы закончилась
            if (isset($indexForSearchColumn['title']) && 
                $content[$rowNumber][$indexForSearchColumn['title']]) {
            
                $firstItemRow = $rowNumber + 1;
                break;
            }     
        }



        $json = array('items' => array(), 'count' => 0);

        //шапка таблицы не найдена
        if ($firstItemRow == -1) {
            return $json;
        }

        //Не найдена колонка с наименованиями
        if (!isset($indexForSearchColumn['title'])) {
            return $json;
        }




        //Чтение списка item'ов
        for ($currentRow = $firstItemRow; $currentRow < $lastItemRow; $currentRow++) { 

            $item = array();

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

}