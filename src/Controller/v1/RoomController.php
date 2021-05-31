<?php


namespace App\Controller\v1;

use App\Entity\Department;
use App\Entity\Room;
use App\ErrorList;
use Symfony\Component\HttpFoundation\Request;
use \Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Annotation\isGrantedFor;

class RoomController extends AbstractController
{

    /**
     * @Route("/rooms", methods={"POST"})
     *
     * @IsGrantedFor(roles = {"user", "admin"})
     *
     * @return JsonResponse
     */
    public function createRoom(): JsonResponse {
        $doctrine = $this->getDoctrine();
        $manager = $doctrine->getManager();

        $room = new Room();
        $room->setNumber('');
        $room->setDepartment($doctrine->getRepository(Department::class)->find(1));
        $manager->persist($room);
        $manager->flush();

        return $this->json(['id' => $room->getId()]);
    }


    /**
     * @Route("/rooms", methods={"GET"})
     *
     * @IsGrantedFor(roles = {"reader", "user", "admin"})
     *
     * @return JsonResponse
     */
    public function getRoomsList(): JsonResponse {
        $rooms = $this->getDoctrine()->getRepository(Room::class)->findBy([],['id' => 'ASC']);

        $json = array();

        foreach ($rooms as $room) {
            array_push($json, $room->toJSON());
        }

        return $this->json($json);
    }


    /**
     * @Route("/rooms/{id}", requirements={"id"="\d+"}, methods={"PUT"})
     *
     * @IsGrantedFor(roles = {"user", "admin"})
     *
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function updateRoom(Request $request,  $id): JsonResponse {
        $inputJson = json_decode($request->getContent(), true);

        if (!$inputJson) {
            return $this->json(['error' => ErrorList::E_REQUEST_BODY_INVALID, 'message' => 'invalid body of request'], 400);
        }

        $manager = $this->getDoctrine()->getManager();
        $room = $this->getDoctrine()->getRepository(Room::class)->find($id);

        if (!$room) {
            return $this->json(['error' => ErrorList::E_NOT_FOUND, 'message' => 'room not found'], 404);
        }

        if (!empty($inputJson['number'])) {
            $room->setNumber($inputJson['number']);
        }

        if (!empty($inputJson['department_id'])) {
            $department = $this->getDoctrine()->getRepository(Department::class)->find($inputJson['department_id']);
            if ($department) {
                $room->setDepartment($department);
            }
        }

        $manager->flush();

        return $this->json($room->toJSON());
    }


    /**
     * @Route("/rooms/{id}", requirements={"id"="\d+"}, methods={"DELETE"})
     *
     * @IsGrantedFor(roles = {"user", "admin"})
     *
     * @param $id
     * @return JsonResponse
     */
    public function deleteRoom($id):JsonResponse {
        $doctrine = $this->getDoctrine();
        $manager = $doctrine->getManager();

        $room = $this->getDoctrine()->getRepository(Room::class)->find($id);

        if (!$room) {
            return $this->json(['error' => ErrorList::E_NOT_FOUND, 'message' => 'room not found'], 404);
        }

        $manager->remove($room);
        $manager->flush();

        return $this->json([], 204);
    }
}