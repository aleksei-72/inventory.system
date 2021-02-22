<?php


namespace App\EventListener;


use App\ErrorList;
use App\Service\JwtToken;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

class ControllerListener
{

    public function onKernelController(ControllerEvent $event) {

        //404 отдать без проверки токена
        if(get_class($event->getController()[0]) == 'App\Controller\ErrorController') {
            return 1;
        }

        $request = $event->getRequest();

        if($request->headers->has('Authorization')) {

            try {
                $jwt = new JwtToken();
                $header = $request->headers->get('Authorization');


                if(!str_starts_with($header, 'Bearer ')) {
                    $event->setController(function () {
                        return new JsonResponse(['error' => ErrorList::E_TOKEN_INVALID,
                            'message' => 'invalid authorization method'], 401);
                    });
                }

                $header = substr($header, strlen('Bearer '));
                $jwt->createFromHeader($header);

                JwtToken::$initPayLoad = $jwt->getPayload();
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

        } else {

            //Часть маршрутов доступна без авторизации
            foreach ($this->allowedRoutes as $route) {
                if($request->getPathInfo() == $route) {
                    return 0;
                }
            }


            $event->setController(function () {
                return new JsonResponse(['error' => ErrorList::E_UNAUTHORIZED,
                    'message' => 'not found Authorization header'], 401);
            });
            return 1;
        }
        return 0;
    }
}
