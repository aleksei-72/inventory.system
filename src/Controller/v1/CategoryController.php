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
     * @Route("/categories", methods={"POST"})
     * @return JsonResponse
     */
    public function createCategory(): JsonResponse {
        $manager = $this->getDoctrine()->getManager();

        $category = new Category();
        $category->setTitle('');
        $manager->persist($category);
        $manager->flush();

        return $this->json($this->$category->toJSON());
    }


    /**
     * @Route("/categories", methods={"GET"})
     * @return JsonResponse
     */
    public function getList(): JsonResponse {
        $categories = $this->getDoctrine()->getRepository(Category::class)->findBy([],['id' => 'ASC']);

        if (count($categories) === 0) {
            return $this->json(['error' => ErrorList::E_INTERNAL_SERVER_ERROR, 'message' => 'categories not found'], 500);
        }

        $json = array();
        foreach ($categories as $category) {
            array_push($json, $category->toJSON());
        }
        return $this->json($json);
    }

    /**
     * @Route("/categories/{id}", requirements={"id"="\d+"}, methods={"PUT"})
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function updateCategory(Request $request,  $id): JsonResponse {
        $inputJson = json_decode($request->getContent(), true);

        if (!$inputJson) {
            return $this->json(['error' => ErrorList::E_REQUEST_BODY_INVALID, 'message' => 'invalid body of request'], 400);
        }

        $manager = $this->getDoctrine()->getManager();
        $category = $this->getDoctrine()->getRepository(Category::class)->find($id);

        if (!$category) {
            return $this->json(['error' => ErrorList::E_NOT_FOUND, 'message' => 'category not found'], 404);
        }

        if (!empty($inputJson['title'])) {
            $category->setTitle($inputJson['title']);
        }

        $manager->flush();

        return $this->json($category->toJSON());
    }




}