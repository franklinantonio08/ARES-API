<?php


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Denuncias\DenunciasController;
use App\Http\Controllers\VehiculoController;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


// Public Routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/denuncias', [DenunciasController::class, 'index']);

Route::get('/denuncias/search/{establecimiento}', [DenunciasController::class, 'search']);

 


// Protected Routes
Route::group(['middleware'=>['auth:sanctum']], function () {   

    Route::post('/denuncias/id', [DenunciasController::class, 'show']);
    

    Route::post('/denuncias', [DenunciasController::class, 'store']);    
    Route::put('/denuncias/{id}', [DenunciasController::class, 'update']);
    Route::delete('/denuncias/{id}', [DenunciasController::class, 'destroy']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::post('/denuncias/consultaCodigo', [DenunciasController::class, 'consultaCodigo']);    

    Route::post('/denuncias/migrateData', [DenunciasController::class, 'migrateData']);    

    Route::post('/denuncias/migrateDataFamilia', [DenunciasController::class, 'migrateDataFamilia']);    

   Route::post('/vehiculos', [VehiculoController::class, 'ListaVehiculos']);

});


Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
