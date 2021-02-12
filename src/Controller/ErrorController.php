<?php


namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Log\DebugLoggerInterface;
use App\ErrorList;

class ErrorController extends \Symfony\Bundle\FrameworkBundle\Controller\AbstractController
{
    public function show(\Throwable $exception, DebugLoggerInterface $logger): JsonResponse {

        $message = $exception->getMessage();

        $code = $exception->getCode();

        $error = ErrorList::E_INTERNAL_SERVER_ERROR;

        if($code == 404 || $code == 0) {
            $error = ErrorList::E_NOT_FOUND;
            $code = 404;
        }

        if($code == 400) {
            $error = ErrorList::E_BAD_REQUEST;
        }

        if($code == 401) {
            $error = ErrorList::E_UNAUTHORIZED;
        }
        return new JsonResponse(['error' => $error, 'message' => $message, 'original_code' => $exception->getCode()], $code);
    }
}