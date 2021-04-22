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
     * @Route("/departments", methods={"GET"})
     * @return JsonResponse
     */
    public function getDepartmentsList(): JsonResponse {
        $departments = $this->getDoctrine()->getRepository(Department::class)->findAll();

        if(count($departments) === 0) {
            return $this->json(['error' => ErrorList::E_INTERNAL_SERVER_ERROR, 'message' => 'departments not found'], 500);
        }

        $json = array();

        foreach ($departments as $department) {
            array_push($json, $department->toJSON());
        }

        return $this->json($json);
    }

}