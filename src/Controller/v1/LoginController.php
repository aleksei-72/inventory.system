<?php


namespace App\Controller\v1;

use App\Entity\User;
use App\ErrorList;
use App\Service\JwtToken;
use Firebase\JWT\ExpiredException;
use Symfony\Component\HttpFoundation\Request;
use \Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use \Firebase\JWT\JWT;

class LoginController extends AbstractController
{

    /**
     * @Route("/auth", methods={"POST"})
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse {
        $inputJson = json_decode($request->getContent(), true);

        if(!$inputJson) {
            return $this->json(['error' => ErrorList::E_BAD_REQUEST, 'message' => 'not found body of request'], 400);
        }
        if(empty($inputJson['username'])) {
            return $this->json(['error' => ErrorList::E_BAD_REQUEST, 'message' => 'not found username'], 400);
        }

        if(empty($inputJson['password'])) {
            return $this->json(['error' => ErrorList::E_BAD_REQUEST, 'message' => 'not found password'], 400);
        }

        $userName = $inputJson['username'];
        $password = $inputJson['password'];

        $userRepos = $this->getDoctrine()->getRepository(User::class);

        $findedUsers = $userRepos->findBy(['userName' => $userName]);

        if(count($findedUsers) !== 1) {
            return $this->json(['error' => ErrorList::E_NOT_FOUND, 'message' => 'user not found'], 404);
        }

        $user = $findedUsers[0];

        if(!password_verify($password, $user->getPassword())) {
            return $this->json(['error' => ErrorList::E_INVALID_DATA, 'message' => 'password not verify'], 400);
        }

        $jwt = new JwtToken();
        $jwt->set('userId', $user->getId());
        $jwt->set('userRole', $user->getRole());

        return $this->json(['token' => $jwt->generate()]);
    }


    /**
     * @Route("/token", methods={"POST"})
     * @param Request $request
     * @param JwtToken $jwt
     * @return JsonResponse
     */
    public function updateToken(Request $request, JwtToken $jwt): JsonResponse {
        return $this->json(['token' => $jwt->generate()]);
    }

    /**
     * @Route("/me")
     * @param Request $request
     * @param JwtToken $jwt
     * @return JsonResponse
     */
    public function aboutMe(Request $request, JwtToken $jwt): JsonResponse {

        $userRepos = $this->getDoctrine()->getRepository(User::class);

        $findedUsers = $userRepos->findBy(['id' => $jwt->get('userId')]);

        if(count($findedUsers) !== 1) {
            return $this->json(['error' => ErrorList::E_NOT_FOUND, 'message' => 'user not found'], 404);
        }

        $user = $findedUsers[0];

        return $this->json(['name' => $user->getName(), 'userName' => $user->getUserName(),
            'email' => $user->getEmail(), 'createdAt' => $user->getCreatedAt(), 'role' => $user->getRole()]);
    }

}