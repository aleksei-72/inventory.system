<?php


namespace App\Controller\v1;

use App\Entity\User;
use App\ErrorList;
use App\Service\JwtToken;
use App\UserRoleList;
use Symfony\Component\HttpFoundation\Request;
use \Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Annotation\isGrantedFor;

class UserController extends AbstractController
{

    /**
     * @Route("/users", methods={"POST"})
     *
     * @IsGrantedFor(roles = {"admin"})
     *
     * @param JwtToken $jwt
     * @return JsonResponse
     */
    public function createUser(JwtToken $jwt): JsonResponse {

        $manager = $this->getDoctrine()->getManager();

        $user= new User();

        $i = 0;

        //поиск свободного username
        while (true) {

            $userName = "user_$i";

            if ($manager->getRepository(User::class)->count(['userName' => $userName]) === 0) {
                break;
            }

            if ($i > 4999) {
                return $this->json(['error' => ErrorList::E_INTERNAL_SERVER_ERROR, 'message' => 'not found free username'], 500);
            }

            $i ++;
        }

        $user->setUserName($userName);
        $user->setName('новый Пользователь ' . $i);
        $user->setPassword('P@ssw0rd');
        $user->setRole(UserRoleList::U_READONLY);
        $user->setCreatedAt(new \DateTime());
        $user->setIsBlocked(true);

        $manager->persist($user);
        $manager->flush();

        return $this->json(['id' => $user->getId()]);
    }


    /**
     * @Route("/users", methods={"GET"})
     *
     * @IsGrantedFor(roles = {"admin"})
     *
     * @return JsonResponse
     */
    public function getUsersList(): JsonResponse {
        $users = $this->getDoctrine()->getRepository(User::class)->findBy([],['id' => 'ASC']);

        $json = array();

        foreach ($users as $user) {
            array_push($json, $user->toJSON());
        }

        return $this->json($json);
    }

    /**
     * @Route("/users/{id}", methods={"PUT"}, requirements={"id"="\d+"})
     *
     * @IsGrantedFor(roles = {"user", "admin"})
     *
     * @param Request $request
     * @param JwtToken $jwt
     * @param $id
     * @return JsonResponse
     */
    public function updateUser(Request $request, JwtToken $jwt, $id): JsonResponse
    {
        $inputJson = json_decode($request->getContent(), true);

        if (!$inputJson) {
            return $this->json(['error' => ErrorList::E_REQUEST_BODY_INVALID, 'message' => 'invalid body of request'], 400);
        }

        $manager = $this->getDoctrine()->getManager();
        $userRepos = $this->getDoctrine()->getRepository(User::class);
        $user = $userRepos->find($id);

        if (!$user) {
            return $this->json(['error' => ErrorList::E_NOT_FOUND, 'message' => 'user not found'], 404);
        }

        $isAdmin = $jwt->get('user_role') === UserRoleList::U_ADMIN;
        $isSelf = $jwt->get('user_id') === (integer)$id;


        if (!$isSelf && !$isAdmin) {
            return $this->json(['error' => ErrorList::E_DONT_HAVE_PERMISSION, 'message' => 'This user is not an administrator. Simple users can update only himself'], 403);
        }


        if ($isAdmin || $isSelf) {

            if (isset($inputJson['name'])) {

                if (mb_strlen($inputJson['name']) < 4) {
                    return $this->json(['error' => ErrorList::E_INVALID_DATA, 'message' => 'name must be longer than 4 characters'], 403);
                }


                $user->setName($inputJson['name']);
            }


            if (isset($inputJson['username']) && $user->getUserName() != $inputJson['username']) {

                if (mb_strlen($inputJson['username']) < 4) {
                    return $this->json(['error' => ErrorList::E_INVALID_DATA, 'message' => 'username must be longer than 4 characters'], 403);
                }

                //Проверка уникальности
                if ($userRepos->count(['userName' => $inputJson['username']]) !== 0) {
                    return $this->json(['error' => ErrorList::E_INVALID_DATA, 'message' => 'username is not unique'], 403);
                }


                $user->setUserName($inputJson['username']);
            }


            if (isset($inputJson['email']) && $user->getEmail() != $inputJson['email']) {

                if (!filter_var($inputJson['email'], FILTER_VALIDATE_EMAIL)) {
                    return $this->json(['error' => ErrorList::E_INVALID_DATA, 'message' => 'email invalidate'], 403);
                }

                //Проверка уникальности
                if ($userRepos->count(['email' => $inputJson['email']]) !== 0) {
                    return $this->json(['error' => ErrorList::E_INVALID_DATA, 'message' => 'email is not unique'], 403);
                }


                $user->setEmail($inputJson['email']);
            }


            if (isset($inputJson['password'])) {

                if (mb_strlen($inputJson['password']) < 6) {
                    return $this->json(['error' => ErrorList::E_INVALID_DATA, 'message' => 'password must be longer than 6 characters'], 403);
                }


                $user->setPassword($inputJson['password']);
            }


        }



        if (isset($inputJson['role'])) {

            if ($isSelf) {
                return $this->json(['error' => ErrorList::E_INVALID_DATA, 'message' => 'you can\'t change the role of yourself'], 403);
            }

            if (!in_array(strtolower($inputJson['role']), [UserRoleList::U_READONLY, UserRoleList::U_USER, UserRoleList::U_ADMIN])) {
                return $this->json(['error' => ErrorList::E_INVALID_DATA, 'message' => 'invalid value of role'], 403);
            }


            $user->setRole(strtolower($inputJson['role']));
        }


        if (isset($inputJson['blocked'])) {

            if (!$isAdmin) {
                return $this->json(['error' => ErrorList::E_DONT_HAVE_PERMISSION, 'message' => 'only the administrator can block or unblock users'], 403);
            }

            if ($isSelf) {
                return $this->json(['error' => ErrorList::E_INVALID_DATA, 'message' => 'you can\'t block or unblock youself'], 403);
            }

            if (!is_bool($inputJson['blocked'])) {
                return $this->json(['error' => ErrorList::E_INVALID_DATA, 'message' => 'invalid value of blocked'], 403);
            }


            $user->setIsBlocked($inputJson['blocked']);
        }


        $manager->flush();

        return $this->json($user->toJSON());
    }


    /**
     * @Route("/users/{id}", methods={"DELETE"}, requirements={"id"="\d+"})
     *
     * @IsGrantedFor(roles = {"admin"})
     *
     * @param $id
     * @param JwtToken $jwt
     * @return JsonResponse
     */
    public function deleteUser($id, JwtToken $jwt): JsonResponse {

        $user = $this->getDoctrine()->getRepository(User::class)->find($id);

        if (!$user) {
            return $this->json(['error' => ErrorList::E_NOT_FOUND, 'message' => 'not found user'], 404);
        }

        $isAdmin = $jwt->get('user_role') === UserRoleList::U_ADMIN;
        $isSelf = $jwt->get('user_id') === (integer)$id;

        if (!$isAdmin) {
            return $this->json(['error' => ErrorList::E_DONT_HAVE_PERMISSION, 'message' => 'this user is not administrator'], 403);
        }

        if ($isSelf) {
            return $this->json(['error' => ErrorList::E_DONT_HAVE_PERMISSION, 'message' => 'cant delete yourself'], 403);
        }

        $manager = $this->getDoctrine()->getManager();
        $user->setDeletedAt(new \DateTime());
        $manager->flush();

        return $this->json(null, 204);
    }
}