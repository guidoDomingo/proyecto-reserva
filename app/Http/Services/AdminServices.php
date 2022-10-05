<?php

namespace App\Http\Services;

use App\Models\Reservation;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class AdminServices {

    public function generate_available_date($request)
    {
        $data = $request->all();

        try{

            $validator = Validator::make($data, [
                'fecha_inicio' => 'required',
                'fecha_fin' => 'required',
                'minutos' => 'required',
                'user_id' => 'required'
            ]);

            if($validator->fails()){
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
                     'estado' => true
            ];

             $date_disponible = [$objeto];

             while(strtotime($fecha_inicio->format('Y-m-d H:i:s')) <= strtotime($fecha_fin->format('Y-m-d H:i:s'))){

                 $fecha_inicio = Carbon::parse($fecha_inicio)->addMinute($minutos);

                 if(strtotime($fecha_inicio->format('H:i:s')) >= strtotime(Carbon::parse($minuto_inicio)) && strtotime($fecha_inicio->format('H:i:s')) <= strtotime(Carbon::parse($minuto_fin))){

                     if(strtotime($fecha_inicio->format('Y-m-d H:i:s')) <= strtotime($fecha_fin->format('Y-m-d H:i:s'))){
                         array_push($date_disponible,['fecha' => $fecha_inicio->format('Y-m-d'), 'hora' => $fecha_inicio->format('H:i:s'), 'estado' => true]);
                     }

                 }
             }


             date_default_timezone_set('America/Asuncion');

             $update = DB::table('available_date')->where('status', true)->update(['status' => false]);

             $insert = DB::table('available_date')->insert([
                'reservation_date' => json_encode($date_disponible),
                'status' => true,
                'user_id' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
             ]);

             return response()->json(['error' => false, 'message' => 'Se genero las fechas y horas correctamente', 'code' => 200]);


        }catch(Exception $e){
            return response()->json(['error' => true, 'message' => $e, 'code' => 208]);
        }
    }

    public function generate_reservation($request)
    {

         try{
            $data = $request->all();

            $validator = Validator::make($data, [
                'hora' => 'required',
                'fecha' => 'required',
                'service_id' => 'required',
                'user_id' => 'required',
            ]);

            if($validator->fails()){
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

            $fecha = Carbon::parse($request->fecha.''.$request->hora)->format('d-m-Y H:i:s');

            $fecha_actual = Carbon::now()->format('d-m-Y H:i:s');


            if((strtotime($fecha) < strtotime($fecha_actual))){
                return response()->json(['error' => true, 'message' => 'La fecha o la hora está desfasado', 'code' => 408],408);
            }

            $reserva = DB::table('reservations')->where('user_id',$data['user_id'])->where('status',true)->first();

            if(!empty($reserva))
            {
                return response()->json(['error' => true, 'message' => 'El usuario ya tiene reserva', 'code' => 408],408);
            }



            $result = DB::table('available_date')->orderBy('created_at','desc')->where('status', 'true')->value('reservation_date');
            $date_disponible = json_decode($result,true);
           // return $date_disponible;
            foreach($date_disponible as $key => $value ){

                if($date_disponible[$key]['fecha'] == $fecha_ && $date_disponible[$key]['hora'] == $hora){
                    $date_disponible[$key]['estado'] = false;
                }
            }


            $affected = DB::table('available_date')->orderBy('created_at','desc')->where("status",true)->update(['reservation_date' => $date_disponible]);

            if($affected){

                $date_reserva = Carbon::parse($hora);

                Reservation::create([
                    'name' => 'Por default',
                    'date_reservation' => $date_reserva,
                    'status' => true,
                    'service_id' => $data['service_id'],
                    'user_id' => $data['user_id'],
                ]);

                return response()->json(['error' => false, 'message' => 'Se genero la reserva correctamente', 'code' => 200]);
            }

         }catch(Exception $e){
            return response()->json(['error' => true, 'message' => $e->getMessage(), 'code' => 207],207);
         }
    }

    public function get_reservation($request)
    {
      try{

        $result = DB::table('available_date')->orderBy('created_at','desc')->where('status', true)->value('reservation_date');

        if($result){

            $date_disponible = json_decode($result,false);
            $datos1 = [];
            $datos2 = [];

            foreach($date_disponible as $key => $value){

                if (!in_array($value->fecha, $datos1)) {

                    array_push($datos1,$value->fecha);
                }
            }


            //return $arreglo1;

            foreach($datos1 as $key => $value){
                $objeto = [
                    "fecha" => '',
                    "horarios" => []
                ];
                foreach($date_disponible as $key1 => $value1){
                    if($value == $value1->fecha){
                        $objeto['fecha'] = $value1->fecha;

                        if($value1->estado){
                            array_push($objeto['horarios'], $value1->hora);
                        }
                    }
                }

                array_push($datos2, $objeto);
            }

            //return $datos2;

            return  response()->json(['error' => false, 'message' => 'Lista de reserva generada', 'code' => 200, 'data' => $datos2]);
        }

        return response()->json(['error' => true, 'message' => 'No hay datos', 'code' => 208]);

      }catch(Exception $e){
        return response()->json(['error' => true, 'message' => $e, 'code' => 208]);
      }
    }


    public function get_reserva_user($request)
    {
        $data = $request->all();

        $validator = Validator::make($data, [
            'user_id' => 'required'
        ]);

        if($validator->fails()){
            return response()->json(['error' => true, 'message' => 'campos requeridos', 'code' => 408],408);
        }

        $user_id = $data['user_id'];

        $reserva = Reservation::where('user_id',$user_id)->where('status',true)->with('servicios')->with('usuarios')->get();

        return  response()->json(['error' => false, 'message' => 'Servicio reservados', 'code' => 200, 'data' => $reserva]);

    }

    public function actualizar_status()
    {

    }
}
