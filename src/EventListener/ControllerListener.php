<?php


namespace App\EventListener;


use App\Entity\User;
use App\ErrorList;
use App\Service\JwtToken;
use App\UserRoleList;
use Doctrine\Common\Annotations\Reader;
use Firebase\JWT\ExpiredException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

class ControllerListener
{

    private $container;

    private $annotationReader;


    public function __construct(ContainerInterface $container, Reader $annotationReader) {

        $this->container = $container;
        $this->annotationReader = $annotationReader;
    }



    public function onKernelController(ControllerEvent $event) {

        $closure = $event->getController();

        if (!is_array($closure)) {
            return 1;
        }

        list($controller, $method) = $closure;

        //404 отдать без проверки токена
        if ($controller::class === 'App\Controller\ErrorController') {
            return 1;
        }

        $request = $event->getRequest();


        $reflectionMethod = (new \ReflectionObject($controller))->getMethod($method);
        $annotation = $this->annotationReader->getMethodAnnotation($reflectionMethod, 'App\Annotation\IsGrantedFor');

        if (!$annotation) {
            return 0;
        }

        if ($annotation->isAccessAllowed('guest')) {
            //Доступно без авторизации
            return 0;
        }



        if (!$request->headers->has('Authorization')) {

            //Токен не найден
            $event->setController(function () {
                return new JsonResponse(['error' => ErrorList::E_UNAUTHORIZED,
                    'message' => "this request is not access without authorization"], 401);
            });
            return 1;
        }



        $userRole = 'guest';

        try {
            $jwt = $this->getToken($request);
            $this->updateLastActive($jwt->get('user_id'));

            $userRole = $jwt->get('user_role');

        } catch (\Exception $e) {

            //Ошибка токена
            $event->setController(function () use ($e){
                return new JsonResponse(['error' => $e->getMessage(),
                    'message' => $e->getMessage()], 401);
            });
            return 1;
        }


        if (!$annotation->isAccessAllowed($userRole)) {
            //Недостаточно прав
            $event->setController(function (){
                return new JsonResponse(['error' => ErrorList::E_DONT_HAVE_PERMISSION,
                    'message' => 'dont have permissions'], 403);
            });
            return 1;
        }


        return 0;
    }

    private function getToken(Request $request): JwtToken {

        try {
            $jwt = new JwtToken();
            $header = $request->headers->get('Authorization');

            if (!str_starts_with($header, 'Bearer ')) {
                throw new \Exception(ErrorList::E_TOKEN_INVALID);

            }

            $header = substr($header, strlen('Bearer '));
            $jwt->createFromHeader($header);
        }

        catch (ExpiredException $e) {
            throw new \Exception(ErrorList::E_TOKEN_EXPIRED);
        }

        catch (\Exception $e) {
            throw new \Exception(ErrorList::E_TOKEN_INVALID);
        }

        $doctrine = $this->container->get('doctrine');

        $userRepos = $doctrine->getManager()->getRepository(User::class);
        $user = $userRepos->find($jwt->get('user_id'));

        if (!$user) {
            throw new \Exception(ErrorList::E_TOKEN_INVALID);
        }

        if ($user->getIsBlocked()) {
            throw new \Exception(ErrorList::E_USER_BLOCKED);
        }

        if ($user->getRole() !== $jwt->get('user_role')) {
            throw new \Exception(ErrorList::E_TOKEN_INVALID);
        }


        return $jwt;
    }

    public function updateLastActive($userId):void {
        $doctrine = $this->container->get('doctrine');

        $userRepos = $doctrine->getManager()->getRepository(User::class);
        $user = $userRepos->find($userId);

        $user->setLastActiveAt(new \DateTime());
        $doctrine->getManager()->flush();
    }

}
