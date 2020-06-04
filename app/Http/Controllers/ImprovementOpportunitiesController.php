<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\ImprovementOpportunities;
use App\Plans;

class ImprovementOpportunitiesController extends Controller
{
    //FUNCIÓNES PARA MOSTRAR TODOS LAS OPORTUNIDADES DE MEJORAMIENTO CREADAS    
    public function index(){
        // Buscar las acciones de mejora existentes
        $improvementOpportunities = ImprovementOpportunities::all();
        if(is_object($improvementOpportunities)){
            $data = array(
                'status' => 'success',
                'code' => 200,
                'improvementOpportunities' => $improvementOpportunities
            );
        } else{
            $data = array(
                'status' => 'error',
                'code' => 404,
                'message' => 'No se han encontrado oportunidades de mejoramiento en la base de datos.'
            );
        }
        // Devolver respuesta
        return response()->json($data, $data['code']);
    }
    
    public function show($id){
        // Buscar la cción de mejora por el id
        $improvementOpportunity = ImprovementOpportunities::with('Plans')
                                                          ->find($id);

        if(is_object($improvementOpportunity)){
            $data = array(
                'status' => 'success',
                'code' => 200,
                'improvementOpportunity' => $improvementOpportunity
            );
        } else{
            $data = array(
                'status' => 'error',
                'code' => 404,
                'message' => 'No se ha encontrado una Oportunidad de Mejoramiento con el id '.$id
            );
        }
        // Devolver respuesta
        return response()->json($data, $data['code']);
    }
    
    public function showByIdPlan($id, $status){
        // Buscar las acciones de mejora por el id del plan
        $plans = array();
        if($status == 1){
            $improvementOpportunities = ImprovementOpportunities::with('Actions')
                                                                ->where('id_plan', $id)
                                                                ->where('estado', $status)
                                                                ->get();
        } else {
            $improvementOpportunities = ImprovementOpportunities::with('Actions')
                                                                ->where('id_plan', $id)
                                                                ->where('estado', '!=', '1')
                                                                ->get();
        }
            
        
        // Validar si encontró datos con el id ingresado
        if(sizeof($improvementOpportunities) != 0){
            foreach ($improvementOpportunities as $opportunity){
                $plan = Plans::select('nom_plan')
                             ->where('id', $opportunity->id_plan)
                             ->first();
                if(is_object($plan)){
                    $plan = array_merge(json_decode($plan, true), json_decode($opportunity, true));
                    array_push($plans, $plan);
                }                    
            }
            $data = array(
                'status' => 'success',
                'code' => 200,
                'improvementOpportunities' => $plans
            );
        } else{
            $data = array(
                'status' => 'error',
                'code' => 404,
                'message' => 'No se encontraron Oportunidades de Mejora relacionadas con el plan ingresado: '.$id
            );
        }
        // Devolver respuesta
        return response()->json($data, $data['code']);
    }

    public function showByResponsable($responsable, $status){
        // Buscar las acciones de mejora por el proceso responsable        
        if($status == 1){
            $improvementOpportunities = ImprovementOpportunities::with('Plans')
                                                                ->where('proceso', $responsable)
                                                                ->where('estado', $status)
                                                                ->get();
        } else {
            $improvementOpportunities = ImprovementOpportunities::with('Plans')
                                                                ->where('proceso', $responsable)
                                                                ->where('estado', '!=', '1')
                                                                ->get();
        }
        
        // Validar si encontró datos con el id ingresado
        if(sizeof($improvementOpportunities) != 0){
            $data = array(
                'status' => 'success',
                'code' => 200,
                'improvementOpportunities' => $improvementOpportunities
            );
        } else{
            $data = array(
                'status' => 'error',
                'code' => 404,
                'message' => 'No se encontraron acciones de mejora relacionadas con el proceso seleccionado.'
            );
        }
        // Devolver respuesta
        return response()->json($data, $data['code']);
    }

