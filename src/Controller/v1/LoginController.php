<?php


namespace App\Controller\v1;

use App\Entity\User;
use App\ErrorList;
use App\Service\JwtToken;
use App\UserRoleList;
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
            return $this->json(['error' => ErrorList::E_REQUEST_BODY_NOT_FOUND, 'message' => 'not found body of request'], 400);
        }
        if(empty($inputJson['username'])) {
            return $this->json(['error' => ErrorList::E_INVALID_DATA, 'message' => 'not found username'], 400);
        }

        if(empty($inputJson['password'])) {
            return $this->json(['error' => ErrorList::E_INVALID_DATA, 'message' => 'not found password'], 400);
        }

        $userName = $inputJson['username'];
        $password = $inputJson['password'];

        $userRepos = $this->getDoctrine()->getRepository(User::class);

        $findedUsers = $userRepos->findBy(['userName' => $userName]);

        if(count($findedUsers) !== 1) {
            return $this->json(['error' => ErrorList::E_USER_NOT_FOUND, 'message' => 'user not found'], 404);
        }

        $user = $findedUsers[0];

        if(!password_verify($password, $user->getPassword())) {
            return $this->json(['error' => ErrorList::E_INVALID_PASSWORD, 'message' => 'password not verify'], 400);
        }

        $jwt = new JwtToken();
        $jwt->set('user_id', $user->getId());
        $jwt->set('user_role', $user->getRole());

        return $this->json($jwt->generate());
    }


    /**
     * @Route("/token", methods={"POST"})
     * @param Request $request
     * @param JwtToken $jwt
     * @return JsonResponse
     */
    public function updateToken(Request $request, JwtToken $jwt): JsonResponse {
        return $this->json($jwt->generate());
    }

    /**
     * @Route("/me")
     * @param Request $request
     * @param JwtToken $jwt
     * @return JsonResponse
     */
    public function aboutMe(Request $request, JwtToken $jwt): JsonResponse {

        $userRepos = $this->getDoctrine()->getRepository(User::class);

        $findedUsers = $userRepos->findBy(['id' => $jwt->get('user_id')]);


        $user = $findedUsers[0];

        return $this->json(['name' => $user->getName(), 'username' => $user->getUserName(),
            'email' => $user->getEmail(), 'created_at' => $user->getCreatedAt(), 'role' => $user->getRole()]);
    }

    /**
     * !--@Route("/dev/createusers")
     */
    public function testCreateUsers(): JsonResponse {
        $manager = $this->getDoctrine()->getManager();

        for ($i = 0; $i < 10; $i++) {
            $user = new User();
            $user->setUserName("user_$i");
            $user->setName("Иванов Иван Иванович");
            $user->setPassword(password_hash("P@ssw0rd_$i", PASSWORD_BCRYPT));
            $user->setEmail("user_$i@example.com");
            $user->setRole(UserRoleList::U_USER);
            $user->setCreatedAt(time());




            $reader = new User();
            $reader->setUserName("reader_$i");
            $reader->setName("Иванов Иван Иванович");
            $reader->setPassword(password_hash("P@ssw0rd_$i", PASSWORD_BCRYPT));
            $reader->setEmail("reader_$i@example.com");
            $reader->setRole(UserRoleList::U_READONLY);
            $reader->setCreatedAt(time());

            $manager->persist($user);
            $manager->persist($reader);
        }

        $user = new User();
        $user->setUserName("admin");
        $user->setName("admin");
        $user->setPassword(password_hash("P@ssw0rd", PASSWORD_BCRYPT));
        $user->setEmail("admin@example.com");
        $user->setRole(UserRoleList::U_ADMIN);
        $user->setCreatedAt(time());

        $manager->persist($user);
        $manager->flush();

        return $this->json(['ok' => 'ok']);
    }

}