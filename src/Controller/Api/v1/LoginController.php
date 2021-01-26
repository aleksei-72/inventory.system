<?php

namespace App\Controller\Api\v1;

use App\Entity\User;
use App\Entity\UserToken;
use MongoDB\Driver\Manager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;


class LoginController extends AbstractController
{
    /**
     * @Route("/v1/login", methods={"POST"})
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request):JsonResponse {
        $json = json_decode($request->getContent(), true);

        if(!$json) {
            return $this->json(['status' => 'error', 'description' => 'not found request body'], 400);
        }

        try{
            $login = $json['login'];
            $password = $json['password'];
        }
        catch (\Exception $e) {
            return $this->json(['status' => 'error',  'description' => 'incomplete data'], 400);
        }


        $user = $this->getDoctrine()->getRepository(User::class)->findBy(['name' => $login]);

        if(count($user) != 1) {
            return $this->json(['status' => 'error',  'description' => 'not found user'], 401);
        }

        $user = $user[0];
        if(!password_verify($password, $user->getPassword())) {
            return $this->json(['status' => 'error',  'description' => 'incorrect password'], 401);
        }

        $tokenRepos = $this->getDoctrine()->getRepository(UserToken::class);

        $findTokens = $tokenRepos->findBy(['userId' => $user->getId()]);

        $manager = $this->getDoctrine()->getManager();
        if(count($findTokens) !== 0) {
            //Удалить незавершенные сеансы текущего пользователя
            foreach ($findTokens as $item) {
                $manager->remove($item);
            }
            $manager->flush();
        }

        $token = new UserToken();
        $token->setToken($this->generateToken());
        $token->setTerm(time());
        $token->setUserId($user->getId());

        $manager->persist($token);
        $manager->flush();


        return $this->json(['status' => 'ok', 'token' => $token->getToken()]);
    }

    /**
     * @Route("/v1/logout", methods={"POST"})
     * @return JsonResponse
     */
    public function logout(Request $request):JsonResponse {
        if ($request->headers->has('Authorization')) {
            $tokenString = $request->headers->get('authorization');

            $manager = $this->getDoctrine()->getManager();
            $tokens = $this->getDoctrine()->getRepository(UserToken::class)->
            findBy(['token' => $tokenString]);


            if(count($tokens) !== 0) {
                foreach ($tokens as $item) {
                    $manager->remove($item);
                }
                $manager->flush();
            }

        }
        return $this->json(['status' => 'ok']);
    }

    private function generateToken():string {
        $sourceString = '0123456789abcdefghijklmnopqrstuvwxyz';
        $token = '';
        for($i = 1; $i < 42; $i++) {
            if($i % 6 === 0) {
                $token .= '-';
                continue;
            }
            $token .= $sourceString[random_int(0,strlen($sourceString ) - 1)];
        }
        return 'jbxpc-km0cz-xfwq2-v3jdj-ou08u-fxxon-2hl21';
        return $token;
    }
}