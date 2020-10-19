<?php

use ePayco\Kernel\Exception\HtmlDebugHandler;
use ePayco\Kernel\Exception\HtmlHandler;
use ePayco\Kernel\Exception\HttpException;
use ePayco\Kernel\Exception\JsonHandler;
use ePayco\Kernel\Exception\RestHandler;
use ePayco\Kernel\Exception\TemporalException;
use ePayco\Kernel\Legacy\LegacySessionStorage;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\NativeFileSessionHandler;

/**
 * Clase principal que se encarga de administrar todo el microkernel via HTTP
 **/
class Kernel extends \KernelBase
{
    /**
     * @var Kernel Referencia Singleton
     */
    private static $instance = null;
    /**
     * @var string Ruta controlador por defecto
     */
    private $defaultPath = '';
    /**
     * @var boolean Establece si la peticion realizada es una peticion REST
     */
    private $isRest = false;
    /**
     * @var Request Instancia de la peticion recibida
     */
    private $request = null;

    /**
     * Devuelve la instancia Singleton
     *
     * @return Kernel
     */
    public static function getInstance() : \Kernel
    {
        if (self::$instance == null) {
            self::$instance = new \Kernel;
        }

        return self::$instance;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this['session.autostart'] = true;
    }


    /**
     * Establece que la peticion actual es una peticion REST
     */
    public function restMode()
    {
        $this->isRest = true;
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
            "Symfony\\Component\\HttpFoundation\\" => $vendorDir . '/symfony/http-foundation'
        ];

        parent::cargarClases(array_merge($extraNamespace, $clases));
    }

    /**
     * Define un controlador por defecto
     *
     * @param string $controlador El controlador por defecto, debe tener el formato `/Controlador/accion` y debe
     *                            ser un controlador válido.
     */
    public function setControladorDefecto(string $controlador)
    {
        if (!preg_match('/^\/\w+\/\w+$/', $controlador)) {
            throw new \Exception("Formato de controlador `$controlador` inválido");
        }

        $this->defaultPath = $controlador;
    }

    /**
     * Gestiona el error correspondiente
     *
     * @param Throwable $ex La excepcion a gestionar.
     */
    protected function doError(\Throwable $ex)
    {
        $codigoHttp = $ex instanceof HttpException? $ex->getStatusCode() : Response::HTTP_INTERNAL_SERVER_ERROR;

        $handler = $this->request->isXmlHttpRequest() ? new JsonHandler() : ($this->isRest ? new RestHandler() : new HtmlHandler());

        if ($handler instanceof HtmlHandler and $this['debug']) {
            $handler = new HtmlDebugHandler();
        }

        $handler->setErrorData(
            $ex->getCode(),
            $ex instanceof TemporalException? $ex->getErrorCode() : get_class($ex),
            $ex->getMessage(),
            $ex->getFile(),
            $ex->getLine(),
            $ex->getTraceAsString(),
            $codigoHttp
        );

        if ($ex instanceof HttpException) {
            $handler->setExtraOptions($ex->getOptions());
        }
        $handler->handle();

        if (isset($this['log'])) {
            $this['log']->error(sprintf(
                '[%s] %s (%s:%s)',
                $ex instanceof TemporalException? $ex->getErrorCode() : get_class($ex),
                $ex->getMessage(),
                $ex->getFile(),
                $ex->getLine()
            ), ['trace' => $ex->getTraceAsString()]);
        } else {
            error_log(sprintf(
                '[%s] %s (%s:%s) | BT: %s',
                $ex instanceof TemporalException? $ex->getErrorCode() : get_class($ex),
                $ex->getMessage(),
                $ex->getFile(),
                $ex->getLine(),
                $ex->getTraceAsString()
            ));
        }

        exit;
    }

    /**
     * Inicializa el kernel
     */
    public function doKernel()
    {
        if (!preg_match('/^\/\w+\/\w+$/', $this->defaultPath)) {
            throw new \Exception('No se ha definido un controlador por defecto');
        }
        $this->request = Request::createFromGlobals();

        if ($this['session.autostart']) {
            $storage = new LegacySessionStorage();
            $storage->setSaveHandler(new NativeFileSessionHandler());
            $session = new Session($storage);
            $session->start();
            $this->request->setSession($session);
        }


       // if ($this->isRest) {
         //   $this->prepareRest();
        //}

        $pathInfo = $this->request->getPathInfo();
        $pathInfo = $pathInfo == '/'? $this->defaultPath : $pathInfo;

        if (!preg_match('/^\/\w+\/\w+$/', $pathInfo)) {
            throw new HttpException(Response::HTTP_NOT_FOUND, "Ruta '$pathInfo' no encontrada");
        }

        list($controller, $action) = explode('/', trim($pathInfo, '/'));

        $response = null;
        $namespace = "\\ePayco\\{$this->getNombreProyecto()}\\Controlador\\{$controller}";
        if (class_exists($namespace) && method_exists($namespace, $action)) {
            $obj = new $namespace();
            $response = $obj->$action($this->request, $this);
            if (is_string($response)) {
                $response = new Response($response, Response::HTTP_OK);
            }
            if (!($response instanceof Response)) {
                throw new \Exception("La respuesta del controlador `$pathInfo` debe ser un objeto Response (o derivados) o un string");
            }
        } else {
            throw new HttpException(Response::HTTP_NOT_FOUND, "Ruta '$pathInfo' no encontrada");
        }

        if (!empty($response)) {
            $response->send();
        }
    }

    /**
     * Valida si la peticion actual devuelve el Http Method seleccionado
     *
     * @param array $methods Listado de metodos aprobados para el controlador.
     * @return void
     * @throws HttpException Si el metodo validado no es correcto, devuelve un error HTTP_METHOD_NOT_ALLOWED (405)
     */
    public function forzarMetodos(array $methods)
    {
        $methods = array_map('strtoupper', $methods);
        $reqMethod = $this->request->getMethod();

        if (!in_array($reqMethod, $methods)) {
            throw new HttpException(Response::HTTP_METHOD_NOT_ALLOWED, sprintf(
                'Esta intentando acceder por una petición HTTP no válida, sólo está permitido el acceso por `%s`',
                implode(', ', $methods)
            ));
        }
    }

    /**
     * Realiza unas prevalidaciones a una peticion REST
     */
    private function prepareRest()
    {
        $req = $this->request;
        if (!$req->headers->has('ePayco-Api-Key')) {
            throw new HttpException(Response::HTTP_UNAUTHORIZED,
                'Acceso no permitido, es necesario definir la API KEY a traves del header `ePayco-Api-Key`'
            );
        }
    }
}
