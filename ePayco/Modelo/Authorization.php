<?php

namespace ePayco\ePaycoPortal\Modelo;

use ePayco\Kernel\Exception\HttpException;
use Symfony\Component\HttpFoundation\Response;
use Firebase\JWT\JWT;

/**
 * Clase de generación (codificación de data usuario) y obtención del token (decodificacion del token)
 **/
class Authorization
{
    /**
     * @var string  $secret_key clave secreta para codificación y decodificación
     */
    private static $secret_key = 'eP4Yc0';
    /**
     * @var string  $encrypt tipo de cifrado
     */
    private static $encrypt = ['HS256'];

     /**
     * Genera Token para emplear como autenticación para el uso de los servicios
     * @param int $exp tiempo de expiración del token
     * @param json $data contiene documento, nombres, email y celular del usuario. 
     * @return string token generada con la data del usuario registrado
     */
    public function generarToken($exp, $data) 
    {
        $documento = $data['Documento'];
        $nombres = $data['Nombres'];
        $email = $data['Email'];
        $celular = $data['Celular'];
        $time = time();
        $token = array(
            'exp' => $time + $exp,
            'documento' => $documento,
            'nombres' => $nombres,
            'email'  => $email,
            'celular' => $celular            
        );
        return JWT::encode($token, self::$secret_key);        
    }
    /**
     * Genera Token para emplear como autenticación para el uso de los servicios
     * @param int $exp tiempo de expiración del token
     * @param json $data contiene documento, nombres, email y celular del usuario. 
     * @return string token generada con la data del usuario registrado
     */
    public function generarTokenFactura($id) 
    {
        $time = time();
        $token = array(
            'exp' => $time + 60*60*24*30,
            'id' => $id                   
        );
        return JWT::encode($token, self::$secret_key);        
    }
    /**
     * Genera Token para emplear como autenticación para el uso de los servicios
     *
     * @param string $token enviado en la solicitud.
     * @return json $data datos del usuario
     */
    public function validarToken($token) 
    {
        if(empty($token))
        {
            throw new HttpException(Response::HTTP_UNAUTHORIZED, 'token inválido');            
        }
        $decode = JWT::decode($token, self::$secret_key, self::$encrypt);
        $time = time();            
        return $decode;        
    }

    
}
