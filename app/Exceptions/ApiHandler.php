<?php

namespace App\Exceptions;

use Exception;
use Dingo\Api\Exception\Handler as DingoHandler;
use \Illuminate\Auth\Access\AuthorizationException;
use \Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use App\Exceptions\Exception\ModelNotFoundException as NotFoundException;

class ApiHandler extends DingoHandler
{
    public function handle(Exception $exception)
    {
        if ($exception instanceof ModelNotFoundException) {
            $model = $exception->getModel();
            $ids = $exception->getIds();
            $exception = new NotFoundException();
            $exception->setModel($model , $ids);
            $exception = new NotFoundHttpException($exception->getMessage(), $exception);
        }
        if ($exception instanceof AuthorizationException) {
            $exception = new AccessDeniedHttpException($exception->getMessage(), $exception);
        }
        return parent::handle($exception);
    }
}
