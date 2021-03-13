<?php


namespace App\Controller\v1;

use App\Entity\Profile;
use App\ErrorList;
use App\Service\JwtToken;
use App\UserRoleList;
use Symfony\Component\HttpFoundation\Request;
use \Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class ProfileController extends AbstractController
{
    /**
     * @Route("/profiles", methods={"POST"})
     * @param Request $request
     * @param JwtToken $jwt
     * @return JsonResponse
     */
    public function createProfile(Request $request, JwtToken $jwt): JsonResponse {

        if($jwt->get('user_role') == UserRoleList::U_READONLY) {
            return $this->json(['error' => ErrorList::E_DONT_HAVE_PERMISSION, 'message' => 'do not have permission'], 403);
        }

        $inputJson = json_decode($request->getContent(), true);

        if(!$inputJson) {
            return $this->json(['error' => ErrorList::E_REQUEST_BODY_INVALID, 'message' => 'invalid body of request'], 400);
        }
        if(empty($inputJson['name'])) {
            return $this->json(['error' => ErrorList::E_INVALID_DATA, 'message' => 'not found name of profile'], 400);
        }

        $manager = $this->getDoctrine()->getManager();

        $newProfile = new Profile();
        $newProfile->setName($inputJson['name']);
        $manager->persist($newProfile);

        $manager->flush();
        return $this->json(['id' => $newProfile->getId(), 'title' => $newProfile->getName()]);
    }

    /**
     * @Route("/profiles/{id}", requirements={"id"="\d+"}, methods={"PUT"})
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function updateProfile(Request $request,  $id): JsonResponse {
        $inputJson = json_decode($request->getContent(), true);

        if(!$inputJson) {
            return $this->json(['error' => ErrorList::E_REQUEST_BODY_INVALID, 'message' => 'invalid body of request'], 400);
        }
        if(empty($inputJson['name'])) {
            return $this->json(['error' => ErrorList::E_INVALID_DATA, 'message' => 'not found name of profile'], 400);
        }

        $manager = $this->getDoctrine()->getManager();
        $profile = $this->getDoctrine()->getRepository(Profile::class)->find($id);

        if(!$profile) {
            return $this->json(['error' => ErrorList::E_NOT_FOUND, 'message' => 'profile not found'], 404);
        }

        $profile->setName($inputJson['name']);
        $manager->flush();

        return new JsonResponse();
    }
}