    public function showByWord($word, $status, Request $request){
        // Obtener el token del header
        $jwtAuth = new \JwtAuth();
        $token = $request->header('Authorization');
        $user = $jwtAuth->checkToken($token, true);

        // Comprobar si el usuario autenticado fue el mismo que creo la oportunidad
        if($user->role == 'ROLE_USER' ||
           $user->role == 'ROLE_ADMIN' ||
           $user->role == 'ROLE_SUPER_ADMIN'){
            // Buscar la oportunidad de mejora para sacar el usuario
            if($status == 1){
                $improvementOpportunities = ImprovementOpportunities::with('Plans')
                                                                    ->where('oportunidad_mejora', 'like', '%'.$word.'%')
                                                                    ->where('estado', $status)
                                                                    ->get();
            } elseif ($status == -1) {
                $improvementOpportunities = ImprovementOpportunities::with('Plans')
                                                                    ->where('oportunidad_mejora', 'like', '%'.$word.'%')
                                                                    ->get();
            } else {
                $improvementOpportunities = ImprovementOpportunities::with('Plans')
                                                                    ->where('oportunidad_mejora', 'like', '%'.$word.'%')
                                                                    ->where('estado', '!=', '1')
                                                                    ->get();
            }

            if(is_object($improvementOpportunities) && sizeof($improvementOpportunities) != 0){
                $data = array(
                    'status' => 'success',
                    'code' => 200,
                    'improvementOpportunities' => $improvementOpportunities
                );
            } else {
                $data = array(
                    'status' => 'error',
                    'code' => 404,
                    'message' => 'No hay ninguna Oportunidad de Mejora que contenga la palabra: '.$word
                ); 
            }
        } else{
            $data = array(
                'status' => 'error',
                'code' => 401,
                'message' => 'Usted no tiene permisos para acceder a esta sección.'
            );   
        } 
        // Devolver respuesta
        return response()->json($data, $data['code']);        
    }
    
    //FUNCIÓN PARA ALMACENAR NUEVAR OPORTUNIDADES DE MEJORA EN LA BASE DE DATOS
    public function store(Request $request){
        // Recoger los datos del usuario autenticado
        $jwtAuth = new \JwtAuth();
        $token = $request->header('Authorization');
        $user = $jwtAuth->checkToken($token, true);
        
        // Obtener los datos por post
        $json = $request->input('json', null);
        $params = json_decode($json);
        $params_array = json_decode($json, true);
        
        if(!empty($params_array)){
            // Validar los datos ingresados
            $validate = \Validator::make($params_array, [
                'id_plan'               => 'required|numeric',
                'oportunidad_mejora'    => 'required',
                'hallazgo'              => 'required',
                'tipo_hallazgo'         => 'required',
                'analisis'              => 'required',
                'riesgo'                => 'required',
                'mesa'                  => 'required',
                'proceso'               => 'required',
                'nom_indicador'         => 'required',
                'for_indicador'         => 'required',
                'meta'                  => 'required',
                'id_homologado'         => 'nullable|numeric',
                'causa_homologacion'    => 'nullable',
                'auditor'               => 'required',
                'fecha_auditoria'       => 'required|date',
                'cum_pri_linea'         => 'required|numeric',
                'cum_seg_linea'         => 'required|numeric',
                'cum_ter_linea'         => 'required|numeric',
                'cum_indicador'         => 'required|numeric',
                'numerador'             => 'numeric',
                'denominador'           => 'numeric',
                'estado'                => 'numeric'                
            ]);            
            if($validate->fails()){
                $data = array(
                    'status' => 'error',
                    'code' => 400,
                    'message' => 'La validación de los datos enviados al servidor ha fallado.',
                    'errors' => $validate->errors()
                );
            } else{
                // Asignar los valores al objeto
                $improvementOpportunity = new ImprovementOpportunities();
                $improvementOpportunity->id_plan = $params->id_plan;
                $improvementOpportunity->oportunidad_mejora = $params->oportunidad_mejora;
                $improvementOpportunity->hallazgo = $params->hallazgo;
                $improvementOpportunity->tipo_hallazgo = $params->tipo_hallazgo;
                $improvementOpportunity->analisis = $params->analisis;
                $improvementOpportunity->riesgo = $params->riesgo;
                $improvementOpportunity->mesa = $params->mesa;
                $improvementOpportunity->proceso = $params->proceso;
                $improvementOpportunity->nom_indicador = $params->nom_indicador;
                $improvementOpportunity->for_indicador = $params->for_indicador;
                $improvementOpportunity->meta = $params->meta;
                $improvementOpportunity->id_homologado = $params->id_homologado;
                $improvementOpportunity->causa_homologacion = $params->causa_homologacion;
                $improvementOpportunity->auditor = $params->auditor;
                $improvementOpportunity->fecha_auditoria = $params->fecha_auditoria;
                $improvementOpportunity->cum_pri_linea = $params->cum_pri_linea;
                $improvementOpportunity->cum_seg_linea = $params->cum_seg_linea;
                $improvementOpportunity->cum_ter_linea = $params->cum_ter_linea;
                $improvementOpportunity->cum_indicador = $params->cum_indicador;
                $improvementOpportunity->usuario_reg = $user->user_alias;
                $improvementOpportunity->numerador = $params->numerador;
                $improvementOpportunity->denominador = $params->denominador; 
                $improvementOpportunity->estado = $params->estado;
                
                // Guardar los datos
                $improvementOpportunity->save();
                $data = array(
                    'status' => 'success',
                    'code' => 200,
                    'message' => 'La Oportunidad de Mejoramiento se ha guardado correctamente.',
                    'improvementOpportunity' => $improvementOpportunity
                );
            }
        } else{
            $data = array(
                'status' => 'error',
                'code' => 411,
                'message' => 'La petición ha enviado datos de manera incorrecta al servidor.'
            );
        }
        // Devolver respuesta
        return response()->json($data, $data['code']);
    }
    
