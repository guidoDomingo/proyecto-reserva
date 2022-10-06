<?php

namespace App\Http\Services;

use App\Models\Reservation;
use Exception;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

class AdminServices
{

    public function generate_available_date($request)
    {
        $data = $request->all();

        try {

            $validator = Validator::make($data, [
                'fecha_inicio' => 'required',
                'fecha_fin' => 'required',
                'minutos' => 'required',
                'user_id' => 'required'
            ]);

            if ($validator->fails()) {
                return [
                    "error" => true,
                    "message" => "error de validación",
                    "code" => 403
                ];
            }

            /*
                generar las fechas y horarios disponibles para mostrar al cliente
            */

            $fecha_inicio = Carbon::parse($data['fecha_inicio']);
            $minuto_inicio = $fecha_inicio->format('H:i:s');
            $fecha_fin = Carbon::parse($data['fecha_fin']);
            $minuto_fin = $fecha_fin->format('H:i:s');
            $minutos = $data['minutos'];
            $user_id = $data['user_id'];

            $objeto = [
                'fecha' => $fecha_inicio->format('Y-m-d'),
                'hora' => $fecha_inicio->format('H:i:s'),
                'estado' => true,
                'user_id' => null
            ];

            $date_disponible = [$objeto];

            while (strtotime($fecha_inicio->format('Y-m-d H:i:s')) <= strtotime($fecha_fin->format('Y-m-d H:i:s'))) {

                $fecha_inicio = Carbon::parse($fecha_inicio)->addMinute($minutos);

                if (strtotime($fecha_inicio->format('H:i:s')) >= strtotime(Carbon::parse($minuto_inicio)) && strtotime($fecha_inicio->format('H:i:s')) <= strtotime(Carbon::parse($minuto_fin))) {

                    if (strtotime($fecha_inicio->format('Y-m-d H:i:s')) <= strtotime($fecha_fin->format('Y-m-d H:i:s'))) {
                        array_push($date_disponible, [
                            'fecha' => $fecha_inicio->format('Y-m-d'),
                            'hora' => $fecha_inicio->format('H:i:s'),
                            'estado' => true,
                            'user_id' => null
                        ]);
                    }
                }
            }


            date_default_timezone_set('America/Asuncion');

            /*
                Ponemos en estado false todos los horarios disponibles para poder crear los nuevos horarios
            */

            $update = DB::table('available_date')->where('status', true)->update(['status' => false]);

            $update = DB::table('reservations')->where('status', true)->update(['status' => false]);

            $insert = DB::table('available_date')->insert([
                'reservation_date' => json_encode($date_disponible),
                'status' => true,
                'user_id' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);

            return response()->json(['error' => false, 'message' => 'Se genero las fechas y horas correctamente', 'code' => 200]);
        } catch (Exception $e) {
            return response()->json(['error' => true, 'message' => $e, 'code' => 208]);
        }
    }

    public function generate_reservation($request)
    {

        try {
            $data = $request->all();

            $validator = Validator::make($data, [
                'hora' => 'required',
                'fecha' => 'required',
                'service_id' => 'required',
                'user_id' => 'required',
            ]);

            if ($validator->fails()) {
                return [
                    "error" => true,
                    "message" => "error de validación",
                    "code" => 403
                ];
            }

            /*
                verificar si el usuario ya tiene reserva
            */

            $hora = Carbon::parse($request->hora)->format('H:i:s');
            $hora_actual = Carbon::now()->format('H:i:s');
            $fecha_ = Carbon::parse($request->fecha)->format('Y-m-d');

            $fecha = Carbon::parse($request->fecha . '' . $request->hora)->format('d-m-Y H:i:s');

            $fecha_actual = Carbon::now()->format('d-m-Y H:i:s');


            if ((strtotime($fecha) < strtotime($fecha_actual))) {
                return response()->json(['error' => true, 'message' => 'La fecha o la hora está desfasado', 'code' => 408], 408);
            }

            $reserva = DB::table('reservations')->where('user_id', $data['user_id'])->where('status', true)->first();

            if (!empty($reserva)) {
                return response()->json(['error' => true, 'message' => 'El usuario ya tiene reserva', 'code' => 408], 408);
            }



            $result = DB::table('available_date')->orderBy('created_at', 'desc')->where('status', 'true')->value('reservation_date');
            $date_disponible = json_decode($result, true);

            $contador = 0;
            foreach ($date_disponible as $key => $value) {

                if ($date_disponible[$key]['fecha'] == $fecha_ && $date_disponible[$key]['hora'] == $hora) {
                    $date_disponible[$key]['estado'] = false;
                    $date_disponible[$key]['user_id'] = $data['user_id'];
                    $contador++;
                }
            }

            if ($contador > 0) {
                $affected = DB::table('available_date')->orderBy('created_at', 'desc')->where("status", true)->update(['reservation_date' => $date_disponible]);

                if ($affected) {

                    $date_reserva = Carbon::parse($fecha_ . ' ' . $hora);

                    Reservation::create([
                        'name' => 'Por default',
                        'date_reservation' => $date_reserva,
                        'status' => true,
                        'service_id' => $data['service_id'],
                        'user_id' => $data['user_id'],
                    ]);

                    return response()->json(['error' => false, 'message' => 'Se genero la reserva correctamente', 'code' => 200]);
                }
            } else {
                return response()->json(['error' => true, 'message' => "No se pudo generar la reserva", 'code' => 207], 207);
            }
        } catch (Exception $e) {
            return response()->json(['error' => true, 'message' => $e->getMessage(), 'code' => 207], 207);
        }
    }

    public function get_reservation($request)
    {
        try {

            $result = DB::table('available_date')->orderBy('created_at', 'desc')->where('status', true)->value('reservation_date');

            if ($result) {

                $date_disponible = json_decode($result, false);
                $datos1 = [];
                $datos2 = [];

                foreach ($date_disponible as $key => $value) {

                    if (!in_array($value->fecha, $datos1)) {

                        array_push($datos1, $value->fecha);
                    }
                }

                $fecha_actual = Carbon::now()->format('d-m-Y H:i:s');


                $tipo = DB::table('users')->where('id', $request->user_id)->first();

                foreach ($datos1 as $key => $value) {
                    $objeto = [
                        "fecha" => '',
                        "horarios" => []
                    ];
                    foreach ($date_disponible as $key1 => $value1) {
                        $fecha = Carbon::parse($value1->fecha . ' ' . $value1->hora)->format('d-m-Y H:i:s');
                        if ($value == $value1->fecha && (strtotime($fecha) >= strtotime($fecha_actual))) {
                            $objeto['fecha'] = $value1->fecha;
                            $array = [
                                'hora' => $value1->hora,
                                'estado' => $value1->estado,
                                'user_id' => $value1->user_id
                            ];

                            //si es admin

                            if ($tipo->tipo_usuario == "1") {
                                if ($value1->estado) {
                                    array_push($objeto['horarios'], $array);
                                } else if ($value1->user_id != null) {
                                    $requestObj = new HttpRequest(array('user_id' => $value1->user_id));
                                    $reserva = $this->get_reserva_user($requestObj);
                                    $data = $reserva->getData();
                                    $nombre = $data->data[0]->usuarios->name;
                                    $servicio = $data->data[0]->servicios->name;
                                    $array['usuario'] = $nombre;
                                    $array['servicio'] = $servicio;

                                    array_push($objeto['horarios'], $array);
                                }
                            } else {

                                // si es usuario normal

                                if ($value1->estado) {
                                    array_push($objeto['horarios'], $array);
                                } else if ($value1->user_id == $request->user_id) {
                                    $requestObj = new HttpRequest(array('user_id' => $request->user_id));
                                    $reserva = $this->get_reserva_user($requestObj);
                                    $data = $reserva->getData();
                                    $nombre = $data->data[0]->usuarios->name;
                                    $servicio = $data->data[0]->servicios->name;
                                    $array['usuario'] = $nombre;
                                    $array['servicio'] = $servicio;
                                    array_push($objeto['horarios'], $array);
                                }
                            }
                        }
                    }

                    if (!empty($objeto['horarios'])) {
                        array_push($datos2, $objeto);
                    }
                }

                //return $datos2;

                return  response()->json(['error' => false, 'message' => 'Lista de reserva generada', 'code' => 200, 'data' => $datos2]);
            }

            return response()->json(['error' => true, 'message' => 'No hay datos', 'code' => 208]);
        } catch (Exception $e) {
            return response()->json(['error' => true, 'message' => $e->getMessage(), 'code' => 208]);
        }
    }


    public function get_reserva_user($request)
    {
        $data = $request->all();

        $validator = Validator::make($data, [
            'user_id' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => true, 'message' => 'campos requeridos', 'code' => 408], 408);
        }

        $user_id = $data['user_id'];
        Log::debug("user_id: " . $user_id);
        $reserva = Reservation::where('user_id', $user_id)->where('status', true)->with('servicios')->with('usuarios')->get();

        return  response()->json(['error' => false, 'message' => 'Servicio reservados', 'code' => 200, 'data' => $reserva]);
    }

