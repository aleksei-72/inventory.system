<?php

namespace App\Controller\Api\v1;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;


class LoginController extends AbstractController
{
    /**
     * @Route("/v1/login", methods={"GET"})
     * @return JsonResponse
     */
    public function login(Request $request):JsonResponse {
        //var_dump($request->request);
        return $this->json(['status' => 'ok']);
    }
}