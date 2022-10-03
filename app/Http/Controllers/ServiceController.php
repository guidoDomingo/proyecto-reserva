<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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

        return  response()->json(['error' => false, 'message' => 'todos los servicios', 'code' => 200, 'data' => $servicios]);
    }



    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $rules = [
            'name' => 'required',
            'price' => 'required',
            'user_id' => 'required',
            'image' => 'required|image'
        ];

        $this->validate($request,$rules);

        $data = $request->all();

        $path = $request->file('image')->store('');
        $data['image'] = '/'.$path;
        $service = Service::create($data);

        return  response()->json(['error' => false, 'message' => 'Servicio insertado', 'code' => 200, 'data' => $service ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Service  $service
     * @return \Illuminate\Http\Response
     */
    public function show(Service $service)
    {
        return  response()->json(['error' => false, 'message' => 'show services', 'code' => 200, 'data' => $service]);
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

        if($request->hasFile('image')){
            Storage::delete($service->image);
            $path = $request->file('image')->store('');
            $data['image'] = $path;
        }

        $upadated = Service::where('id',$service->id)->update($data);

        if($upadated)
        {
            return  response()->json(['error' => false, 'message' => 'servicio actualizado', 'code' => 200, 'data' => $upadated]);
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

        Storage::delete($service->image);

        if($deleted)
        {
            return  response()->json(['error' => false, 'message' => 'servicio borrado', 'code' => 200, 'data' => $service]);
        }
    }
}
