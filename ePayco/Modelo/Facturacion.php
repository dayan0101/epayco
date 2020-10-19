<?php

namespace ePayco\ePaycoPortal\Modelo;

use ePayco\Kernel\Exception\HttpException;
use Symfony\Component\HttpFoundation\Response;
use ePayco\Kernel\Database\ePayPdo;

/**
 * Clase de conexion y listado de facturas, creación y actualización y borrado de facturas
 **/
class Facturacion
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
     * Obtener facturas del usuario registrado
     * @param json $data contiene documento, nombres, email y celular del usuario. 
     * @return object con el resultado de las facturas;     
     */
    public function obtenerFacturas($data) 
    {
        $documento = $data['documento'];        
        return $this->cnx->query(
            'SELECT fc.id, fc.correo_electronico, fc.nombres, fc.estado_factura, fc.valor FROM facturas_cabecera fc 
                INNER JOIN registro_usuarios ru 
                ON ru.id = fc.id_usuario 
            WHERE ru.documento = ?', [$documento]);        
    }

    /**
     * Genera el detalle de una factura
     * @param int $id identificación de la factura
     * @return array con el resultado del detalle de la factura
     */
     public function detalleFacturas($id) {
        $resultado = $this->cnx->query('SELECT * FROM facturas_cuerpo WHERE id_factura = ?', [$id]);
        $data = array(); 
        foreach ($resultado as $row) {
            $data[] = $row;
        } 
        return $data;
     }
     /**
     * Crear factura de cobro a un cliente pagador
     * @param json $data contiene data de cabecera y cuerpo de la factura 
     */
     public function crearFacturas($dataUser, $data) {
        $documento        = $dataUser->documento;
        $emailCliente     = $data->email;
        $nombres          = $data->nombre_cliente;
        $tipoDocumento    = $data->tipo_documento;
        $documentoCliente = $data->documento;
        $telefono         = $data->telefono_cliente;
        $valor            = $data->monto;
        $id =  $this->cnx->getSingleResult('SELECT MAX(id) FROM facturas_cabecera') + 1;
        $this->cnx->exec(
            'INSERT INTO facturas_cabecera (SELECT $id, id_usuario, $emailCliente, $nombres,  $tipoDocumento, $documentoCliente, $telefono, $direccion, 'creada', $valor FROM registro_usuarios 
            WHERE documento = ?)',
            [$documento]
        ); 
        $nombre = ;
        $this->cnx->exec(
            'INSERT INTO facturas_cuerpo (id_factura, nombre, descripcion, pais, moneda,monto, tax_base, tax, lang, email) VALUES (?,?,?,?,?,?,?,?,?) ',
                [$id, $data->nombre, $data->descripcion, $data->pais, $data->moneda, $data->monto, $data->tax_base, $data->tax, $data->lang, $data->email_cliente]
        );  
        return $id;
     }
      
}
