<?php


namespace App\Controller\v1;


use App\Entity\Item;
use App\Entity\Room;
use App\ErrorList;
use App\Repository\ItemRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use \Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
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
                $order = 'asc';
            }
        }





        $doctrine = $this->getDoctrine();

        $items = $doctrine->getRepository(Item::class)->getSomeColumnsByCriterias($criterias, $columns, [$orderBy => $sort]);

        return $this->json(['filters' => $criterias, 'columns' => $columns, 'items' => $items]);
    }
}