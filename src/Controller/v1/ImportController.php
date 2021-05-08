<?php


namespace App\Controller\v1;

use Symfony\Component\HttpFoundation\Request;
use \Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class ImportController extends AbstractController
{

    /**
     * @Route("/file/import", methods={"POST"})
     * @param Request $request
     * @return JsonResponse
     */
    public function importFile(Request $request): JsonResponse {

        ini_set('max_execution_time', 2*60);

        $files = $request->files->all();

        $json = array('items' => array(), 'count' => 0);

        foreach ($files as $file) {

            $file->move('../storage', $file->getFileName());

            exec('php ../bin/console excel:import '. $file->getFileName(). ' > /dev/null &');
        }


        return $this->json($json['count']);
    }
}