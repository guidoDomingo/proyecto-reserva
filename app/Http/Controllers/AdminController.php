<?php

namespace App\Http\Controllers;

use App\Http\Services\AdminServices;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AdminController extends Controller
{

    public function __construct()
    {
        $this->service = new AdminServices();
    }

    public function generate_available_date(Request $request)
    {
       return $this->service->generate_available_date($request);
    }

    public function generate_reservation(Request $request)
    {
       return $this->service->generate_reservation($request);
    }

    public function get_reservation(Request $request)
    {
       return $this->service->get_reservation($request);
    }

    public function get_reserva_user(Request $request)
    {
       return $this->service->get_reserva_user($request);
    }
}