    //FUNCIÓN PARA ACTUALIZAR OPORTUNIDADES DE MEJORA EN LA BASE DE DATOS
    public function update($id, Request $request){
        // Obtener el token del header
        $jwtAuth = new \JwtAuth();
        $token = $request->header('Authorization');
        $user = $jwtAuth->checkToken($token, true);
        
        // Buscar la oportunidad de mejora para sacar el usuario
        $improvementOportunity = ImprovementOpportunities::find($id);
        if(is_object($improvementOportunity)){
            // Comprobar si el usuario autenticado fue el mismo que creo la oportunidad
            if($user->user_alias == $improvementOportunity->usuario_reg || $user->role == 'ROLE_SUPER_ADMIN'){
                // Recoger el json
                $json = $request->input('json', null);
                $params = json_decode($json);
                $params_array = json_decode($json, true);

                if(!empty($params_array)){
                    // Validar los datos
                    $validate = \Validator::make($params_array, [
                        'id_plan'               => 'required|numeric',
                        'oportunidad_mejora'    => 'required',
                        'hallazgo'              => 'required',
                        'tipo_hallazgo'         => 'required',
                        'analisis'              => 'required',
                        'riesgo'                => 'required',
                        'mesa'                  => 'required',
                        'proceso'               => 'required',
                        'nom_indicador'         => 'required',
                        'for_indicador'         => 'required',
                        'meta'                  => 'required',
                        'auditor'               => 'required',
                        'fecha_auditoria'       => 'required|date'
                    ]);
                    if($validate->fails()){
                        $data = array(
                            'status' => 'error',
                            'code' => 400,
                            'message' => 'La validación de los datos enviados al servidor ha fallado.',
                            'errors' => $validate->errors()
                        );
                    } else{
                        // Eliminar los datos que no se van a actualizar
                        unset($params_array['id']);
                        unset($params_array['cum_pri_linea']);
                        unset($params_array['cum_seg_linea']);
                        unset($params_array['cum_ter_linea']);
                        unset($params_array['cum_indicador']);
                        unset($params_array['fecha_reg']);
                        unset($params_array['usuario_reg']);
                        unset($params_array['numerador']);
                        unset($params_array['denominador']);
                        unset($params_array['estado']);
                        unset($params_array['created_at']);
                        unset($params_array['updated_at']);

                        // Actualizar la oportunidad en la bd
                        $improvementOportunity = ImprovementOpportunities::where('id', $id)->update($params_array);
                        $data = array(
                            'status' => 'success',
                            'code' => 200,
                            'message' => 'La Oportunidad de Mejora '.$id.' se ha actualizado correctamente',
                            'changes' => $params_array
                        );
                    }
                } else{
                    $data = array(
                        'status' => 'error',
                        'code' => 411,
                        'message' => 'La petición ha enviado datos de manera incorrecta al servidor.'
                    );                
                }
            } else{
                $data = array(
                    'status' => 'error',
                    'code' => 401,
                    'message' => 'Usted no tiene permisos para acceder a esta sección. Inicie sesión con un usuario "Super Administrador".'
                );   
            }
        } else {
            $data = array(
                'status' => 'error',
                'code' => 404,
                'message' => 'La Oportunidad de Mejora '.$id.' no existe.'
            ); 
        }
        // Devolver respuesta
        return response()->json($data, $data['code']);        
    }

