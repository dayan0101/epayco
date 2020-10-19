<?php

use \League\CLImate\CLImate;

/**
 * Clase principal que se encarga de administrar todo el microkernel via consola de comandos
 **/
class KernelCli extends \KernelBase
{
    /**
     * @var KernelCli Referencia Singleton
     */
    private static $instance = null;
    /**
     * @var string Titulo del script
     */
    private $titulo = null;
    /**
     * @var array Definicion de los argumentos a pasar
     */
    private $args = [];

    /**
     * Devuelve la instancia Singleton
     *
     * @return KernelCli
     */
    public static function getInstance() : \KernelCli
    {
        if (self::$instance == null) {
            self::$instance = new \KernelCli;
        }

        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct()
    {
        // Validacion entorno de ejecucion.
        if (strpos(PHP_SAPI, 'cli') !== 0) {
            http_response_code(409);
            die('<!DOCTYPE html> <html lang="en"><head><meta charset="UTF-8"><title></title></head><body><h1 ' .
                'style="color:#e51c23">ACCESO PROHIBIDO</h1></body></html>');
        }
        parent::__construct();
        $this['session.autostart'] = true;
        $this->args = [
            'help' => [
                'prefix' => 'h',
                'longPrefix' => 'help',
                'description' => 'Muestra la ayuda del script',
                'noValue' => true,
            ]
        ];
        $this['cli'] = null;
    }

    /**
     * Establece el titulo del script
     */
    public function titulo(string $titulo)
    {
        $this->titulo = $titulo;
    }

    /**
     * Establece los argumentos que recibe el script
     *
     * @param array $args Los argumentos del script.
     */
    public function setArgs(array $args)
    {
        $this->args = array_merge($this->args, $args);
    }

    /**
     * Carga las clases base del microkernel
     *
     * @param array $clases Listado asociativo de clases a cargar (namespace => Ubicacion directorio).
     */
    public function cargarClases(array $clases = array())
    {
        $vendorDir = dirname(__DIR__) . '/vendor';
        $extraNamespace = [
            "Seld\\CliPrompt\\" => $vendorDir . '/cli-prompt/src',
            "League\\CLImate\\" => $vendorDir . '/league/climate/src',
            "Firebase\\JWT\\" => $vendorDir . '/firebase/php-jwt/src',
        ];

        parent::cargarClases(array_merge($extraNamespace, $clases));

        if (empty($this['cli'])) {
            $this['cli'] = new CLImate;
        }
    }

    /**
     * Gestiona el error correspondiente
     *
     * @param Throwable $ex La excepcion a gestionar.
     */
    protected function doError(\Throwable $ex)
    {
        if (isset($this['log']) and is_string($this['log.path'])) {
            $this['log']->error(sprintf(
                '[%s] %s (%s:%s)',
                $ex instanceof TemporalException? $ex->getErrorCode() : get_class($ex),
                $ex->getMessage(),
                $ex->getFile(),
                $ex->getLine()
            ), ['trace' => $ex->getTraceAsString()]);
        }
        echo sprintf(
            "\033[31m[%s] %s\033[0\n",
            $ex instanceof TemporalException? $ex->getErrorCode() : get_class($ex),
            $ex->getMessage()
        );
        if ($this['debug']) {
            echo sprintf(
                "\033[31m--------------------\n(%s:%s) | BT:\n%s\033[0\n",
                $ex->getFile(),
                $ex->getLine(),
                $ex->getTraceAsString()
            );
        }
    }

    /**
     * Inicializa el kernel
     */
    public function doKernel()
    {
        if (!empty($this->titulo)) {
            $this['cli']->description($this->titulo);
        }
        if (!empty($this->args)) {
            $this['cli']->arguments->add($this->args);
            $this['cli']->arguments->parse();
        } else {
            if ($this['debug']) {
                $this['cli']->yellow('!! Script sin parámetros');
            }
        }

        if ($this['cli']->arguments->defined('help')) {
            $this['cli']->usage();
            exit(0);
        }
    }
}

/**
 * Clase alias
 */
class Kernel extends \KernelCli
{
}
