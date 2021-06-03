<?php


namespace App\Controller\v1;

use App\Entity\User;
use App\ErrorList;
use App\Service\JwtToken;
use Symfony\Component\HttpFoundation\Request;
use \Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Annotation\isGrantedFor;

class LoginController extends AbstractController
{

    /**
     * @Route("/auth", methods={"POST"})
     *
     * @IsGrantedFor(roles = {"guest"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse {
        $inputJson = json_decode($request->getContent(), true);

        if (!$inputJson) {
            return $this->json(['error' => ErrorList::E_REQUEST_BODY_INVALID, 'message' => 'invalid body of request'], 400);
        }
        if (empty($inputJson['username'])) {
            return $this->json(['error' => ErrorList::E_INVALID_DATA, 'message' => 'not found username'], 400);
        }

        if (empty($inputJson['password'])) {
            return $this->json(['error' => ErrorList::E_INVALID_DATA, 'message' => 'not found password'], 400);
        }

        $userName = $inputJson['username'];
        $password = $inputJson['password'];

        $usersList = $this->getDoctrine()->getRepository(User::class)->findBy(['userName' => $userName, 'deletedAt' => null]);

        if (count($usersList) !== 1) {
            return $this->json(['error' => ErrorList::E_USER_NOT_FOUND, 'message' => 'user not found'], 404);
        }

        $user = $usersList[0];

        if ($user->getIsBlocked()) {
            return $this->json(['error' => ErrorList::E_USER_BLOCKED, 'message' => 'this user is blocked'], 403);
        }

        if (!$user->verifyPassword($password)) {
            return $this->json(['error' => ErrorList::E_INVALID_PASSWORD, 'message' => 'password not verify'], 400);
        }


        $jwt = new JwtToken();
        $jwt->set('user_id', $user->getId());
        $jwt->set('user_role', $user->getRole());

        return $this->json($jwt->generate());
    }


    /**
     * @Route("/token", methods={"POST"})
     *
     * @IsGrantedFor(roles = {"reader", "user", "admin"})
     *
     * @param JwtToken $jwt
     * @return JsonResponse
     */
    public function updateToken( JwtToken $jwt): JsonResponse {
        return $this->json($jwt->generate());
    }

    /**
     * @Route("/me")
     *
     * @IsGrantedFor(roles = {"reader", "user", "admin"})
     *
     * @param JwtToken $jwt
     * @return JsonResponse
     */
    public function aboutMe( JwtToken $jwt): JsonResponse {
        $userRepos = $this->getDoctrine()->getRepository(User::class);
        $usersList = $userRepos->findBy(['id' => $jwt->get('user_id')]);

        $user = $usersList[0];

        return $this->json($user->toJSON());
    }

}