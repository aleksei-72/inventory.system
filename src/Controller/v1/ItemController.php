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

        $limit = $request->query->get('limit', 20);
        $skip = $request->query->get('skip', 0);
        $categoryId = $request->query->get('category_id');


        $doctrine = $this->getDoctrine();

        $findCriterial = array();

        if($categoryId) {
            $categoryRepos = $doctrine->getRepository(Category::class);

            $findCategory = $categoryRepos->find($categoryId);
            if(!$findCategory) {
                return $this->json(['status' => 'error', 'description' => 'not found category'], 400);
            }

            $findCriterial['category'] = $findCategory;
        }


        $itemRepos = $doctrine->getRepository(Item::class);

        $itemsArray = $itemRepos->findBy($findCriterial, [], $limit, $skip);

        $json = ['status' => 'ok', 'items' => array()];
        foreach($itemsArray as $item) {
            $arr = array();
            $arr['title'] = $item->getTitle();
            $arr['comment'] = $item->getComment();
            $arr['count'] = $item->getCount();
            $arr['number'] = $item->getNumber();
            $arr['id'] = $item->getId();
            array_push($json['items'], $arr);
        }
        return $this->json($json);
    }
}