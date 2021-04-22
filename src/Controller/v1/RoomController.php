<?php


namespace App\Controller\v1;

use App\Entity\Room;
use App\ErrorList;
use Symfony\Component\HttpFoundation\Request;
use \Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class RoomController extends AbstractController
{

    /**
     * @Route("/rooms", methods={"GET"})
     * @return JsonResponse
     */
    public function getRoomsList(): JsonResponse {
        $rooms = $this->getDoctrine()->getRepository(Room::class)->findAll();

        if(count($rooms) === 0) {
            return $this->json(['error' => ErrorList::E_INTERNAL_SERVER_ERROR, 'message' => 'rooms not found'], 500);
        }

        $json = array();

        foreach ($rooms as $room) {
            array_push($json, $room->toJSON());
        }

        return $this->json($json);
    }

}