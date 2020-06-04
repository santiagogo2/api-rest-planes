<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Plans;

class PlansController extends Controller
{    
    //FUNCIÓN PARA MOSTRAR TODOS LOS PLANES CREADOS
    public function index (Request $request){
        // Recoger el token del header
        $jwtAuth = new \JwtAuth();
        $token = $request->header('Authorization');
        $user = $jwtAuth->checkToken($token, true);
        
        if($user->role == 'ROLE_ADMIN' || $user->role == 'ROLE_SUPER_ADMIN'){
            $plans = Plans::with('ImprovementOpportunities')
                          ->get();
            if(is_object($plans)){
                $data = array(
                    'status' => 'success',
                    'code' => 200,
                    'plans' => $plans
                );
            } else{
                $data = array(
                    'status' => 'error',
                    'code' => 404,
                    'message' => 'No se han encontrado registros en la base de datos.'
                );
            }
        } else{
            $data = array(
                'status' => 'error',
                'code' => 401,
                'message' => 'Usted no tiene permisos para realizar esta acción.'
            );            
        }
            
        //Devolver respuesta        
        return response()->json($data, $data['code']);
    }
    
    public function show($id, Request $request){
        // Recoger el token del header
        $jwtAuth = new \JwtAuth();
        $token = $request->header('Authorization');
        $user = $jwtAuth->checktoken($token, true);
        
        // Comprobar si el usuario tiene los permisos
        if($user->role == 'ROLE_ADMIN' || $user->role == 'ROLE_SUPER_ADMIN'){
            // Buscar el plan por id
            $plan = Plans::find($id);
            
            if(is_object($plan)){
                $data = array(
                    'status' => 'success',
                    'code' => 200,
                    'plan' => $plan
                );
            } else{                
                $data = array(
                    'status' => 'error',
                    'code' => 404,
                    'message' => 'El Plan de Mejora '.$id.' no existe.'
                );
            }
        } else{
            $data = array(
                'status' => 'error',
                'code' => 401,
                'message' => 'Usted no tiene permisos para realizar esta acción.'
            );            
        }
        // Devolver respuesta
        return response()->json($data, $data['code']);
    }
    
    //FUNCIÓN PARA ALMACENAR NUEVOS PLANES EN LA BASE DE DATOS
    public function store(Request $request){
        // Recoger el token del header
        $jwtAuth = new \JwtAuth();
        $token = $request->header('Authorization');
        $user = $jwtAuth->checkToken($token, true);
        
        // Comprobar si el usuario tiene los permisos
        if($user->role == 'ROLE_ADMIN' || $user->role == 'ROLE_SUPER_ADMIN'){
            //Recoger el json del request
            $json = $request->input('json', null);
            $params = json_decode($json);
            $params_array = json_decode($json, true);
            
            if(!empty($params_array)){
                // Validar los datos que se están ingresando
                $validate = \Validator::make($params_array, [
                    'nom_plan' => 'required|regex:/^[\pL\s\-]+$/u|unique:PlanesMejora',
                    'fecha_ini' => 'required|date',
                    'fecha_fin' => 'nullable|date',
                    'responsable' => 'required',                   
                ]);
                if($validate->fails()){                    
                    $data = array(
                        'status' => 'error',
                        'code' => 400,
                        'message' => 'La validación de los datos ha fallado.',
                        'errors' => $validate->errors()
                    );
                } else{
                    $plan = new Plans();
                    $plan->nom_plan = $params_array['nom_plan'];
                    $plan->fecha_ini = $params_array['fecha_ini'];
                    $plan->fecha_fin = $params_array['fecha_fin'];
                    $plan->fuente = $params_array['fuente'];
                    $plan->responsable = $params_array['responsable'];
                    $plan->fecha_reg = $params_array['fecha_reg'];
                    $plan->usuario_reg = $user->user_alias;
                    
                    $plan->save(); //INSERT A LA BASE DE DATOS
                    $data = array(
                        'status' => 'success',
                        'code' => 200,
                        'message' => 'El Plan de Mejoramiento '.$plan->id.' se ha guardado correctamente.',
                        'plan' => $plan
                    );
                }
            } else{
                $data = array(
                    'status' => 'error',
                    'code' => 411,
                    'message' => 'Ha ingrasado los datos de manera incorrecta o incompletos'
                );
            }
        } else{
            $data = array(
                'status' => 'error',
                'code' => 401,
                'message' => 'Usted no tiene permisos para realizar esta acción. Inicie sesión con un usuario "Administrador" o "Super Administrador"'
            );              
        }
        // Devolver respuesta
        return response()->json($data, $data['code']);
    }
    
