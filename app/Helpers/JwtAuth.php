<?php
namespace App\Helpers;

use Firebase\JWT\JWT;
use Illuminate\Support\Facades\DB;
use App\User;

class JwtAuth{
    
    public $key;
    
    public function __construct() {
        $this->key = 'clave para el aplicativo PlanesMejoramiento_2019';
    }
    
    public function signup($user_alias, $password, $getToken=null){
        // Buscar si existe el usuario con sus credenciales
        $user = User::where([
            'user_alias' => $user_alias,
            'password' => $password
        ])->first();
        
        // Comprobar si son correctas
        $signup = false;
        if(is_object($user)){
            $signup = true;
        }
        
        // Generar el token con los datos del usuario identificado
        if($signup){
            $token = array(
                'sub'           =>      $user->id,
                'user_alias'    =>      $user->user_alias,
                'name'          =>      $user->name,
                'surname'       =>      $user->surname,
                'role'          =>      $user->role,
                'iat'           =>      time(),
                'exp'           =>      time() + (2*24*60*60)
            );
            $jwt = JWT::encode($token, $this->key, 'HS256');
            $decoded = JWT::decode($jwt, $this->key, ['HS256']);
            
            if(is_null($getToken)){
                $data = $jwt;
            } else {
                $data = $decoded;
            }
        } else{
            $data = array(
                'status' => 'error',
                'code' => 401,
                'message' => 'El login ha fallado'
            );
        }
        // Devolver los datos decodificados o el token en función de un parametro
        return $data;
    }
    
    public function checkToken($token, $getIdentity=false){
        $auth = false;
        try{
            $jwtToken = str_replace('"', "", $token);
            $decoded = JWT::decode($jwtToken, $this->key, ['HS256']);
        } catch (\UnexpectedValueException $ex) {
            $auth = false;
        } catch (\DomainException $ex){
            $auth = false;
        }
        
        if(!empty($decoded) && is_object($decoded) && isset($decoded->sub)){
            $auth = true;
        } else{
            $auth = false;
        }
        
        if($getIdentity){
            return $decoded;
        } else{
            return $auth;
        }
    }
}


