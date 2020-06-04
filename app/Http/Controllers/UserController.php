<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Respose;
use App\User;

class UserController extends Controller
{    
    // FUNCIÓN PARA LOGUEARSE
    public function login(Request $request){
        $jwtAuth = new \JwtAuth();
        
        // Recibir los datos por post
        $json = $request->input('json', null);
        $params = json_decode($json);
        $params_array = json_decode($json, true);
        
        if(!empty($params_array)){
            //Validar los datos recibidos
            $validate = \Validator::make($params_array, [
                'user_alias' => 'required',
                'password' => 'required'
            ]);
            if($validate->fails()){
                $data = array(
                    'status' => 'error',
                    'code' => 400,
                    'message' => 'La validación de los datos ha fallado.',
                    'errors' => $validate->errors()
                );
            } else {
                // Cifrar la contraseña
                $password_hashed = hash('SHA256', $params->password);
                
                // Devolver el token o los datos
                if(!empty($params->gettoken)){
                    $data = $jwtAuth->signup($params->user_alias, $password_hashed, true);
                } else{
                    $data = $jwtAuth->signup($params->user_alias, $password_hashed);
                }
            }
        } else{
            $data = array(
                'status' => 'error',
                'code' => 411,
                'message' => 'Ha ingrasado los datos de manera incorrecta o incompletos'
            );
        }
        // Devolver la respuesta
        return response()->json($data, 200);
    }
    
    // FUNCIONES PARA MOSTRAR USUARIOS
    
    public function index(Request $request){
        // Obtener el token que viaja por el header
        $token = $request->header('Authorization');
        $jwtAuth = new \JwtAuth();
        $user = $jwtAuth->checkToken($token, true);
        
        // Comprobar el role del usuario
        if($user->role == "ROLE_SUPER_ADMIN"){
            //Obtener los usuarios en la BD
            $users = User::all();
            $data = array(
                'status' => 'success',
                'code' => 200,
                'users' => $users
            );
        } else{
            $data = array(
                'status' => 'error',
                'code' => 401,
                'message' => 'Usted no tiene permisos para realizar esta acción.'
            );
        }
        // Devolver la respuesta
        return response()->json($data, $data['code']);
    }
    
    public function show($id, Request $request){
        // Recoger el token del header
        $jwtAuth = new \JwtAuth();
        $token = $request->header('Authorization');
        $user = $jwtAuth->checkToken($token, true);
        
        // Comprobar si el usuario está autorizado
        if($user->role == 'ROLE_SUPER_ADMIN'){
            //Buscar el usuario por el id
            $user = User::find($id);
            
            if(is_object($user)){
                $data = array(
                    'status' => 'success',
                    'code' => 200,
                    'user' => $user
                );
            } else{
                $data = array(
                    'status' => 'error',
                    'code' => 404,
                    'message' => 'El usuario que está intentando buscar no existe'
                );
            }
        } else{
            $data = array(
                'status' => 'error',
                'code' => 401,
                'message' => 'Usted no tiene permisos para realizar esta acción.'
            );
        }
        // Devolver la respuesta
        return response()->json($data, $data['code']);
    }
    
