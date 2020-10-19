<?php

namespace ePayco\Kernel\Database;
use ePayco\Kernel\Database\ePayPdo;
use ePayco\Kernel\Exception\HttpException;

/**
 * Clase que administra las conexiones a las BD
 **/
class ConnectionManager
{
    /**
     * @var array Listado de las conexiones activas
     */
    private $cacheCnx = [];
    /**
     * @var array Informacion de conexion
     */
    private $infoCnx = null;

    /**
     * Constructor
     *
     * @param string $path Ruta donde se encuentra el archivo database.php.
     */
    public function __construct(string $path)
    {
        if (!file_exists($path)) {
            throw new HttpException("No se encuentra el archivo de conexiones `$path`");
        }

        $this->infoCnx = require $path;
    }

    /**
     * Devuelve una conexion específica
     *
     * @param string $nombre Nombre de la conexion a traer.
     * @return ePayPdo|null
     */
    public function getConexion(string $nombre)
    {
       $cnx =  new ePayPdo('localhost', 'usuarios','ePayco', 'ePayco');
       return $cnx;
    }
}
