<?php


namespace App\Controller\v1;

use App\Entity\Item;
use App\Entity\Profile;
use App\ErrorList;
use App\Service\JwtToken;
use Symfony\Component\HttpFoundation\Request;
use \Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Annotation\isGrantedFor;

class ProfileController extends AbstractController
{
    /**
     * @Route("/profiles", methods={"POST"})
     *
     * @IsGrantedFor(roles = {"user", "admin"})
     *
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
     *
     * @IsGrantedFor(roles = {"reader", "user", "admin"})
     *
     * @return JsonResponse
     */
    public function getProfileList(): JsonResponse {
        $profiles = $this->getDoctrine()->getRepository(Profile::class)->findBy([],['id' => 'ASC']);

        $json = array();

        foreach ($profiles as $profile) {
            array_push($json, $profile->toJSON());
        }

        return $this->json($json);
    }

    /**
     * @Route("/profiles/{id}", requirements={"id"="\d+"}, methods={"PUT"})
     *
     * @IsGrantedFor(roles = {"user", "admin"})
     *
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function updateProfile(Request $request,  $id): JsonResponse {
        $inputJson = json_decode($request->getContent(), true);

        if (!$inputJson) {
            return $this->json(['error' => ErrorList::E_REQUEST_BODY_INVALID, 'message' => 'invalid body of request'], 400);
        }

        $manager = $this->getDoctrine()->getManager();
        $profile = $this->getDoctrine()->getRepository(Profile::class)->find($id);

        if (!$profile) {
            return $this->json(['error' => ErrorList::E_NOT_FOUND, 'message' => 'profile not found'], 404);
        }

        if (!empty($inputJson['name'])) {
            $profile->setName($inputJson['name']);
        }

        $manager->flush();

        return $this->json($profile->toJSON());
    }

    /**
     * @Route("/profiles/{id}", requirements={"id"="\d+"}, methods={"DELETE"})
     *
     * @IsGrantedFor(roles = {"user", "admin"})
     *
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function deleteProfile(Request $request, $id): JsonResponse {

        $doctrine = $this->getDoctrine();
        $manager = $doctrine->getManager();

        $newProfileId = $request->query->get('new_profile_id', null);

        $newProfile = null;
        if ($newProfileId) {
            $newProfile = $this->getDoctrine()->getRepository(Profile::class)->find($newProfileId);
        }


        $currentProfile = $this->getDoctrine()->getRepository(Profile::class)->find($id);

        if (!$currentProfile) {
            return $this->json(['error' => ErrorList::E_NOT_FOUND, 'message' => 'profile not found'], 404);
        }

        $items = $doctrine->getRepository(Item::class)->findBy(['profile' => $currentProfile]);


        foreach ($items as $item) {
            $item->setProfile($newProfile);
        }

        $manager->flush();


        $manager->remove($currentProfile);
        $manager->flush();

        return $this->json([], 204);
    }

}