    public function updateLines($id, Request $request){
        // Conseguir el usuario del token
        $jwtAuth = new \JwtAuth();
        $token = $request->header('Authorization');
        $user = $jwtAuth->checkToken($token, true);

        if($user->role == 'ROLE_USER' || 
           $user->role == 'ROLE_ADMIN' ||
           $user->role == 'ROLE_SUPER_ADMIN'){
            // Recoger los datos Json
            $json = $request->input('json', null);
            $params = json_decode($json);
            $params_array = json_decode($json, true);

            if(!empty($params_array)){
                // Validar los datos
                $validate = \Validator::make($params_array, [
                    'cum_pri_linea'         => 'required|numeric',
                    'cum_seg_linea'         => 'required|numeric',
                    'cum_ter_linea'         => 'required|numeric'
                ]);

                if($validate->fails()){
                    $data = array(
                        'status' => 'error',
                        'code' => 400,
                        'message' => 'La validación de los datos enviados al servidor ha fallado.',
                        'errors' => $validate->errors()
                    );
                } else{
                    // Eliminar los datos que no se van a actualizar
                    unset($params_array['id']);
                    unset($params_array['id_plan']);
                    unset($params_array['oportunidad_mejora']);
                    unset($params_array['hallazgo']);
                    unset($params_array['analisis']);
                    unset($params_array['riesgo']);
                    unset($params_array['mesa']);
                    unset($params_array['proceso']);
                    unset($params_array['nom_indicador']);
                    unset($params_array['for_indicador']);
                    unset($params_array['meta']);
                    unset($params_array['cum_indicador']);
                    unset($params_array['numerador']);
                    unset($params_array['denominador']);
                    unset($params_array['estado']);
                    unset($params_array['usuario_reg']);
                    unset($params_array['created_at']);
                    unset($params_array['updated_at']);

                    // Actualizar la oportunidad en la bd
                    $improvementOportunity = ImprovementOpportunities::where('id', $id)->update($params_array);
                    $data = array(
                        'status' => 'success',
                        'code' => 200,
                        'message' => 'Los indicadores de la Oportunidad de Mejora '.$id.' se ha actualizado correctamente',
                        'changes' => $params_array
                    );
                }
            } else{
                $data = array(
                    'status' => 'error',
                    'code' => 411,
                    'message' => 'La petición ha enviado datos de manera incorrecta al servidor.'
                ); 
            }
        } else{
            $data = array(
                'status' => 'error',
                'code' => 401,
                'message' => 'Usted no tiene permisos para acceder a esta sección.'
            );
        }
        // Devolver respuesta
        return response()->json($data, $data['code']);
    }

