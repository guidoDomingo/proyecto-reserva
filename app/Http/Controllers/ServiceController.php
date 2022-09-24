<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Service $service)
    {
        $servicios = $service->all();

        return  response()->json(['error' => false, 'message' => 'todos los servicios', 'status' => 200, 'data' => $servicios]);
    }

    

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data = $request->all();

        date_default_timezone_set('America/Asuncion');

        $service = Service::create($data);

        return  response()->json(['error' => false, 'message' => 'Servicio insertado', 'status' => 200]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Service  $service
     * @return \Illuminate\Http\Response
     */
    public function show(Service $service)
    {
        return  response()->json(['error' => false, 'message' => 'show services', 'status' => 200, 'data' => $service]);
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Service  $service
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Service $service)
    {
        $data = $request->all();

        $upadated = Service::where('id',$service->id)->update($data);

        if($upadated)
        {
            return  response()->json(['error' => false, 'message' => 'servicio actualizado', 'status' => 200]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Service  $service
     * @return \Illuminate\Http\Response
     */
    public function destroy(Service $service)
    {
        $deleted = $service->delete();

        if($deleted)
        {
            return  response()->json(['error' => false, 'message' => 'servicio borrado', 'status' => 200, 'data' => $service]);
        }
    }
}
