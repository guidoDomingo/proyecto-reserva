<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ActualizarStatusReserva extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'actualizamos el status de la reserva que llego ya su hora';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $fecha_reserva = DB::table('reservations')->get();
        $fecha_actual = Carbon::now()->format('Y-m-d H:i:s');
        $datos = [];
        $datos1 = [];

        foreach($fecha_reserva as $key => $value)
        {

            if(strtotime($fecha_actual) >= strtotime($value->date_reservation))
            {
                $updated = DB::table('reservations')->where('date_reservation', $value->date_reservation)->update(['status' => false]);

                array_push($datos,$value->date_reservation);
            
            }else{
                array_push($datos1,$value->date_reservation);
            }
        }

        return $datos;
    }
}