    // FUNCIÓN PARA ALMACENAR NUEVOS USUARIOS A LA BASE DE DATOS
    public function store(Request $request){
        // Recoger el token de la cabecera
        $jwtAuth = new \JwtAuth();
        $token = $request->header('Authorization');
        $user = $jwtAuth->checkToken($token, true);
        
        // Comprobar si el usuario tiene permisos
        if($user->role == 'ROLE_SUPER_ADMIN'){
            // Recoger el json de la cabecera
            $json = $request->input('json', null);
            $params = json_decode($json);
            $params_array = json_decode($json, true);

            if(!empty($params_array)){
                //Validar los datos
                $validate = \Validator::make($params_array, [
                    'user_alias' => 'required|unique:users',
                    'name' => 'required|regex:/^[\pL\s\-]+$/u',
                    'surname' => 'required|regex:/^[\pL\s\-]+$/u',
                    'role' => 'required',
                    'password' => 'required'
                ]);
                if($validate->fails()){
                    $data = array(
                        'status' => 'error',
                        'code' => 400,
                        'message' => 'La validación de los datos ha fallado.',
                        'errors' => $validate->errors()
                    );
                } else{
                    // Cifrar la contraseña
                    $password_hashed = hash('SHA256', $params->password);

                    // Guardar el usuario nuevo
                    $user = new User();
                    $user->user_alias = $params_array['user_alias'];
                    $user->name = $params_array['name'];
                    $user->surname = $params_array['surname'];
                    $user->role = $params_array['role'];
                    $user->password = $password_hashed;

                    $user->save();

                    $data = array(
                        'status' => 'success',
                        'code' => 200,
                        'message' => 'El usuario se ha guardado correctamente.',
                        'user' => $user
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
                'message' => 'Usted no tiene permisos para realizar esta acción. Ingrese sesión con un usuario "Super Administrador".'
            );
        }
        //Devolver respuesta
        return response()->json($data, $data['code']);
    }
    
    //FUNCIÓN PARA ACTUALIZAR LA INFORMACIÓN DE LOS USUARIOS
    public function update($id, Request $request){
        //Recoger el token de la cabecera
        $jwtAuth = new \JwtAuth();
        $token = $request->header('Authorization');
        $user = $jwtAuth->checkToken($token, true);
        
        //Comprobar si el usuario tiene permisos
        if($user->role == 'ROLE_SUPER_ADMIN'){
            // Recoger el json por POST
            $json = $request->input('json', null);
            $params = json_decode($json);
            $params_array = json_decode($json, true);
            
            if(!empty($params_array)){
                // Validar los datos ingresados
                $validate = \Validator::make($params_array, [
                    'user_alias' => 'required|unique:users,user_alias,'.$id,
                    'name' => 'required|regex:/^[\pL\s\-]+$/u',
                    'surname' => 'required|regex:/^[\pL\s\-]+$/u',
                    'role' => 'required'
                ]);
                if($validate->fails()){
                    $data = array(
                        'status' => 'error',
                        'code' => 400,
                        'message' => 'La validación de los datos ha fallado.',
                        'errors' => $validate->errors()
                    );                    
                } else {
                    // Retirar el contenido que no se desea actualizar
                    unset($params_array['id']);
                    unset($params_array['password']);
                    unset($params_array['created_at']);
                    unset($params_array['updated_at']);
                    
                    // Actualizar los datos en la BD
                    $user = User::where('id', $id)->update($params_array);
                    // Devolver array con resultado
                    if($user != 0){
                        $data = array(
                            'status' => 'success',
                            'code' => 200,
                            'message' => 'El usuario '.$params->user_alias.' se ha actualizado correctamente.',
                            'changes' => $params_array
                        );
                    } else {
                        $data = array(
                            'status' => 'error',
                            'code' => 404,
                            'message' => 'No se ha podido actualizar el usuario: '.$params->user_alias
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
                'message' => 'Usted no tiene permisos para realizar esta acción. Ingrese sesión con un usuario "Super Administrador".'
            );
        }
        //Devolver respuesta
        return response()->json($data, $data['code']);
    }
    
    public function updatePassword($id, Request $request){
        // Recoger el token de la cabecera
        $jwtAuth = new \JwtAuth();
        $token = $request->header('Authorization');
        $user = $jwtAuth->checkToken($token, true);
        
        // Validar si el usuario tiene acceso a esta sección
        if($user->role == 'ROLE_SUPER_ADMIN'){
            // Recoger los datos por post
            $json = $request->input('json', null);
            $params = json_decode($json);
            $params_array = json_decode($json, true);
            
            if(!empty($params_array)){
                // Validar la información recibida
                $validate = \Validator::make($params_array, [
                    'password' => 'required'
                ]);
                if($validate->fails()){
                    $data = array(
                        'status' => 'error',
                        'code' => 400,
                        'message' => 'La validación de los datos ha fallado.',
                        'errors' => $validate->errors()
                    );                      
                } else{
                    // Elminar el contenido que no se desea actualizar
                    unset($params_array['id']);
                    unset($params_array['user_alias']);
                    unset($params_array['name']);
                    unset($params_array['surname']);
                    unset($params_array['role']);
                    unset($params_array['created_at']);
                    unset($params_array['updated_at']);
                    
                    // Cifrar la contraseña
                    $params_array['password'] = hash('SHA256', $params_array['password']);                    
                    
                    // Actualizar el usuario en la base de datos
                    $user = User::where('id', $id)->update($params_array);
                    // Devolver array con resultado
                    if($user != 0){
                        $data = array(
                            'status' => 'success',
                            'code' => 200,
                            'message' => 'La contraseña del Usuario: '.$params->user_alias.' se ha actualizado correctamente.',
                            'changes' => $params_array
                        );
                    } else {
                        $data = array(
                            'status' => 'error',
                            'code' => 404,
                            'message' => 'No se ha podido actualizar la contraseña del Usuario: '.$params->user_alias
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
                'message' => 'Usted no tiene permisos para realizar esta acción. Ingrese sesión con un usuario "Super Administrador".'
            );
        }
        // Devolver respuesta
        return response()->json($data, $data['code']);
    }
    
    //FUNCIÓN PARA ELIMINAR LOS USUARIOS
    public function destroy($id, Request $request){
        // Recoger el token de la cabecera
        $jwtAuth = new \JwtAuth();
        $token = $request->header('Authorization');
        $user = $jwtAuth->checkToken($token, true);
        
        // Comprobar si el usuario tiene permisos
        if($user->role == 'ROLE_SUPER_ADMIN'){
            $user = User::where('id', $id)->first();
            if(!empty($user)){
                $user->delete();

                $data = array(
                    'status' => 'success',
                    'code' => 200,
                    'message' => 'El usuario se ha eliminado correctamente',
                    'post' => $user
                );
            } else{
                $data = array(
                    'status' => 'error',
                    'code' => 404,
                    'message' => 'No existe ningun usuario con el id: '.$id
                ); 
            }
        } else{
            $data = array(
                'status' => 'error',
                'code' => 401,
                'message' => 'Usted no tiene permisos para realizar esta acción. Ingrese sesión con un usuario "Super Administrador".'
            );
        }
        // Devolver la respuesta
        return response()->json($data, $data['code']);
    }
}