    public function get_reservas($request)
    {

        $reserva = Reservation::where('status', true)->with('servicios')->with('usuarios')->get();

        return  response()->json(['error' => false, 'message' => 'Servicios reservados', 'code' => 200, 'data' => $reserva]);
    }

    public function cancelar_reserva($request)
    {
        try {
            $data = $request->all();

            $validator = Validator::make($data, [
                'user_id' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => true, 'message' => 'campos requeridos', 'code' => 408], 408);
            }

            $user_id = $data['user_id'];

            $reserva = Reservation::where('user_id', $user_id)->where('status', true)->first();

            $reserva->status = false;

            $fecha = $reserva->date_reservation;

            $fecha1 = explode(' ', $fecha);

            $reserva->save();

            $result = DB::table('available_date')->orderBy('created_at', 'desc')->where('status', 'true')->value('reservation_date');
            $date_disponible = json_decode($result, true);

            $contador = 0;
            foreach ($date_disponible as $key => $value) {

                if ($date_disponible[$key]['fecha'] == $fecha1[0] && $date_disponible[$key]['hora'] == $fecha1[1]) {
                    $date_disponible[$key]['estado'] = true;
                    $date_disponible[$key]['user_id'] =  null;
                    $contador++;
                }
            }

            if ($contador > 0) {
                $affected = DB::table('available_date')->orderBy('created_at', 'desc')->where("status", true)->update(['reservation_date' => $date_disponible]);

                if ($affected) {
                    return  response()->json(['error' => false, 'message' => 'Reversa cancelada con éxito', 'code' => 200]);
                }
            }
        } catch (Exception $e) {
            return response()->json(['error' => true, 'message' => $e->getMessage(), 'code' => 208]);
        }
    }

    public function actualizar_status()
    {
    }
}
