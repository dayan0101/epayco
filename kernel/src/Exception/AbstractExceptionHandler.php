<?php

namespace ePayco\Kernel\Exception;

/**
 * Clase base para el manejp de errores y excepciones
 **/
abstract class AbstractExceptionHandler
{
    protected $codigo;
    protected $clase;
    protected $mensaje;
    protected $archivo;
    protected $linea;
    protected $backtrace;
    protected $codigoHttp;
    protected $opciones = [];

    /**
     * Establece la informacion de error a mostrar
     *
     * @param string $codigo El código de error.
     * @param string $clase Tipo/Clase de error.
     * @param string $mensaje El mensaje de error.
     * @param string $archivo Ubicacion del archivo donde se genera el error.
     * @param integer $linea Linea del archivo donde se genera el error.
     * @param string $backtrace Traza de la pila de llamado.
     * @param integer $codigoHttp Codigo http del error.
     */
    public function setErrorData(string $codigo, string $clase, string $mensaje, string $archivo, int $linea, string $backtrace, int $codigoHttp = 500)
    {
        $this->codigo     = $codigo;
        $this->clase      = $clase;
        $this->mensaje    = $mensaje;
        $this->archivo    = $archivo;
        $this->linea      = $linea;
        $this->backtrace  = $backtrace;
        $this->codigoHttp = $codigoHttp;
    }

    /**
     * Establece Opciones extra (si las hay)
     *
     * @param array $options Opciones extra.
     */
    public function setExtraOptions(array $options)
    {
        $this->opciones = $options;
    }

    /**
     * Procesa el error para mostrarlo al usuario
     */
    public abstract function handle();
}
