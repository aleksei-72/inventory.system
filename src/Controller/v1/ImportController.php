<?php


namespace App\Controller\v1;

use App\Entity\ImportTransaction;
use App\ErrorList;
use App\Service\JwtToken;
use Symfony\Component\HttpFoundation\Request;
use \Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Annotation\isGrantedFor;

class ImportController extends AbstractController
{

    /**
     * @Route("/items/imports", methods={"POST"})
     *
     * @IsGrantedFor(roles = {"user", "admin"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function importFile(Request $request, JwtToken $jwt): JsonResponse {

        $files = $request->files->all();

        foreach ($files as $file) {

            $fileName = str_replace([' '], '', $file->getClientORiginalName());

            //Писк свободного имени файла
            if (file_exists('../storage/'. $fileName)) {
                $i = 0;

                $fileNameWithoutExt = pathinfo($fileName, PATHINFO_FILENAME);
                $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);

                while (true) {
                    $i ++;
                    $newFileName = $fileNameWithoutExt. '_'. $i. '.'. $fileExt;

                    if (!file_exists('../storage/'. $newFileName)) {
                        $fileName = $newFileName;
                        break;
                    }

                }
            }



            $file->move('../storage', $fileName);


            //передать через аргументы имя файла и id юзера, выполняющего импорт

            $command = 'php ../bin/console excel:import "'. $fileName. '" '.
                $jwt->get('user_id');


            if (str_contains(mb_strtolower(php_uname()), 'windows')) {

                exec("start /B ". $command. " &");
            } else {

                exec($command. ' > /dev/null &');
            }

        }


        return $this->json(['files_count' => count($files)]);
    }

    /**
     * @Route("/items/imports", methods={"GET"})
     *
     * @IsGrantedFor(roles = {"reader", "user", "admin"})
     *
     * @return JsonResponse
     */
    public function getImportList(): JsonResponse {
        $imports = $this->getDoctrine()->getRepository(ImportTransaction::class)->findBy([],['id' => 'DESC']);

        $json = array();

        foreach ($imports as $import) {
            array_push($json, $import->toJSON());
        }

        return $this->json($json);
    }
}