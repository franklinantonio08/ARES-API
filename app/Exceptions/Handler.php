<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        //
    }


    /*Quitar en caso de que quieras mostrar errores*/
    // public function render($request, Throwable $exception){

    //     if ($request->is('api/*')) {
    //         return response()->json([
    //             'status' => 'restricted',
    //             'message' => 'Acceso restringido. Servicio protegido.'
    //         ], 403);
    //     }

    //     return parent::render($request, $exception);
    // }
}
