<?php


namespace App\Controller\v1;


use App\Entity\Category;
use App\ErrorList;
use Symfony\Component\HttpFoundation\Request;
use \Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;


class CategoryController extends AbstractController
{

    /**
     * @Route("/categories", methods={"GET"})
     * @return JsonResponse
     */
    public function getList(): JsonResponse {
        $categoryRepos = $this->getDoctrine()->getRepository(Category::class);
        $findedCategory = $categoryRepos->findAll();

        if(count($findedCategory) === 0) {
            return $this->json(['error' => ErrorList::E_NOT_FOUND, 'message' => 'caterogies not found'], 404);
        }

        $json = array();
        foreach ($findedCategory as $item) {
            $category = array('id' => $item->getId(), 'title' => $item->getTitle());
            array_push($json, $category);
        }

        return $this->json($json);
    }

    /**
     * @Route("/categories", methods={"POST"})
     * @param Request $request
     * @return JsonResponse
     */
    public function createCategory(Request $request): JsonResponse {
        $inputJson = json_decode($request->getContent(), true);

                if(!$inputJson) {
            return $this->json(['error' => ErrorList::E_BAD_REQUEST, 'message' => 'not found body of request'], 400);
        }
        if(empty($inputJson['title'])) {
            return $this->json(['error' => ErrorList::E_BAD_REQUEST, 'message' => 'not found title of category'], 400);
        }

        $manager = $this->getDoctrine()->getManager();

        $newCategory = new Category();
        $newCategory->setTitle($inputJson['title']);
        $manager->persist($newCategory);

        $manager->flush();
        return $this->json(['id' => $newCategory->getId(), 'title' => $newCategory->getTitle()]);
    }
}