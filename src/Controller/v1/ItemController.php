<?php


namespace App\Controller\v1;


use App\Entity\Category;
use App\Entity\Item;
use App\Entity\Room;
use App\Entity\Profile;
use App\ErrorList;
use Symfony\Component\HttpFoundation\Request;
use \Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class ItemController extends AbstractController
{

    /**
     * @Route("/items", methods={"POST"})
     * @return JsonResponse
     */
    public function createItem(): JsonResponse {

        $manager = $this->getDoctrine()->getManager();

        $item = new Item();

        $item->setTitle('');
        $item->setComment('');
        $item->setCount(0);
        $item->setNumber(0);
        $item->setPrice(0);

        $item->setCreatedAt(new \DateTime());
        $item->setUpdatedAt(new \DateTime());

        $manager->persist($item);
        $manager->flush();

        return $this->json(['id' => $item->getId()]);
    }

    /**
     * @Route("/items/{id}", methods={"GET"}, requirements={"id"="\d+"})
     * @param $id
     * @return JsonResponse
     */
    public function getItem($id): JsonResponse {

        $item = $this->getDoctrine()->getRepository(Item::class)->find($id);

        if (!$item) {
            return $this->json(['error' => ErrorList::E_NOT_FOUND, 'message' => 'item not found'], 404);
        }

        return $this->json($item->toJSON());
    }

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
        $query = $request->query->get('query', null);

        if (strtolower($order) !== 'asc') {
            $order = 'desc';
        }

        if (!in_array($orderBy, ['title', 'comment', 'count', 'createdAt', 'updatedAt', 'profile', 'number', 'price', 'category'], true)) {
            $orderBy = 'updatedAt';
        }

        if (!is_numeric($limit) || $limit < 0) {
            $limit = 50;
        }


        if (!is_numeric($skip) || $skip < 0) {
            $skip = 0;
        }


        $doctrine = $this->getDoctrine();

        $findCriteria = array();

        if ($categoryId !== 0) {

            if (!is_numeric($categoryId) || $categoryId < 0) {
                $categoryId = 0;
            }


            $findCategory = $doctrine->getRepository(Category::class)->find((int)$categoryId);

            if ($findCategory) {
                $findCriteria['category'] = $findCategory->getId();
            }
        }


        $itemRepos = $doctrine->getRepository(Item::class);

        if ($query) {
            $items= $itemRepos->findByKeyWord($query, [$orderBy => $order], (int)$limit, (int)$skip);
        } else {

            if ($categoryId !== 0) {
                $items = $itemRepos->findByCategory($categoryId, [$orderBy => $order], (int)$limit, (int)$skip);
            } else {

                $itemsList = $itemRepos->findBy([], [$orderBy => $order], (int)$limit, (int)$skip);
                $totalCount = $itemRepos->count([]);
                $items = ['items' => $itemsList, 'total_count' => $totalCount];
            }
        }


        $json = ['items' => array(), 'total_count' => $items['total_count']];

        foreach ($items['items'] as $item) {
            array_push($json['items'], $item->toJSON());
        }

        return $this->json($json);
    }

    /**
     * @Route("/items/{id}", methods={"PUT"}, requirements={"id"="\d+"})
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function updateItem(Request $request, $id): JsonResponse {
        $inputJson = json_decode($request->getContent(), true);

        if (!$inputJson) {
            return $this->json(['error' => ErrorList::E_REQUEST_BODY_INVALID, 'message' => 'invalid body of request'], 400);
        }

        $manager = $this->getDoctrine()->getManager();
        $item = $this->getDoctrine()->getRepository(Item::class)->find($id);

        if (!$item) {
            return $this->json(['error' => ErrorList::E_NOT_FOUND, 'message' => 'item not found'], 404);
        }

        if (!empty($inputJson['number']) && is_numeric($inputJson['number'])) {
            $item->setNumber($inputJson['number']);
        }

        if (!empty($inputJson['price']) && is_numeric($inputJson['price'])) {
            $item->setPrice($inputJson['price']);
        }

        if (!empty($inputJson['title'])) {
            $item->setTitle($inputJson['title']);
        }

        if (!empty($inputJson['comment'])) {
            $item->setComment($inputJson['comment']);
        }

        if (!empty($inputJson['count']) && preg_match('/^(\d+\s\D+)$/u', $inputJson['count'])) {
            $item->setCount($inputJson['count']);
        }

        if (!empty($inputJson['profile_id']) && is_numeric($inputJson['profile_id'])) {
            $profile = $this->getDoctrine()->getRepository(Profile::class)->find($inputJson['profile_id']);
            if ($profile) {
                $item->setProfile($profile);
            }
        }

        if (!empty($inputJson['category_id']) && is_numeric($inputJson['category_id'])) {
            $category = $this->getDoctrine()->getRepository(Category::class)->find($inputJson['category_id']);
            if ($category) {
                $item->setCategory($category);
            }
        }

        if (!empty($inputJson['room_id']) && is_array($inputJson['room_id'])) {
            $item->removeAllRoom();
            $roomRepos = $this->getDoctrine()->getRepository(Room::class);

            foreach ($inputJson['room_id'] as $newRoomId) {

                if (empty($newRoomId)) {
                    continue;
                }

                $room = $roomRepos->find($newRoomId);

                if ($room) {
                    $item->AddRoom($room);
                }
            }
        }



        if (!empty($inputJson['category_string'])) {
            $categoryList = $this->getDoctrine()->getRepository(Category::class)->findAll();

            foreach ($categoryList as $category) {
                if (str_contains(mb_strtolower($category->getTitle()), mb_strtolower($inputJson['category_string']))) {
                    $item->setCategory($category);
                    break;
                }
            }
        }

        if (!empty($inputJson['profile_string'])) {
            $profileList = $this->getDoctrine()->getRepository(Profile::class)->findAll();

            foreach ($profileList as $profile) {
                if (str_contains(mb_strtolower($profile->getName()), mb_strtolower($inputJson['profile_string']))) {
                    $item->setProfile($profile);
                    break;
                }
            }
        }

        if (!empty($inputJson['room_string'])) {
            $item->removeAllRoom();

            $roomList = $this->getDoctrine()->getRepository(Room::class)->findAll();

            foreach ($inputJson['room_string'] as $newRoomTitle) {

                if (empty($newRoomId)) {
                    continue;
                }

                foreach ($roomList as $room) {
                    if (str_contains(mb_strtolower($room->getNumber()), mb_strtolower($newRoomTitle))) {
                        $item->addRoom($room);
                        break;
                    }
                }
            }
        }


        $item->setUpdatedAt(new \DateTime());

        $manager->flush();

        return $this->json($item->toJSON());
    }

    /**
     * @Route("/items/{id}", methods={"DELETE"}, requirements={"id"="\d+"})
     * @param $id
     * @return JsonResponse
     */
    public function deleteItem($id): JsonResponse {

        $item = $this->getDoctrine()->getRepository(Item::class)->find($id);

        if (!$item) {
            return $this->json(['error' => ErrorList::E_NOT_FOUND, 'message' => 'not found item'], 404);
        }
        $manager = $this->getDoctrine()->getManager();
        $manager->remove($item);
        $manager->flush();

        return $this->json(null, 204);
    }

}