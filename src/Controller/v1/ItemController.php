<?php


namespace App\Controller\v1;


use App\Entity\Category;
use App\Entity\Item;
use App\Entity\Profile;
use App\ErrorList;
use App\Service\JwtToken;
use App\UserRoleList;
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

        if(strtolower($order) !== 'asc') {
            $order = 'desc';
        }

        if(!in_array($orderBy, ['title', 'comment', 'count', 'createdAt', 'updatedAt', 'profile', 'number'], true)) {
            $orderBy = 'id';
        }

        if(!is_numeric($limit) || $limit < 0) {
            $limit = 50;
        }


        if(!is_numeric($skip) || $skip < 0) {
            $skip = 0;
        }


        $doctrine = $this->getDoctrine();

        $findCriteria = array();

        if($categoryId !== 0) {

            if(!is_numeric($categoryId) || $categoryId < 0) {
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
            $arr['created_at'] = $item->getCreatedAt();
            $arr['updated_at'] = $item->getUpdatedAt();

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

    /**
     * @Route("/items", methods={"POST"})
     * @param Request $request
     * @param JwtToken $jwt
     * @return JsonResponse
     */
    public function createItem(Request $request, JwtToken $jwt): JsonResponse {

        if($jwt->get('user_role') == UserRoleList::U_READONLY) {
            return $this->json(['error' => ErrorList::E_DONT_HAVE_PERMISSION, 'message' => 'do not have permission'], 403);
        }


        $inputJson = json_decode($request->getContent(), true);

        if(!$inputJson) {
            return $this->json(['error' => ErrorList::E_REQUEST_BODY_NOT_FOUND, 'message' => 'not found body of request'], 400);
        }

        try {
            $title = $inputJson['title'];
            $categoryId = $inputJson['category_id'];
            $profileId = $inputJson['profile_id'];
        } catch (\Exception $e) {
            return $this->json(['error' => ErrorList::E_INVALID_DATA, 'message' => 'incomplete data '], 400);
        }

        $number = $inputJson['number'] ?? 0;
        $count = $inputJson['count'] ?? 1;
        $comment = $inputJson['comment'] ?? '';


        $category = $this->getDoctrine()->getRepository(Category::class)->find($categoryId);
        if(!$category) {
            return $this->json(['error' => ErrorList::E_INVALID_DATA, 'message' => 'not found category'], 400);
        }

        $profile = $this->getDoctrine()->getRepository(Profile::class)->find($profileId);
        if(!$profile) {
            return $this->json(['error' => ErrorList::E_INVALID_DATA, 'message' => 'not found profile'], 400);
        }

        $manager = $this->getDoctrine()->getManager();

        $item = new Item();

        $item->setTitle($title);
        $item->setComment($comment);
        $item->setCount($count);
        $item->setNumber($number);
        $item->setCategory($category);
        $item->setProfile($profile);

        $item->setCreatedAt(time());
        $item->setUpdatedAt(time());

        $manager->persist($item);
        $manager->flush();

        return $this->json(['id' => $item->getId(), 'number' => $item->getNumber(), 'title' => $item->getTitle(),
            'comment' => $item->getComment(), 'count' => $item->getCount(), 'profile' =>
                ['id' => $profile->getId(), 'name' => $profile->getName()]]);
    }


    /**
     * @Route("/items/{id}", methods={"DELETE"}, requirements={"id"="\d+"})
     * @param JwtToken $jwt
     * @param $id
     * @return JsonResponse
     */
    public function deleteItem(JwtToken $jwt, $id): JsonResponse {

        if($jwt->get('user_role') == UserRoleList::U_READONLY) {
            return $this->json(['error' => ErrorList::E_DONT_HAVE_PERMISSION, 'message' => 'do not have permission'], 403);
        }


        $item = $this->getDoctrine()->getRepository(Item::class)->find($id);

        if(!$item) {
            return $this->json(['error' => ErrorList::E_NOT_FOUND, 'message' => 'not found item'], 404);
        }
        $manager = $this->getDoctrine()->getManager();
        $manager->remove($item);
        $manager->flush();

        return new JsonResponse();
    }
}