    //FUNCIÓN PARA ACTUALIZAR LOS PLANES DE LA BASE DE DATOS
    public function update($id, Request $request){
        // Recoger los datos del token del header
        $jwtAuth = new \JwtAuth();
        $token = $request->header('Authorization');
        $user = $jwtAuth->checkToken($token, true);
        
        // Validar si el usuario tiene los permisos
        if($user->role == 'ROLE_ADMIN' || $user->role == 'ROLE_SUPER_ADMIN'){
            // Recoger el json
            $json = $request->input('json', null);
            $params = json_decode($json);
            $params_array = json_decode($json, true);
            
            if(!empty($params_array)){
                $validate = \Validator::make($params_array, [
                    'nom_plan' => 'required|regex:/^[\pL\s\-]+$/u|unique:PlanesMejora,nom_plan,'.$id,
                    'fecha_ini' => 'required|date',
                    'fecha_fin' => 'required|date',
                    'responsable' => 'required'                     
                ]);
                if($validate->fails()){                   
                    $data = array(
                        'status' => 'error',
                        'code' => 400,
                        'message' => 'La validación de los datos ha fallado.',
                        'errors' => $validate->errors()
                    );                    
                } else{
                    // Retirar el contenido que no se desea actualizar
                    unset($params_array['id']);
                    unset($params_array['created_at']);
                    unset($params_array['updated_at']);
                    
                    // Actualizar los datos de la BD
                    $plan = Plans::where('id', $id)->update($params_array);
                    
                    // Devolver array con el resultado
                    if($plan != 0){
                        $data = array(
                            'status' => 'success',
                            'code' => 200,
                            'message' => 'El Plan de Mejora '.$id.' se ha actualizado correctamente.',
                            'changes' => $params_array
                        );
                    } else {
                        $data = array(
                            'status' => 'error',
                            'code' => 404,
                            'message' => 'El Plan de Mejoramiento '.$id.' no se ha podido actualizar.'
                        );  
                    }
                }
            } else{
                $data = array(
                    'status' => 'error',
                    'code' => 411,
                    'message' => 'Ha ingrasado los datos de manera incorrecta o incompletos.'
                );
            }
        } else{
            $data = array(
                'status' => 'error',
                'code' => 401,
                'message' => 'Usted no tiene permisos para realizar esta acción. Inicie sesión con un usuario "Administrador" o "Super Administrador'
            );              
        }
        // Devolver la respuesta+
        return response()->json($data, $data['code']);
    }
    
    //FUNCIÓN PARA ELIMINAR LOS PLANES    
    public function destroy($id, Request $request){
        // Conseguir el token del header
        $jwtAuth = new \JwtAuth();
        $token = $request->header('Authorization');
        $user = $jwtAuth->checkToken($token, true);
        
        // Validar si el usuario tiene los permisos
        if($user->role == 'ROLE_ADMIN' || $user->role == 'ROLE_SUPER_ADMIN'){
            $plan = Plans::where('id', $id)->first();
            if(!empty($plan)){
                $plan->delete();
                $data = array(
                    'status' => 'success',
                    'code' => 200,
                    'message' => 'El plan se ha eliminado correctamente',
                    'plan' => $plan
                );
            } else{
                $data = array(
                    'status' => 'error',
                    'code' => 404,
                    'message' => 'No existe ningun plan con este id'
                ); 
            }
        } else{
            $data = array(
                'status' => 'error',
                'code' => 401,
                'message' => 'Usted no tiene permisos para realizar esta acción.'
            );              
        }
        // Devolver respuesta
        return response()->json($data, $data['code']);
    }
}