    public function updateEffectivenessIndicator($id, Request $request){
        // Conseguir el usuario del token
        $jwtAuth = new \JwtAuth();
        $token = $request->header('Authorization');
        $user = $jwtAuth->checkToken($token, true);

        if($user->role == 'ROLE_USER' || 
           $user->role == 'ROLE_ADMIN' ||
           $user->role == 'ROLE_SUPER_ADMIN'){
            // Recoger los datos Json
            $json = $request->input('json', null);
            $params = json_decode($json);
            $params_array = json_decode($json, true);

            if(!empty($params_array)){
                // Validar los datos
                $validate = \Validator::make($params_array, [
                    'nom_indicador'         => 'required',
                    'for_indicador'         => 'required',
                    'cum_indicador'         => 'required|numeric',
                    'numerador'             => 'required|numeric',
                    'denominador'           => 'required|numeric'
                ]);

                if($validate->fails()){
                    $data = array(
                        'status' => 'error',
                        'code' => 400,
                        'message' => 'La validación de los datos enviados al servidor ha fallado.',
                        'errors' => $validate->errors()
                    );
                } else{
                    // Eliminar los datos que no se van a actualizar
                    unset($params_array['id']);
                    unset($params_array['id_plan']);
                    unset($params_array['oportunidad_mejora']);
                    unset($params_array['hallazgo']);
                    unset($params_array['analisis']);
                    unset($params_array['riesgo']);
                    unset($params_array['mesa']);
                    unset($params_array['proceso']);
                    unset($params_array['meta']);
                    unset($params_array['cum_pri_linea']);
                    unset($params_array['cum_seg_linea']);
                    unset($params_array['cum_ter_linea']);
                    unset($params_array['estado']);
                    unset($params_array['usuario_reg']);
                    unset($params_array['created_at']);
                    unset($params_array['updated_at']);

                    // Actualizar la oportunidad en la bd
                    $improvementOportunity = ImprovementOpportunities::where('id', $id)->update($params_array);
                    $data = array(
                        'status' => 'success',
                        'code' => 200,
                        'message' => 'Los datos del indicador de efectividad de la Oportunidad de Mejora '.$id.' se ha actualizado correctamente',
                        'changes' => $params_array
                    );
                }
            } else{
                $data = array(
                    'status' => 'error',
                    'code' => 411,
                    'message' => 'La petición ha enviado datos de manera incorrecta al servidor.'
                ); 
            }
        } else{
            $data = array(
                'status' => 'error',
                'code' => 401,
                'message' => 'Usted no tiene permisos para acceder a esta sección.'
            );
        }
        // Devolver respuesta
        return response()->json($data, $data['code']);
    }

    public function updateStatus($id, $status, Request $request){
        // Conseguir el usuario del token
        $jwtAuth = new \JwtAuth();
        $token = $request->header('Authorization');
        $user = $jwtAuth->checkToken($token, true);

        // Comprobar si el usuario tiene los permisos
        if($user->role == 'ROLE_USER' ||
           $user->role == 'ROLE_ADMIN' ||
           $user->role == 'ROLE_SUPER_ADMIN'){
            // Actualizar la oportunidad en la bd
            if($status == 0){
                $improvementOportunity = ImprovementOpportunities::where('id', $id)->update(['estado' => 0]);
                $data = array(
                    'status' => 'success',
                    'code' => 200,
                    'message' => 'El estado de la Oportunidad de Mejora '.$id.' se ha actualizado a HOMOLOGADO correctamente.',
                );
            } else if ($status == 1){
                $improvementOportunity = ImprovementOpportunities::where('id', $id)->update(['estado' => 1]);
                $data = array(
                    'status' => 'success',
                    'code' => 200,
                    'message' => 'El estado de la Oportunidad de Mejora '.$id.' se ha actualizado a ABIERTO correctamente.',
                );
            } else if ($status == 2){
                $improvementOportunity = ImprovementOpportunities::where('id', $id)->update(['estado' => 2]);
                $data = array(
                    'status' => 'success',
                    'code' => 200,
                    'message' => 'El estado de la Oportunidad de Mejora '.$id.' se ha actualizado a CERRADO correctamente.',
                );
            } else {
                $data = array(
                    'status' => 'error',
                    'code' => 404,
                    'message' => 'El estado que está ingresando no es correcto.',
                );
            }
        } else{
            $data = array(
                'status' => 'error',
                'code' => 401,
                'message' => 'Usted no tiene permisos para acceder a esta sección. Inicie sesión con un usuario "Super Administrador".'
            );
        }
        // Devolver respuesta
        return response()->json($data, $data['code']);
    }

