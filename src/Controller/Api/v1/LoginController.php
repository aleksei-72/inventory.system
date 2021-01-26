<?php

namespace App\Controller\Api\v1;

use App\Entity\User;
use App\Entity\Session;
use App\Service\UserLogin;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;


class LoginController extends AbstractController
{

    //Срок действия токена (сек)
    const TOKEN_VALIDITY_PERIOD = 3;//60 * 60 * 2;
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


        //Поиск пользователя в базе
        $user = $this->getDoctrine()->getRepository(User::class)->findBy(['name' => $login]);

        if(count($user) !== 1) {
            return $this->json(['status' => 'error',  'description' => 'not found user'], 401);
        }

        $user = $user[0];

        //Проверка пароля
        if(!password_verify($password, $user->getPassword())) {
            return $this->json(['status' => 'error',  'description' => 'incorrect password'], 401);
        }

        $sessionRepos = $this->getDoctrine()->getRepository(Session::class);

        $activeSessions = $sessionRepos->findBy(['userId' => $user->getId()]);

        $manager = $this->getDoctrine()->getManager();
        if(count($activeSessions) !== 0) {
            //Удалить незавершенные сеансы текущего пользователя
            foreach ($activeSessions as $item) {
                $manager->remove($item);
            }
            $manager->flush();
        }

        $token = $this->generateToken();
        //Проверка уникальности токена
        $countIteration = 0;
        while (true) {
            $sessionsWithSameToken = $this->getDoctrine()->getRepository(Session::class)->findBy(['token' => $token]);

            if(count($sessionsWithSameToken) === 0) {
                break;
            }
            $token = $this->generateToken();
            $countIteration ++;

            if($countIteration === 25) {
                return $this->json(['status' => 'error',  'description' => 'too many users. Try later'], 401);
            }
        }

        //Занести сессию в базу
        $session = new Session();
        $session->setToken($token);
        $session->setTerm(time() + self::TOKEN_VALIDITY_PERIOD);
        $session->setUserId($user->getId());

        $manager->persist($session);
        $manager->flush();


        return $this->json(['status' => 'ok', 'token' => $session->getToken()]);
    }

    /**
     * @Route("/v1/logout", methods={"POST"})
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request):JsonResponse {

        if(UserLogin::check($request, $this->getDoctrine())) {
            UserLogin::removeSession($this->getDoctrine());
        }

        return $this->json(['status' => 'ok']);
    }

    /**
     * @Route("/v1/whoami", methods={"GET"})
     * @param Request $request
     * @return JsonResponse
     */
    public function whoAmI(Request $request):JsonResponse {

        if(!UserLogin::check($request, $this->getDoctrine())) {
            return $this->json(['status' => 'error',  'description' => 'not authorize'], 401);
        }

        return $this->json(['status' => 'ok', 'name' => UserLogin::$user->getName(), 'role' => UserLogin::$user->getRole(),
            'email' => UserLogin::$user->getEmail()]);
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
        //return $token;
    }
}