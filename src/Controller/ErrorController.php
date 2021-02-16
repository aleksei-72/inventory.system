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

        if($code === 0) {
            if(str_starts_with($message, 'No route found for')) {
                $error = ErrorList::E_NOT_FOUND;
                $code = 404;
            } else {
                $error = ErrorList::E_INTERNAL_SERVER_ERROR;
                $message = 'internal server error';
                $code = 500;
            }
        }

        return new JsonResponse(['error' => $error, 'message' => $message, 'original_code' => $exception->getCode()], $code);
    }
}