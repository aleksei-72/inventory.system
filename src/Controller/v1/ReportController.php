<?php


namespace App\Controller\v1;


use App\Entity\Item;
use App\ErrorList;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use App\Repository\ItemRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use \Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;

class ReportController extends AbstractController
{

    /**
     * @Route("/items/report", methods={"POST"})
     * @param Request $request
     * @return Response
     */
    public function createReport(Request $request):Response {
        $inputJson = json_decode($request->getContent(), true);

        if (!$inputJson) {
            return $this->json(['error' => ErrorList::E_REQUEST_BODY_INVALID, 'message' => 'invalid body of request'], 400);
        }

        if (empty($inputJson['filters']) || empty($inputJson['columns'])) {
            return $this->json(['error' => ErrorList::E_REQUEST_BODY_INVALID, 'message' => 'not found \'filters\' or \'columns\''], 400);
        }


        $filterNames = array_keys(ItemRepository::$filterNames);
        $columnNames = array_keys(ItemRepository::$columnNames);


        $criterias = array();
        $columns = array();

        //выделить условия отбора item'ов
        foreach ($inputJson['filters'] as $conditionName => $condition) {
            if (in_array($conditionName, $filterNames, true)) {

                if (is_array($condition)) {

                    try {
                        $operator = $condition['operator'];
                        if(!in_array($operator, array_keys(ItemRepository::$operators))) {
                            continue;
                        }
                        $value = $condition['value'];
                        $criterias[$conditionName] = ['operator' => $operator, 'value' => $value];
                    } catch (\Exception $e) {
                        continue;
                    }

                } else {
                    $criterias[$conditionName] = ['operator' => 'eq', 'value' => $condition];
                }
            }
        }

        //колонки для отчета
        foreach ($inputJson['columns'] as $column) {
            if (in_array($column, $columnNames, true)) {
                array_push($columns, $column);
            }
        }

        //default value
        $orderBy = 'id';
        $sort = 'desc';

        if (!empty($inputJson['sort'])) {
            if (in_array($inputJson['sort'], ['title', 'comment', 'count', 'createdAt', 'updatedAt', 'profile', 'number', 'price', 'category'], true)) {
                $orderBy = $inputJson['sort'];
            }
        }

        if (!empty($inputJson['order'])) {
            if (strtolower($inputJson['order']) == 'asc') {
                $sort = 'asc';
            }
        }





        $doctrine = $this->getDoctrine();

        $data = $doctrine->getRepository(Item::class)->getSomeColumnsByCriterias($criterias, $columns, [$orderBy => $sort]);

        if (!$data) {
            return $this->json(['error' => ErrorList::E_NOT_FOUND, 'message' => 'not found entity by this filters'], 204);
        }

        $response =  new StreamedResponse(
            function () use ($data) {
                $this->generateReportFile($data, 'php://output');
            }
        );


        $response->headers->set('Content-Type', 'application/vnd.ms-excel');
        $response->headers->set('Content-Disposition', 'attachment;filename="file.xlsx"');
        return $response;
    }


    private function generateReportFile($data, $fileName) {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'Отчет сформирован ' .
            (new \DateTime())->format('Y-m-d H:i:s'));





        $cellColumnForHeader = 2;

        $columnNames = ['room' => 'Помещение (место)', 'profile' => 'Ответственное лицо',
            'category' => 'Категория', 'department' => 'Корпус', 'count' => 'Количество', 'number' => 'Инвентаризационный номер',
            'title' => 'Наименование объекта нефинансового актива', 'price' => 'Цена', 'comment' => 'Комментарий',
            'created_at' => 'Дата создания', 'updated_at' => 'Дата редактирования'];


        //создание шапки таблицы
        $sheet->getCellByColumnAndRow(1, 3)->setValue('№ Записи');
        $sheet->getCellByColumnAndRow(1, 4)->setValue('1');

        foreach ($data[0] as $field => $value) {
            $sheet->getCellByColumnAndRow($cellColumnForHeader, 3)->setValue($columnNames[$field]);
            $sheet->getCellByColumnAndRow($cellColumnForHeader, 4)->setValue($cellColumnForHeader);
            $cellColumnForHeader++;
        }


        $id = 1;
        $cellRow = 6;

        //заполнение данными
        foreach ($data as $entity) {
            $sheet->getCellByColumnAndRow(1, $cellRow)->setValue($id);
            $id ++;

            $cellColumn = 2;
            foreach ($entity as $property => $value) {
                $cell = $sheet->getCellByColumnAndRow($cellColumn, $cellRow);
                $cell->setValue($value);

                /*if ($property === 'price') {
                    //$cell->
                }*/
                $cellColumn ++;
            }
            $cellRow ++;
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($fileName);
        $writer->save(__DIR__ . '/../../../storage/reports/file.xlsx');
    }
}