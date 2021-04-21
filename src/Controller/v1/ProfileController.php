<?php


namespace App\Controller\v1;

use App\Entity\Profile;
use App\ErrorList;
use App\Service\JwtToken;
use Symfony\Component\HttpFoundation\Request;
use \Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class ProfileController extends AbstractController
{
    /**
     * @Route("/profiles", methods={"POST"})
     * @return JsonResponse
     */
    public function createProfile(): JsonResponse {
        $manager = $this->getDoctrine()->getManager();

        $newProfile = new Profile();
        $newProfile->setName('');
        $manager->persist($newProfile);

        $manager->flush();
        return $this->json(['id' => $newProfile->getId()]);
    }

    /**
     * @Route("/profiles", methods={"GET"})
     * @return JsonResponse
     */
    public function getProfileList(): JsonResponse {
        $profiles = $this->getDoctrine()->getRepository(Profile::class)->findAll();

        if(count($profiles) === 0) {
            return $this->json(['error' => ErrorList::E_INTERNAL_SERVER_ERROR, 'message' => 'profiles not found'], 500);
        }

        $json = array();

        foreach ($profiles as $profile) {
            array_push($json, $profile->toJSON());
        }

        return $this->json($json);
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

        $manager = $this->getDoctrine()->getManager();
        $profile = $this->getDoctrine()->getRepository(Profile::class)->find($id);

        if(!$profile) {
            return $this->json(['error' => ErrorList::E_NOT_FOUND, 'message' => 'profile not found'], 404);
        }

        if(!empty($inputJson['name'])) {
            $profile->setName($inputJson['name']);
        }

        $manager->flush();

        return $this->json($this->$profile->toJSON());
    }

}