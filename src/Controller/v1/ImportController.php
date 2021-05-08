<?php


namespace App\Controller\v1;

use App\Service\JwtToken;
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
}