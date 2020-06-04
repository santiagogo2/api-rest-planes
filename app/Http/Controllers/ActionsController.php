<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Actions;
use App\ImprovementOpportunities;
use App\Plans;

class ActionsController extends Controller
{
    //FUNCIÓN PARA MOSTRAR LOS DATOS DE LAS ACCIONES
    public function index(Request $request){
        // Recoger los datos del token
        $jwtAuth = new \JwtAuth();
        $token = $request->header('Authorization', null);
        $user = $jwtAuth->checkToken($token, true);
        
        if($user->role == 'ROLE_ADMIN' || $user->role == 'ROLE_SUPER_ADMIN'){
            $actions = Actions::with('ImprovementOpportunities')
                              ->get();
            if(is_object($actions)){
                $data = array(
                    'status' => 'success',
                    'code' => 200,
                    'actions' => $actions
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
        $jwtAuth = new \JwtAuth();
        $token = $request->header('Authorization');
        $user = $jwtAuth->checkToken($token, true);
        
        // Comprobar si el usuario tiene permisos para acceder a la función
        if($user->role == 'ROLE_USER' || $user->role == 'ROLE_ADMIN' ||
           $user->role == 'ROLE_SUPER_ADMIN'){
            $action = Actions::with('ImprovementOpportunities')
                             ->find($id);
            if(is_object($action)){
                $data = array(
                    'status' => 'success',
                    'code' => 200,
                    'action' => $action
                );
            } else{
                $data = array(
                    'status' => 'error',
                    'code' => 404,
                    'message' => 'No se ha encontrado una acción con el id '.$id
                );
            }
        } else {
            $data = array(
                'status' => 'error',
                'code' => 401,
                'message' => 'Usted no tiene permisos para realizar esta acción.'
            );
        }
        // Devolver la respuesta
        return response()->json($data, $data['code']);
    }

    public function showByOpportunityId($id, Request $request){
        $jwtAuth = new \JwtAuth();
        $token = $request->header('Authorization');
        $user = $jwtAuth->checkToken($token, true);
        
        // Comprobar si el usuario tiene permisos para acceder a la función
        if($user->role == 'ROLE_USER' || $user->role == 'ROLE_ADMIN' ||
           $user->role == 'ROLE_SUPER_ADMIN'){
            if($user->role == 'ROLE_ADMIN' || $user->role == 'ROLE_SUPER_ADMIN'){
                $actions = Actions::where('id_oportunidad', $id)->get();
            } else {
                 $actions = Actions::where('id_oportunidad', $id)
                                   ->where('usuario_reg', $user->user_alias)
                                   ->where('usuario_reg', 'admin')
                                   ->get();
            }               

            if(sizeof($actions) != 0){
                $data = array(
                    'status' => 'success',
                    'code' => 200,
                    'actions' => $actions 
                );
            } else{
                $data = array(
                    'status' => 'error',
                    'code' => 404,
                    'message' => 'No se encontraron Acciones de Mejora para la Oportunidad de Mejora: '.$id
                );
            }
        } else {
            $data = array(
                'status' => 'error',
                'code' => 401,
                'message' => 'Usted no tiene permisos para realizar esta acción.'
            );
        }
        // Devolver la respuesta
        return response()->json($data, $data['code']);
    }

    public function showToExport(Request $request){
        // Recoger los datos del token
        $jwtAuth = new \JwtAuth();
        $token = $request->header('Authorization', null);
        $user = $jwtAuth->checkToken($token, true);
        
        if($user->role == 'ROLE_ADMIN' || $user->role == 'ROLE_SUPER_ADMIN'){
            $actions = Actions::all();

            if(is_object($actions)){
                foreach ($actions as $action){
                    $opportunity = ImprovementOpportunities::where('id', $action->id_oportunidad)->first();
                    $plan = Plans::where('id', $opportunity->id_plan)->first();
                    unset($action->id_oportunidad);
                    unset($action->observacion_cum);
                    unset($action->observacion_seg_linea);
                    unset($action->observacion_ter_linea);
                    unset($action->soporte);
                    unset($action->soporte2);
                    unset($action->soporte3);
                    unset($action->soporte4);
                    unset($action->soporte5);
                    unset($action->usuario_reg);
                    unset($action->created_at);
                    unset($action->updated_at);
                    unset($action->improvement_opportunities);
                    $action->id_plan = $plan->id;
                    $action->nom_plan = $plan->nom_plan;
                    $action->no_oportunidad = $opportunity->id;
                    $action->id_homologado = $opportunity->id_homologado;
                    $action->oportunidad = $opportunity->oportunidad_mejora;
                    $action->hallazgo = $opportunity->hallazgo;
                    $action->analisis = $opportunity->analisis;
                    $action->riesgo = $opportunity->riesgo;
                    $action->mesa = $opportunity->mesa;
                    $action->proceso = $opportunity->proceso;
                    $action->nom_indicador = $opportunity->nom_indicador;
                    $action->for_indicador = $opportunity->for_indicador;
                }
        

                $data = array(
                    'status' => 'success',
                    'code' => 200,
                    'actions' => $actions
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
    
    //FUNCIÓN PARA ALMACENAR NUEVAS ACCIONES EN LA BASE DE DATOS
    public function store(Request $request){
        //Recoger el token del header
        $jwtAuth = new \JwtAuth();
        $token = $request->header('Authorization');
        $user = $jwtAuth->checkToken($token, true);
        
        // Verificar si el usuario tiene permisos en esta sección
        if($user->role == 'ROLE_USER' || $user->role == 'ROLE_ADMIN' ||
           $user->role == 'ROLE_SUPER_ADMIN'){
            // Recoger el json del request
            $json = $request->input('json', null);
            $params = json_decode($json);
            $params_array = json_decode($json, true);
            
            if(!empty($params_array)){
                // Validar los datos ingresados
                $validate = \Validator::make($params_array, [
                    'id_oportunidad'    => 'required|numeric',
                    'tipo_accion'       => 'required|alpha',
                    'accion'            => 'required',
                    'fecha_ini_accion'  => 'required|date',
                    'fecha_fin_accion'  => 'required|date',
                    'responsable'       => 'required'
                ]);
                if($validate->fails()){
                    $data = array(
                        'status' => 'error',
                        'code' => 400,
                        'message' => 'La validación de los datos ha fallado.',
                        'errors' => $validate->errors()
                    );
                } else{
                    $action = new Actions();
                    $action->id_oportunidad = $params_array['id_oportunidad'];
                    $action->tipo_accion = $params_array['tipo_accion'];
                    $action->accion = $params_array['accion'];
                    $action->fecha_ini_accion = $params_array['fecha_ini_accion'];
                    $action->fecha_fin_accion = $params_array['fecha_fin_accion'];
                    $action->responsable = $params_array['responsable'];
                    $action->estado = "PENDIENTE";
                    $action->estado_seg_linea = "PENDIENTE";
                    $action->estado_ter_linea = "PENDIENTE";
                    $action->usuario_reg = $user->user_alias;
                    
                    $action->save();
                    $data = array(
                        'status' => 'success',
                        'code' => 200,
                        'message' => 'La Acción de Mejora se ha guardado correctamente.',
                        'plan' => $action
                    );
                }
            } else{
                $data = array(
                    'status' => 'error',
                    'code' => 411,
                    'message' => 'Ha ingrasado los datos de manera incorrecta o incompletos'
                );
            }
        } else {
            $data = array(
                'status' => 'error',
                'code' => 401,
                'message' => 'Usted no tiene permisos para realizar esta acción.'
            );
        }
        return response()->json($data, $data['code']);
    }
    
    //FUNCIÓN PARA ACTUALIZAR LAS ACCIONES EN LA BASE DE DATOS
    public function update($id, Request $request){
        //Recoger el token del header
        $jwtAuth = new \JwtAuth();
        $token = $request->header('Authorization');
        $user = $jwtAuth->checkToken($token, true);
        
        // Verificar si el usuario tiene permisos en esta sección
        if($user->role == 'ROLE_USER' || $user->role == 'ROLE_ADMIN' ||
           $user->role == 'ROLE_SUPER_ADMIN'){
            //Obtener los datos json
            $json = $request->input('json', null);
            $params = json_decode($json);
            $params_array = json_decode($json, true);
            
            if(!empty($params_array)){
                // Validar los datos ingresados
                $validate = \Validator::make($params_array, [
                    'tipo_accion'           => 'required|alpha',
                    'accion'                => 'required',
                    'fecha_ini_accion'      => 'required|date',
                    'fecha_fin_accion'      => 'required|date',
                    'responsable'           => 'required'
                ]);
                if($validate->fails()){
                    $data = array(
                        'status' => 'error',
                        'code' => 400,
                        'message' => 'La validación de los datos ha fallado.',
                        'errors' => $validate->errors()
                    );
                } else {
                    // Eliminar lo que no se desea actualizar
                    unset($params_array['id']);
                    unset($params_array['id_oportunidad']);
                    unset($params_array['estado']);
                    unset($params_array['observacion_cum']);
                    unset($params_array['estado_seg_linea']);
                    unset($params_array['observacion_seg_linea']);
                    unset($params_array['estado_ter_linea']);
                    unset($params_array['observacion_ter_linea']);
                    unset($params_array['soporte']);
                    unset($params_array['soporte2']);
                    unset($params_array['soporte3']);
                    unset($params_array['soporte4']);
                    unset($params_array['soporte5']);
                    unset($params_array['usuario_reg']);
                    unset($params_array['created_at']);
                    unset($params_array['updated_at']);
                    
                    // Actualizar los datos de la BD
                    $plan = Actions::where('id', $id)->update($params_array);
                    
                    // Devolver array con el resultado
                    if($plan != 0){
                        $data = array(
                            'status' => 'success',
                            'code' => 200,
                            'message' => 'La Acción de Mejora '.$id. ' se ha actualizado correctamente',
                            'changes' => $params_array
                        );
                    } else {
                        $data = array(
                            'status' => 'error',
                            'code' => 404,
                            'message' => 'No se ha actualizado ningún dato'
                        );  
                    }
                }
            } else{
                $data = array(
                    'status' => 'error',
                    'code' => 411,
                    'message' => 'Ha ingrasado los datos de manera incorrecta o incompletos'
                );
            }
        } else {            
            $data = array(
                'status' => 'error',
                'code' => 401,
                'message' => 'Usted no tiene permisos para realizar esta acción.'
            );
        }
        //Devolver respuesta
        return response()->json($data, $data['code']);
    }
    
    public function updateFirstLine($id, Request $request){
        $jwtAuth = new \JwtAuth();
        $token = $request->header('Authorization');
        $user = $jwtAuth->checkToken($token, true);
        
        // Verificar si el usuario tiene permisos para acceder a esta sección
        if($user->role == 'ROLE_USER' || $user->role == 'ROLE_ADMIN' ||
           $user->role == 'ROLE_SUPER_ADMIN'){
            // Recoger los datos del json
            $json = $request->input('json', null);
            $params = json_decode($json);
            $params_array = json_decode($json, true);
            
            if(!empty($params_array)){
                // Validar los datos
                $validate = \Validator::make($params_array, [
                    'estado'            => 'required|alpha',
                    'observacion_cum'   => 'nullable',
                    'soporte'           => 'nullable',
                    'usuario_soporte1'  => 'nullable',
                    'soporte2'          => 'nullable',
                    'usuario_soporte2'  => 'nullable',
                    'soporte3'          => 'nullable',
                    'usuario_soporte3'  => 'nullable',
                    'soporte4'          => 'nullable',
                    'usuario_soporte4'  => 'nullable',
                    'soporte5'          => 'nullable',
                    'usuario_soporte5'  => 'nullable'
                ]);
                if($validate->fails()){
                    $data = array(
                        'status' => 'error',
                        'code' => 400,
                        'message' => 'La validación de los datos ha fallado.',
                        'errors' => $validate->errors()
                    );
                } else{
                    // Quitar lo que no se desea actualizar
                    unset($params_array['id']);
                    unset($params_array['id_oportunidad']);
                    unset($params_array['estado_seg_linea']);
                    unset($params_array['observacion_seg_linea']);
                    unset($params_array['estado_ter_linea']);
                    unset($params_array['observacion_ter_linea']);
                    unset($params_array['usuario_reg']);
                    unset($params_array['created_at']);
                    unset($params_array['updated_at']);
                    
                    // Actualizar los datos de la BD
                    $plan = Actions::where('id', $id)->update($params_array);
                    
                    // Devolver array con el resultado
                    if($plan != 0){
                        $data = array(
                            'status' => 'success',
                            'code' => 200,
                            'message' => 'La Acción de Mejora '.$id.' se ha actualizado correctamente',
                            'changes' => $params_array
                        );
                    } else {
                        $data = array(
                            'status' => 'error',
                            'code' => 404,
                            'message' => 'No se ha podido actualizar la Acción de Mejora con el id '.$id
                        );  
                    }
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
                'message' => 'Usted no tiene permisos para realizar esta acción.'
            );
        }
        // Devolvere la respuesta
        return response()->json($data, $data['code']);
    }
    
    public function updateSecondLine($id, Request $request){
        $jwtAuth = new \JwtAuth();
        $token = $request->header('Authorization');
        $user = $jwtAuth->checkToken($token, true);
        
        // Verificar si el usuario tiene permisos para acceder a esta sección
        if($user->role == 'ROLE_USER' || $user->role == 'ROLE_ADMIN' ||
           $user->role == 'ROLE_SUPER_ADMIN'){
            // Recoger los datos del json
            $json = $request->input('json', null);
            $params = json_decode($json);
            $params_array = json_decode($json, true);
            
            if(!empty($params_array)){
                // Validar los datos
                $validate = \Validator::make($params_array, [
                    'estado_seg_linea' => 'required|alpha',
                    'observacion_seg_linea' => 'required'
                ]);
                if($validate->fails()){
                    $data = array(
                        'status' => 'error',
                        'code' => 400,
                        'message' => 'La validación de los datos ha fallado.',
                        'errors' => $validate->errors()
                    );
                } else{
                    // Quitar lo que no se desea actualizar
                    unset($params_array['id']);
                    unset($params_array['id_oportunidad']);
                    unset($params_array['tipo_accion']);
                    unset($params_array['accion']);
                    unset($params_array['fecha_ini_accion']);
                    unset($params_array['fecha_fin_accion']);
                    unset($params_array['estado']);
                    unset($params_array['observacion_cum']);
                    unset($params_array['estado_ter_linea']);
                    unset($params_array['observacion_ter_linea']);
                    unset($params_array['soporte']);
                    unset($params_array['soporte2']);
                    unset($params_array['soporte3']);
                    unset($params_array['soporte4']);
                    unset($params_array['soporte5']);
                    unset($params_array['usuario_reg']);
                    unset($params_array['created_at']);
                    unset($params_array['updated_at']);
                    
                    // Actualizar los datos de la BD
                    $plan = Actions::where('id', $id)->update($params_array);
                    
                    // Devolver array con el resultado
                    if($plan != 0){
                        $data = array(
                            'status' => 'success',
                            'code' => 200,
                            'message' => 'La Acción de Mejora '.$id.' se ha actualizado correctamente',
                            'changes' => $params_array
                        );
                    } else {
                        $data = array(
                            'status' => 'error',
                            'code' => 404,
                            'message' => 'No se ha podido actualizar la Acción de Mejora con el id '.$id
                        );  
                    }
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
                'message' => 'Usted no tiene permisos para realizar esta acción.'
            );
        }
        // Devolvere la respuesta
        return response()->json($data, $data['code']);
    }
    
    public function updateThirdLine($id, Request $request){
        //Recoger el token del header
        $jwtAuth = new \JwtAuth();
        $token = $request->header('Authorization');
        $user = $jwtAuth->checkToken($token, true);
        
        // Verificar si el usuario tiene permisos para acceder a esta sección
        if($user->role == 'ROLE_USER' || $user->role == 'ROLE_ADMIN' ||
           $user->role == 'ROLE_SUPER_ADMIN'){
            // Recoger los datos del json
            $json = $request->input('json', null);
            $params = json_decode($json);
            $params_array = json_decode($json, true);
            
            if(!empty($params_array)){
                // Validar los datos
                $validate = \Validator::make($params_array, [
                    'estado_ter_linea' => 'required|alpha',
                    'observacion_ter_linea' => 'required'
                ]);
                if($validate->fails()){
                    $data = array(
                        'status' => 'error',
                        'code' => 400,
                        'message' => 'La validación de los datos ha fallado.',
                        'errors' => $validate->errors()
                    );
                } else{
                    // Quitar lo que no se desea actualizar
                    unset($params_array['id']);
                    unset($params_array['id_oportunidad']);
                    unset($params_array['tipo_accion']);
                    unset($params_array['accion']);
                    unset($params_array['fecha_ini_accion']);
                    unset($params_array['fecha_fin_accion']);
                    unset($params_array['estado']);
                    unset($params_array['observacion_cum']);
                    unset($params_array['estado_seg_linea']);
                    unset($params_array['observacion_seg_linea']);
                    unset($params_array['soporte']);
                    unset($params_array['soporte2']);
                    unset($params_array['soporte3']);
                    unset($params_array['soporte4']);
                    unset($params_array['soporte5']);
                    unset($params_array['usuario_reg']);
                    unset($params_array['created_at']);
                    unset($params_array['updated_at']);
                    
                    // Actualizar los datos de la BD
                    $plan = Actions::where('id', $id)->update($params_array);
                    
                    // Devolver array con el resultado
                    if($plan != 0){
                        $data = array(
                            'status' => 'success',
                            'code' => 200,
                            'message' => 'La Acción de Mejora '.$id.' se ha actualizado correctamente',
                            'changes' => $params_array
                        );
                    } else {
                        $data = array(
                            'status' => 'error',
                            'code' => 404,
                            'message' => 'No se ha podido actualizar la Acción de Mejora con el id '.$id
                        );  
                    }
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
                'message' => 'Usted no tiene permisos para realizar esta acción.'
            );
        }
        // Devolvere la respuesta
        return response()->json($data, $data['code']);
    }

    function updateAllLines($id, Request $request){
        // Recoger el token del header
        $token = $request->header('Authorization');
        $jwtAuth = new \JwtAuth();
        $user = $jwtAuth->checkToken($token, true);

        // Comprobar si el usuario tiene permisos
        if($user->role == 'ROLE_USER' || 
           $user->role == 'ROLE_ADMIN' || 
           $user->role == 'ROLE_SUPER_ADMIN'){
            // Recoger los datos json
            $json = $request->input('json', null);
            $params = json_decode($json);
            $params_array = json_decode($json, true);

            if(!empty($params_array)){
                // Validar los datos que ingresaron
                $validate = \Validator::make($params_array, [
                    'estado' => 'required|alpha',
                    'estado_seg_linea' => 'required|alpha',
                    'estado_ter_linea' => 'required|alpha',
                ]);
                if($validate->fails()){
                    $data = array(
                        'status' => 'error',
                        'code' => 400,
                        'message' => 'La validación de los datos ha fallado.',
                        'errors' => $validate->errors()
                    );
                } else {
                    // Eliminar lo que no se desea actualizar
                    unset($params_array['id']);
                    unset($params_array['id_oportunidad']);
                    unset($params_array['tipo_accion']);
                    unset($params_array['accion']);
                    unset($params_array['fecha_ini_accion']);
                    unset($params_array['fecha_fin_accion']);
                    unset($params_array['observacion_cum']);
                    unset($params_array['observacion_seg_linea']);
                    unset($params_array['soporte']);
                    unset($params_array['soporte2']);
                    unset($params_array['soporte3']);
                    unset($params_array['soporte4']);
                    unset($params_array['soporte5']);
                    unset($params_array['usuario_reg']);
                    unset($params_array['created_at']);
                    unset($params_array['updated_at']);
                    
                    // Actualizar los datos de la BD
                    $actions = Actions::where('id', $id)->update($params_array);
                    
                    // Devolver array con el resultado
                    if($actions != 0){
                        $data = array(
                            'status' => 'success',
                            'code' => 200,
                            'message' => 'La Acción de Mejora '.$id.' se ha homologado correctamente.',
                            'changes' => $params_array
                        );
                    } else {
                        $data = array(
                            'status' => 'error',
                            'code' => 404,
                            'message' => 'No se ha podido homologar correctamente la Acción de Mejora con el id '.$id
                        );  
                    }
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
                'message' => 'Usted no tiene permisos para realizar esta acción.'
            );
        }
        return response()->json($data, $data['code']);
    }

    //GUARDAR ARCHIVOS
    function uploadFile(Request $request){
        // Recoger los datos de la petición
        $file = $request->file('file0');

        // Validación del archivo
        $validate = \Validator::make($request->all(), [
            'file0' => 'required'
        ]);
        if($validate->fails()){
            $data = array(
                'status' => 'error',
                'code' => 400,
                'message' => 'La validación de los datos ha fallado. No ha subido los archivos correctamente',
                'errors' => $validate->errors()
            );            
        } else {
            // Guardar la imágen
            if($file){
                $file_name = time().$file->getClientOriginalName();
                \Storage::disk('sop_mejoramiento')->put($file_name, \File::get($file));

                $data = array(
                    'status' => 'success',
                    'code' => 200,
                    'message' => 'El archivo '.$file->getClientOriginalName().' se ha subido correctamente al servidor.',
                    'file' => $file_name
                );
            } else{
                $data = array(
                    'status' => 'error',
                    'code' => 411,
                    'message' => 'No se ha podido subir el archivo '.$file->getClientOriginalName().' al servidor.'
                );
            }
        }
            
        // Devolver el resultado
        return response()->json($data, $data['code']);
    }

    //OBTENER UN ARCHIVO
    public function getFile($filename, Request $request){
        $isset = \Storage::disk('sop_mejoramiento')->exists($filename);
        if($isset){
            $file = \Storage::disk('sop_mejoramiento')->get($filename);
            return new Response($file, 200);
        } else{
            $data = array(
                'status' => 'error',
                'code' => 400,
                'message' => 'El archivo '.$filename.' no existe en el servidor.'
            );
            return response()->json($data, $data['code']);
        }
    }

    //ELIMINAR UN ARCHIVO
    public function deleteFile($filename, Request $request){
        $isset = \Storage::disk('sop_mejoramiento')->exists($filename);
        if($isset){
            $file = \Storage::disk('sop_mejoramiento')->delete($filename);
            $data = array(
                'status' => 'success',
                'code' => 200,
                'message' => 'El archivo '.$filename.' se ha eliminado correctamente.'
            );
        } else{
            $data = array(
                'status' => 'error',
                'code' => 400,
                'message' => 'El archivo '.$filename.' no existe en el servidor.'
            );
        }
        return response()->json($data, $data['code']);
    }
    
    //FUNCIÓN PARA ELIMINAR ACCIONES
    public function destroy($id, Request $request){
        // Recoger el token del header
        $jwtAuth = new \JwtAuth();
        $token = $request->header('Authorization');
        $user = $jwtAuth->checkToken($token, true);
        
        // Verificar si el usuario tiene permisos para acceder a esta sección
        if($user->role == 'ROLE_ADMIN' ||
           $user->role == 'ROLE_SUPER_ADMIN'){
            $action = Actions::where('id', $id)->first();
            // Comprobar si existe la acción con el id
            if(!empty($action)){
                //Eliminar el registro
                $action->delete();
                $data = array(
                    'status' => 'success',
                    'code' => 200,
                    'message' => 'La Acción de Mejora '.$id.' se ha eliminado correctamente.',
                    'plan' => $action
                );
            } else{
                $data = array(
                    'status' => 'error',
                    'code' => 404,
                    'message' => 'No existe ninguna Acción de Mejora con el id: '.$id
                ); 
            }
        } else{
            $data = array(
                'status' => 'error',
                'code' => 401,
                'message' => 'Usted no tiene permisos para realizar esta acción. Inicie sesión con un usuario "Administrador" o "Super Administrador"'
            );
        }
        // Devolvere la respuesta
        return response()->json($data, $data['code']);
    }
}
