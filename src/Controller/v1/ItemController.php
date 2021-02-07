<?php


namespace App\Controller\v1;


use App\Entity\Category;
use App\Entity\Item;
use Symfony\Component\HttpFoundation\Request;
use \Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ItemController extends AbstractController
{
    /**
     * @Route("/v1/items", methods={"GET"})
     * @param Request $request
     * @return JsonResponse
     */
    public function getItemList(Request $request) :JsonResponse {

        $limit = $request->query->get('limit', 50);
        $skip = $request->query->get('skip', 0);
        $categoryId = $request->query->get('category_id');

        if(!is_numeric($limit)) {
            return $this->json(['status' => 'error', 'description' => 'incorrect value of limit'], 400);
        }
        if($limit < 0) {
            return $this->json(['status' => 'error', 'description' => 'negative value of limit'], 400);
        }


        if(!is_numeric($skip)) {
            return $this->json(['status' => 'error', 'description' => 'incorrect value of skip'], 400);
        }
        if($skip < 0) {
            return $this->json(['status' => 'error', 'description' => 'negative value of skip'], 400);
        }


        $doctrine = $this->getDoctrine();

        $findCriteria = array();

        if($categoryId) {

            if(!is_numeric($categoryId)) {
                return $this->json(['status' => 'error', 'description' => 'incorrect value of category_id'], 400);
            }



            $categoryRepos = $doctrine->getRepository(Category::class);
            $findCategory = $categoryRepos->find((int)$categoryId);

            if(!$findCategory) {
                return $this->json(['status' => 'error', 'description' => 'not found category'], 400);
            }

            $findCriteria['category'] = $findCategory;
        }


        $itemRepos = $doctrine->getRepository(Item::class);

        $itemsArray = $itemRepos->findBy($findCriteria, [], (int)$limit, (int)$skip);

        $json = ['status' => 'ok', 'items' => array()];

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


            array_push($json['items'], $arr);
        }
        return $this->json($json);
    }
}