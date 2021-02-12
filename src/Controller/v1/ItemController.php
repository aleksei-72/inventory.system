<?php


namespace App\Controller\v1;


use App\Entity\Category;
use App\Entity\Item;
use App\ErrorList;
use Symfony\Component\HttpFoundation\Request;
use \Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ItemController extends AbstractController
{
    /**
     * @Route("/items", methods={"GET"})
     * @param Request $request
     * @return JsonResponse
     */
    public function getItemList(Request $request): JsonResponse {

        $limit = $request->query->get('limit', 50);
        $skip = $request->query->get('skip', 0);
        $categoryId = $request->query->get('category_id', 0);
        $orderBy = $request->query->get('sort', 'id');
        $order = $request->query->get('order', 'desc');

        if($order !== 'asc') {
            $order = 'desc';
        }

        if(!in_array($orderBy, ['title', 'comment', 'count', 'createdAt', 'updatedAt', 'profile', 'number'], true)) {
            $orderBy = 'id';
        }

        if(!is_numeric($limit)) {
            return $this->json(['error' => ErrorList::E_INVALID_DATA, 'message' => 'incorrect value of limit'], 400);
        }
        if($limit < 0) {
            return $this->json(['error' => ErrorList::E_INVALID_DATA, 'message' => 'negative value of limit'], 400);
        }


        if(!is_numeric($skip)) {
            return $this->json(['error' => ErrorList::E_INVALID_DATA, 'message' => 'incorrect value of skip'], 400);
        }
        if($skip < 0) {
            return $this->json(['error' => ErrorList::E_INVALID_DATA, 'message' => 'negative value of skip'], 400);
        }


        $doctrine = $this->getDoctrine();

        $findCriteria = array();

        if($categoryId !== 0) {

            if(!is_numeric($categoryId)) {
                $categoryId = 0;
            }



            $categoryRepos = $doctrine->getRepository(Category::class);
            $findCategory = $categoryRepos->find((int)$categoryId);

            if($findCategory) {
                $findCriteria['category'] = $findCategory;
            }


        }


        $itemRepos = $doctrine->getRepository(Item::class);

        $itemsArray = $itemRepos->findBy($findCriteria, [$orderBy => $order], (int)$limit, (int)$skip);

        if(count($itemsArray) === 0) {
            return $this->json(['error' => ErrorList::E_NOT_FOUND, 'message' => 'not found'], 404);
        }

        $json = ['items' => array()];

        foreach($itemsArray as $item) {
            $arr = array();
            $arr['title'] = $item->getTitle();
            $arr['comment'] = $item->getComment();
            $arr['count'] = $item->getCount();
            $arr['number'] = $item->getNumber();
            $arr['id'] = $item->getId();
            $arr['createdAt'] = $item->getCreatedAt();
            $arr['updatedAt'] = $item->getUpdatedAt();

            $arr['category']['id'] = $item->getCategory()->getId();
            $arr['category']['title'] = $item->getCategory()->getTitle();

            $itemProfile = $item->getProfile();

            if($itemProfile) {
                $arr['profile']['id'] = $itemProfile->getId();
                $arr['profile']['name'] = $itemProfile->getName();
            } else {
                $arr['profile'] = null;
            }

            $itemRooms = $item->getRoom();

            if(count($itemRooms) !== 0) {

                $arr['rooms'] = array();
                foreach ($itemRooms as $room) {
                    $roomInfo = array();

                    $roomInfo['id'] = $room->getId();
                    $roomInfo['number'] = $room->getNumber();

                    $itemDepartment = $room->getDepartment();

                    $roomInfo['department']['id'] = $itemDepartment->getId();
                    $roomInfo['department']['title'] = $itemDepartment->getTitle();
                    $roomInfo['department']['address'] = $itemDepartment->getAddress();

                    array_push($arr['rooms'], $roomInfo);
                }

            } else {
                $arr['rooms'] = null;
            }


            array_push($json['items'], $arr);
        }
        return $this->json($json);
    }
}