    public function updateDataHomologate($id, Request $request){
        // Conseguir el usuario del token
        $jwtAuth = new \JwtAuth();
        $token = $request->header('Authorization');
        $user = $jwtAuth->checkToken($token, true);
        
        // Comprobar si el usuario tiene los permisos
        if($user->role == 'ROLE_USER' ||
           $user->role == 'ROLE_ADMIN' ||
           $user->role == 'ROLE_SUPER_ADMIN'){
            // Recoger los datos Json
            $json = $request->input('json', null);
            $params = json_decode($json);
            $params_array = json_decode($json, true);

            if(!empty($params_array)){
                // Validar los datos
                $validate = \Validator::make($params_array, [
                    'id_homologado'         => 'required|numeric',
                    'causa_homologacion'    => 'required'
                ]);
                if($validate->fails()){
                    $data = array(
                        'status' => 'error',
                        'code' => 400,
                        'message' => 'La validación de los datos enviados al servidor ha fallado.',
                        'errors' => $validate->errors()
                    );
                } else{
                    $params_array['estado'] = 0;
                    // Eliminar lo que no se va a actualizar
                    unset($params_array['id']);
                    unset($params_array['id_plan']);
                    unset($params_array['oportunidad_mejora']);
                    unset($params_array['hallazgo']);
                    unset($params_array['analisis']);
                    unset($params_array['riesgo']);
                    unset($params_array['mesa']);
                    unset($params_array['proceso']);
                    unset($params_array['nom_indicador']);
                    unset($params_array['for_indicador']);
                    unset($params_array['meta']);
                    unset($params_array['cum_pri_linea']);
                    unset($params_array['cum_seg_linea']);
                    unset($params_array['cum_ter_linea']);
                    unset($params_array['cum_indicador']);
                    unset($params_array['numerador']);
                    unset($params_array['denominador']);
                    unset($params_array['usuario_reg']);
                    unset($params_array['created_at']);
                    unset($params_array['updated_at']);

                    // Actualizar la oportunidad en la bd
                    $improvementOportunity = ImprovementOpportunities::where('id', $id)->update($params_array);
                    $data = array(
                        'status' => 'success',
                        'code' => 200,
                        'message' => 'La Oportunidad de Mejora '.$id.' ha sido homologada correctamente.',
                        'changes' => $params_array
                    );
                }
            } else{
                $data = array(
                    'status' => 'error',
                    'code' => 411,
                    'message' => 'La petición ha enviado datos de manera incorrecta al servidor.',
                    'sdadsa' => $params_array
                ); 
            }
        } else {
            $data = array(
                'status' => 'error',
                'code' => 401,
                'message' => 'Usted no tiene permisos para acceder a esta sección. Inicie sesión con un usuario "Super Administrador".'
            );
        }
        // Devolver respuesta
        return response()->json($data, $data['code']);
    }
    
    public function destroy($id, Request $request){
        // Conseguir el usuario del token
        $jwtAuth = new \JwtAuth();
        $token = $request->header('Authorization');
        $user = $jwtAuth->checkToken($token, true);
        
        // Comprobar si el usuario tiene los permisos
        if($user->role == 'ROLE_SUPER_ADMIN'){
            $improvementOpportunity = ImprovementOpportunities::where('id', $id)->first();
            if(!empty($improvementOpportunity)){
                $improvementOpportunity->delete();
                $data = array(
                    'status' => 'success',
                    'code' => 200,
                    'message' => 'La Oportunidad de Mejora '.$id.' se ha eliminado correctamente',
                    'improvementOpportunity' => $improvementOpportunity
                );
            } else{
                $data = array(
                    'status' => 'error',
                    'code' => 404,
                    'message' => 'No existe ninguna Oportunidad de Mejora con el id: '.$id
                ); 
            }
        } else{
            $data = array(
                'status' => 'error',
                'code' => 401,
                'message' => 'Usted no tiene permisos para acceder a esta sección. Inicie sesión con un usuario "Super Administrador".'
            );
        }
        // Devolver respuesta
        return response()->json($data, $data['code']);
    }
}
