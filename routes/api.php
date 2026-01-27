<?php


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Denuncias\DenunciasController;
use App\Http\Controllers\VehiculoController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OperativoController;
use App\Http\Controllers\InteropController;



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
    Route::post('/BuscarVehiculos', [VehiculoController::class, 'BuscarVehiculos']);
    Route::post('/accesorios', [VehiculoController::class, 'ListaAccesorios']);
    Route::post('/inspeccion', [VehiculoController::class, 'GuardaInspeccion']);

    

    Route::post('/interop/BuscarRuex', [InteropController::class, 'BuscarRegistro']);
    Route::get('/interop/BuscarPais', [InteropController::class, 'BuscarPaises']);
    Route::get('/interop/BuscarOcupacion', [InteropController::class, 'BuscarOcupaciones']);


    Route::post('/operativo/BuscarRuex', [OperativoController::class, 'BuscarRuex']);
    Route::post('/operativo/BuscarPasaporte', [OperativoController::class, 'BuscarPasaporte']);
    Route::post('/operativo/BuscarCedula', [OperativoController::class, 'BuscarCedula']);
    Route::post('/operativo/BuscarPais', [OperativoController::class, 'BuscarPais']);
    Route::post('/operativo/BuscarOperativo', [OperativoController::class, 'BuscarOperativo']);
    Route::post('/operativo/BuscarAcciones', [OperativoController::class, 'BuscarAcciones']);
    Route::post('/operativo/BuscarMotivo', [OperativoController::class, 'BuscarMotivo']);
    Route::post('/operativo/BuscarProvincia', [OperativoController::class, 'BuscarProvincia']);
    Route::post('/operativo/BuscarDistrito', [OperativoController::class, 'BuscarDistrito']);
    Route::post('/operativo/BuscarCorregimiento', [OperativoController::class, 'BuscarCorregimiento']);
    Route::post('/operativo/GuardaOperacion', [OperativoController::class, 'GuardaOperacion']);  
    Route::put('/operativo/ActualizaOperacion/{id}', [OperativoController::class, 'ActualizaOperacion']);  

    Route::post('/operativo/BuscarAccionesInc', [OperativoController::class, 'BuscarAccionesInc']);
    Route::post('/operativo/BuscarMotivoInc', [OperativoController::class, 'BuscarMotivoInc']);

    Route::post('/operativo/Estadistica', [OperativoController::class, 'Estadistica']);  

});


Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
