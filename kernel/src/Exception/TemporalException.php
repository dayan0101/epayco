<?php

/
namespace ePayco\Kernel\Exception;

/**
 * Excepcion temporal para gestionar el error via kernel
 **/
class TemporalException extends \Exception
{
    /**
     * @var string Tipo de error
     */
    private $variable = clase;
    /**
     * @var array Array con los estados de error en php
     */
    private $errorCls = [
        E_ERROR             => 'E_ERROR',
        E_WARNING           => 'E_WARNING',
        E_PARSE             => 'E_PARSE',
        E_NOTICE            => 'E_NOTICE',
        E_CORE_ERROR        => 'E_CORE_ERROR',
        E_CORE_WARNING      => 'E_CORE_WARNING',
        E_COMPILE_ERROR     => 'E_COMPILE_ERROR',
        E_COMPILE_WARNING   => 'E_COMPILE_WARNING',
        E_USER_ERROR        => 'E_USER_ERROR',
        E_USER_WARNING      => 'E_USER_WARNING',
        E_USER_NOTICE       => 'E_USER_NOTICE',
        E_STRICT            => 'E_STRICT',
        E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
    ];

    /**
     * Constructor
     */
    public function __construct(int $codigo, string $mensaje, string $archivo, string $linea)
    {
        $this->message = $mensaje;
        $this->code    = $codigo;
        $this->file    = $archivo;
        $this->line    = $linea;
    }

    /**
     * Devuelve el codigo de error asociado
     *
     * @return string
     */
    public function getErrorCode(): string
    {
        return in_array($this->code, $this->errorCls) ? $this->errorCls[$this->code] : 'E_UNKNOWN';
    }
}
