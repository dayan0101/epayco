<?php

namespace ePayco\ePaycoPortal\Modelo;

use ePayco\Kernel\Exception\HttpException;
use Symfony\Component\HttpFoundation\Response;
use ePayco\Kernel\Database\ePayPdo;

/**
 * Clase de conexion creación 
 **/
class Usuario
{
    /**
     * @var ePayPdo Conexion a la BD usuarios
     */
    private $cnx = null;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->cnx = new ePayPdo('localhost', 'usuarios','ePayco', 'ePayco');
    }
    /**
     * Registro usuario en la base de datos
     *
     * @param json $data contiene documento, nombres, email y celular del usuario.      
     */
    public function registrarUsuario($data) 
    {
        $documento = $data['Documento'];
        $nombres = $data['Nombres'];
        $email = $data['Email'];
        $celular = $data['Celular'];
        if ($this->cnx->getSingleResult('SELECT COUNT(id) FROM registro_usuarios WHERE documento = ? OR email = ?', [$documento, $email]) > 0) {
            throw new HttpException(Response::HTTP_NOT_FOUND, "EL usuario ya existe en el sistema",
                    ['message' => true, 'extra' => "EL usuario ya existe en el sistema"]
                );
        }
        $this->cnx->exec(
            'INSERT INTO registro_usuarios (documento, nombres,email,celular) VALUES (?, ?, ?,?)',
            [$documento, $nombres,$email,$celular]
        );
    }
    /**
     * Creación de contraseña
     *
     * @param json $data contiene documento, nombres, email y celular del usuario.   
     * @param string $password contraseña para validar logueo del usuario   
     */
    public function crearPass($data, $password);
    {
        $documento = $data['documento'];
        $nombres = $data['nombres'];
        $email = $data['email'];
        $celular = $data['celular'];        
        $this->cnx->exec(
            'UPDATE registro_usuarios SET password = ? WHERE documento = ? AND email = ?',
            [$password, $documento, $email]
        );
    }

    /**
     * Creación de contraseña
     *
     * @param json $data contiene email y contraseña del usuario.      
     */
    public function validarPass($data) {
        $email = $data['email'];
        $password = $data['passwprd'];
        return $this->cnx->getSingleResult('SELECT COUNT(id) FROM registro_usuarios WHERE email = ? AND password = ?', [$$email, $password]) > 0;
    }
}
