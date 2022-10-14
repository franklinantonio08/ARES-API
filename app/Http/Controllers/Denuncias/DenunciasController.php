<?php

namespace App\Http\Controllers\Denuncias;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Denuncias;


class DenunciasController extends Controller
{
    //


    public function index(){
    
        return Denuncias:: all();
    }

    public function store(Request $request){

        $request->validate([
            'establecimiento' => 'required',
            'provincia' => 'required',
            'distrito' => 'required',
            'corregimiento' => 'required',
            'categoria' => 'required',
            'subcategoria' => 'required',
            'lat' => 'required',
            'lon' => 'required',
            'user' => 'required',
        ]);

        return Denuncias::create($request->all());

    }

    public function show($id){

        return Denuncias::find($id);
    }

    public function update(Request $request, $id){
        
        $denuncias = Denuncias::find($id);
        
        $denuncias->update($request->all());

        return $denuncias;
    }

    public function destroy($id){

        return Denuncias::destroy($id);

    }

    public function search($establecimiento){

        return Denuncias::where('establecimiento', 'like', '%'.$establecimiento.'%')->get();
        //return Denuncias::destroy($id);

    }
}
