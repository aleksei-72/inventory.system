<?php


namespace App\EventListener;


use App\Entity\User;
use App\ErrorList;
use App\Service\JwtToken;
use App\UserRoleList;
use Firebase\JWT\ExpiredException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

class ControllerListener
{

    private $routesWithoutAuthorization = [
        array('method'=> 'POST', 'url' => '/auth')
    ];

    //ReadOnly юзер может делать любые GET запросы и запросы из этого списка
    private $routesAllowedForReadOnlyUsers = [
        array('method'=> 'POST', 'url' => '/token')
    ];

    private $container;


    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }


    public function onKernelController(ControllerEvent $event) {

        //404 отдать без проверки токена
        if($event->getController()[0]::class === 'App\Controller\ErrorController') {
            return 1;
        }


        $request = $event->getRequest();

        //Часть маршрутов доступна без авторизации
        foreach ($this->routesWithoutAuthorization as $route) {
            if($request->getPathInfo() == $route['url'] && $request->getMethod() == $route['method']) {
                return 0;
            }
        }

        if(!$request->headers->has('Authorization')) {

            $event->setController(function () {
                return new JsonResponse(['error' => ErrorList::E_UNAUTHORIZED,
                    'message' => 'not found Authorization header'], 401);
            });

            return 1;

        } else {

            $jwt = null;

            try {
                $jwt = new JwtToken();
                $header = $request->headers->get('Authorization');

                if(!str_starts_with($header, 'Bearer ')) {
                    $event->setController(function () {
                        return new JsonResponse(['error' => ErrorList::E_TOKEN_INVALID,
                            'message' => 'invalid token'], 401);
                    });
                    return 1;
                }

                $header = substr($header, strlen('Bearer '));
                $jwt->createFromHeader($header);
            }

            catch (ExpiredException $e) {
                $event->setController(function () {
                    return new JsonResponse(['error' => ErrorList::E_TOKEN_EXPIRED,
                        'message' => 'token expired'], 401);
                });
                return 1;
            }

            catch (\Exception $e) {
                $event->setController(function () {
                    return new JsonResponse(['error' => ErrorList::E_TOKEN_INVALID,
                        'message' => 'invalid token'], 401);
                });
                return 1;
            }

            $userRepos = $this->container->get('doctrine')->getManager()->getRepository(User::class);
            $user = $userRepos->find($jwt->get('user_id'));

            if(!$user) {
                $event->setController(function () {
                    return new JsonResponse(['error' => ErrorList::E_TOKEN_INVALID,
                        'message' => 'incorrect user'], 401);
                });
                return 1;
            }

            if($user->getIsBlocked()) {
                $event->setController(function () {
                    return new JsonResponse(['error' => ErrorList::E_USER_BLOCKED,
                        'message' => 'this user is blocked'], 401);
                });
                return 1;
            }

            if($user->getRole() !== $jwt->get('user_role')) {
                $event->setController(function () {
                    return new JsonResponse(['error' => ErrorList::E_TOKEN_INVALID,
                        'message' => 'invalid token'], 401);
                });
                return 1;
            }


            if($jwt->get('user_role') === UserRoleList::U_READONLY && $request->getMethod() !== 'GET') {

                //Маршруты, разрешенные для ReadOnly юзеров
                foreach ($this->routesAllowedForReadOnlyUsers as $route) {
                    if($request->getPathInfo() == $route['url'] && $request->getMethod() == $route['method']) {
                        return 0;
                    }
                }

                $event->setController(function () {
                    return new JsonResponse(['error' => ErrorList::E_DONT_HAVE_PERMISSION, 'message' => 'this user is readonly'], 403);
                });
            }
        }
        return 0;
    }
}
