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
        $categories = $this->getDoctrine()->getRepository(Category::class)->findAll();

        if(count($categories) === 0) {
            return $this->json(['error' => ErrorList::E_INTERNAL_SERVER_ERROR, 'message' => 'categories not found'], 500);
        }

        $json = array();
        foreach ($categories as $category) {
            array_push($json, array('id' => $category->getId(), 'title' => $category->getTitle()));
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
            return $this->json(['error' => ErrorList::E_REQUEST_BODY_INVALID, 'message' => 'invalid body of request'], 400);
        }

        if(empty($inputJson['title'])) {
            return $this->json(['error' => ErrorList::E_INVALID_DATA, 'message' => 'not found title of category'], 400);
        }

        $manager = $this->getDoctrine()->getManager();

        $newCategory = new Category();
        $newCategory->setTitle($inputJson['title']);
        $manager->persist($newCategory);
        $manager->flush();

        return $this->json(['id' => $newCategory->getId(), 'title' => $newCategory->getTitle()]);
    }
}