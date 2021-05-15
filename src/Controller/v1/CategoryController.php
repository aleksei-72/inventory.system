<?php


namespace App\Controller\v1;


use App\Entity\Category;
use App\Entity\Item;
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
        $doctrine = $this->getDoctrine();
        $manager = $doctrine->getManager();
        $categoryRepos = $doctrine->getRepository(Category::class);


        $categoryTitle = 'Новая категория ';
        $i = 1;
        while (true) {
            if (count($categoryRepos->findBy(['title' => $categoryTitle. $i])) === 0) {
                $categoryTitle = $categoryTitle. $i;
                break;
            }
            $i ++;
        }


        $category = new Category();
        $category->setTitle($categoryTitle);
        $manager->persist($category);
        $manager->flush();

        return $this->json(['id' => $category->getId()]);
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

    /**
     * @Route("/categories/{id}", requirements={"id"="\d+"}, methods={"DELETE"})
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function deleteCategory(Request $request, $id): JsonResponse {

        $doctrine = $this->getDoctrine();
        $manager = $doctrine->getManager();

        $newCategoryId = $request->query->get('new_category_id', null);

        $newCategory = null;
        if ($newCategoryId) {
            $newCategory = $this->getDoctrine()->getRepository(Category::class)->find($newCategoryId);
        }


        $currentCategory = $this->getDoctrine()->getRepository(Category::class)->find($id);

        if (!$currentCategory) {
            return $this->json(['error' => ErrorList::E_NOT_FOUND, 'message' => 'category not found'], 404);
        }

        $items = $doctrine->getRepository(Item::class)->findBy(['category' => $currentCategory]);


        foreach ($items as $item) {
            $item->setCategory($newCategory);
        }

        $manager->flush();


        $manager->remove($currentCategory);
        $manager->flush();

        return $this->json([], 200);
    }


}