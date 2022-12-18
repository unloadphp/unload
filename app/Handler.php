<?php

namespace App;

use Aws\CloudFormation\Exception\CloudFormationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class Handler extends ExceptionHandler
{
    protected $dontReport = [
        // \Illuminate\Database\Eloquent\ModelNotFoundException::class,
    ];

    public function report(\Throwable $exception)
    {
        parent::report($exception);
    }

//    public function renderForConsole($output, \Throwable $exception)
//    {
//        if ($exception instanceof CloudFormationException) {
//            parent::renderForConsole($output, new \BadMethodCallException($exception->getAwsErrorMessage()));
//            return;
//        }
//
//        parent::renderForConsole($output, $exception);
//    }
}
