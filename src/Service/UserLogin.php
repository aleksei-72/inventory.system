<?php

namespace App\Service;

use App\Controller\Api\v1\LoginController;
use App\Entity\User;
use App\Entity\Session;
use Doctrine\ORM\Query\Expr\Select;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Persistence\ManagerRegistry;

class UserLogin
{
    public static $user, $session;

    public static $isChecked = false;

    /**
     * Проверка авторизации клиента + продление токена
     * @static
     * @param Request $request
     * @param ManagerRegistry $doctrine
     * @return bool
     */
    public static function check(Request $request, ManagerRegistry $doctrine): bool {

        if(!self::$isChecked) {
            self::$isChecked = true;
            return self::$user !== null;
        }

        if (!$request->headers->has('Authorization')) {
            return false;
        }

        $token = $request->headers->get('Authorization');

        //Поиск активной сессии
        $session = $doctrine->getRepository(Session::class)->findBy(['token' => $token]);

        if(count($session) !== 1) {
            return false;
        }

        $session = $session[0];

        //Извлечение информации о пользователе
        $user = $doctrine->getRepository(User::class)->findBy(['id' => $session->getUserId()]);

        if(count($user) !== 1) {
            return false;
        }

        $user = $user[0];

        $manager = $doctrine->getManager();

        //Проверка на истечение времени действия токена
        if($session->getTerm() < time()) {
            UserLogin::removeSession($doctrine);
            return false;
        }
        $session->setTerm(time() + LoginController::TOKEN_VALIDITY_PERIOD);
        $manager->flush();

        UserLogin::$session = $session;
        UserLogin::$user = $user;

        return true;
    }

    public static function removeSession(ManagerRegistry $doctrine) {
        if(UserLogin::$session) {

            $manager = $doctrine->getManager();

            $manager->remove(UserLogin::$session);
            $manager->flush();
            UserLogin::$session = false;
            UserLogin::$user = false;
        }
    }
}