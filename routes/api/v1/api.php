<?php

use App\Http\Middleware\Authenticate;
use App\Http\Middleware\authReserva;
use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

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

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::prefix('/user')->group(function(){

    Route::post('/login', 'App\Http\Controllers\LoginController@login');
    Route::post('/register', 'App\Http\Controllers\LoginController@register');
    Route::get('/all','App\Http\Controllers\LoginController@all')->middleware('auth:api');
    Route::get('/activar_admin/{id}','App\Http\Controllers\LoginController@activar_admin')->middleware('auth:api');
});

Route::middleware(['auth:api'])->group(function () {
    Route::prefix('/reservation')->group(function() {
        Route::post('/generate_available_date', 'App\Http\Controllers\AdminController@generate_available_date');
        Route::post('/generate_reservation', 'App\Http\Controllers\AdminController@generate_reservation');
        Route::get('/get_reservation/{user_id}', 'App\Http\Controllers\AdminController@get_reservation');
        Route::post('/get_reserva_user', 'App\Http\Controllers\AdminController@get_reserva_user');
        Route::get('/get_reservas', 'App\Http\Controllers\AdminController@get_reservas');
        Route::post('/cancelar_reserva', 'App\Http\Controllers\AdminController@cancelar_reserva');
        Route::resource('/services','App\Http\Controllers\ServiceController');
    });
});



Route::post('oauth/token', '\Laravel\Passport\Http\Controllers\AccessTokenController@issueToken');

Route::get('test', function(){

    $fecha_reserva = DB::table('reservations')->get();
    $fecha_actual = Carbon::now()->format('Y-m-d H:i:s');
    $datos = [];
    $datos1 = [];


    foreach($fecha_reserva as $key => $value)
    {
        $fecha = Carbon::parse($value->date_reservation)->format('Y-m-d H:i:s');
        if(strtotime($fecha) <= strtotime($fecha_actual))
        {
            $updated = DB::table('reservations')->where('date_reservation', $value->date_reservation)->update(['status' => false]);

            array_push($datos,$value->date_reservation);

        }else{
            array_push($datos1,$value->date_reservation);
        }
    }

    return $datos;
});
