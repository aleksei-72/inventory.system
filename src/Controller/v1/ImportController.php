<?php


namespace App\Controller\v1;

use App\Entity\ImportTransaction;
use App\ErrorList;
use App\Service\JwtToken;
use Symfony\Component\HttpFoundation\Request;
use \Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class ImportController extends AbstractController
{

    /**
     * @Route("/imports", methods={"POST"})
     * @param Request $request
     * @return JsonResponse
     */
    public function importFile(Request $request, JwtToken $jwt): JsonResponse {

        ini_set('max_execution_time', 2*60);

        $files = $request->files->all();

        $json = array('items' => array(), 'count' => 0);

        foreach ($files as $file) {

            $file->move('../storage', $file->getFileName());

            //передать имя файла и id юзера, выполняющего импорт
            exec('php ../bin/console excel:import '. $file->getFileName(). ' '.
                $jwt->get('user_id'). ' > /dev/null &');
        }


        return $this->json($json['count']);
    }

    /**
     * @Route("/imports", methods={"GET"})
     * @return JsonResponse
     */
    public function getImportList(): JsonResponse {
        $imports = $this->getDoctrine()->getRepository(ImportTransaction::class)->findBy([],['id' => 'ASC']);

        if (count($imports) === 0) {
            return $this->json(['error' => ErrorList::E_NOT_FOUND, 'message' => 'imports not found'], 404);
        }

        $json = array();

        foreach ($imports as $import) {
            array_push($json, $import->toJSON());
        }

        return $this->json($json);
    }
}