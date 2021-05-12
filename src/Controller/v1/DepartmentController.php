<?php


namespace App\Controller\v1;

use App\Entity\Department;
use App\ErrorList;
use Symfony\Component\HttpFoundation\Request;
use \Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class DepartmentController extends AbstractController
{

    /**
     * @Route("/departments", methods={"POST"})
     * @return JsonResponse
     */
    public function createDepartment(): JsonResponse {
        $doctrine = $this->getDoctrine();
        $manager = $doctrine->getManager();

        $department = new Department();
        $department->setTitle('');
        $department->setAddress('');
        $manager->persist($department);
        $manager->flush();

        return $this->json(['id' => $department->getId()]);
    }

    /**
     * @Route("/departments", methods={"GET"})
     * @return JsonResponse
     */
    public function getDepartmentsList(): JsonResponse {
        $departments = $this->getDoctrine()->getRepository(Department::class)->findBy([],['id' => 'ASC']);

        if (count($departments) === 0) {
            return $this->json(['error' => ErrorList::E_INTERNAL_SERVER_ERROR, 'message' => 'departments not found'], 500);
        }

        $json = array();

        foreach ($departments as $department) {
            array_push($json, $department->toJSON());
        }

        return $this->json($json);
    }


    /**
     * @Route("/departments/{id}", requirements={"id"="\d+"}, methods={"PUT"})
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function updateDepartment(Request $request,  $id): JsonResponse {
        $inputJson = json_decode($request->getContent(), true);

        if (!$inputJson) {
            return $this->json(['error' => ErrorList::E_REQUEST_BODY_INVALID, 'message' => 'invalid body of request'], 400);
        }

        $manager = $this->getDoctrine()->getManager();
        $department = $this->getDoctrine()->getRepository(Department::class)->find($id);

        if (!$department) {
            return $this->json(['error' => ErrorList::E_NOT_FOUND, 'message' => 'department not found'], 404);
        }

        if (!empty($inputJson['title'])) {
            $department->setTitle($inputJson['title']);
        }

        if (!empty($inputJson['address'])) {
            $department->setAddress($inputJson['address']);
        }

        $manager->flush();

        return $this->json($department->toJSON());
    }

}