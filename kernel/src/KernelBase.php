<?php
use ePayco\Kernel\Database\ConnectionManager;
use ePayco\Kernel\Exception\TemporalException;
use League\Plates\Engine;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Valitron\Validator;

/**
 * Clase Base para los kernels
 **/
abstract class KernelBase implements \ArrayAccess
{
    protected $values = array();
    /**
     * @var string Nombre del proyecto
     */
    private $nombreProyecto = null;
    /**
     * @var array Directorio de plantillas
     */
    private $dirPlantillas = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        
        $this->values = array();
        $this['log.level'] = 400;
        $this['log.path'] = null;
        $this['debug'] = false;
        $this['cnx.path'] = 'ePayco\Kernel\Database\database.php';
        $this['mailer.smtp.server'] = '192.168.0.5';
        $this['mailer.smtp.port'] = 1025;
    }

    /**
     * Sets a parameter or an object.
     *
     * Objects must be defined as Closures.
     *
     * Allowing any PHP callable leads to difficult to debug problems
     * as function names (strings) are callable (creating a function with
     * the same a name as an existing parameter would break your container).
     *
     * @param string $id    The unique identifier for the parameter or object
     * @param mixed  $value The value of the parameter or a closure to defined an object
     */
    public function offsetSet($id, $value)
    {
        $this->values[$id] = $value;
    }

    /**
     * Gets a parameter or an object.
     *
     * @param string $id The unique identifier for the parameter or object
     *
     * @return mixed The value of the parameter or an object
     *
     * @throws InvalidArgumentException if the identifier is not defined
     */
    public function offsetGet($id)
    {
        if (!array_key_exists($id, $this->values)) {
            throw new InvalidArgumentException(sprintf('Identifier "%s" is not defined.', $id));
        }

        $isFactory = is_object($this->values[$id]) && method_exists($this->values[$id], '__invoke');

        return $isFactory ? $this->values[$id]($this) : $this->values[$id];
    }

    /**
     * Checks if a parameter or an object is set.
     *
     * @param string $id The unique identifier for the parameter or object
     *
     * @return Boolean
     */
    public function offsetExists($id) : bool
    {
        return array_key_exists($id, $this->values);
    }

    /**
     * Unsets a parameter or an object.
     *
     * @param string $id The unique identifier for the parameter or object
     */
    public function offsetUnset($id)
    {
        unset($this->values[$id]);
    }

    /**
     * Returns a closure that stores the result of the given service definition
     * for uniqueness in the scope of this instance of Class
     *
     * @param object $callable A service definition to wrap for uniqueness
     *
     * @return Closure The wrapped closure
     */
    public static function preload(\Closure $callable) : \Closure
    {
        if (!is_object($callable) || !method_exists($callable, '__invoke')) {
            throw new InvalidArgumentException('Service definition is not a Closure or invokable object.');
        }

        return function ($c) use ($callable) {
            static $object;

            if (null === $object) {
                $object = $callable($c);
            }

            return $object;
        };
    }

    /**
     * Carga las clases base del microkernel
     *
     * @param array $clases Listado asociativo de clases a cargar (namespace => Ubicacion directorio).
     */
    public function cargarClases(array $clases = array())
    {
        $cl = new \Composer\Autoload\ClassLoader();
        $vendorDir = dirname(__DIR__) . '/vendor';

        $cl->addPsr4("Psr\\Log\\", $vendorDir . '/psr/log/Psr/Log');
        $cl->addPsr4("Monolog\\", $vendorDir . '/monolog/src/Monolog');
        $cl->addPsr4("League\\Plates\\", $vendorDir . '/league/plates/src');
        $cl->addPsr4("Envms\\FluentPDO\\", $vendorDir . '/fluentpdo/src');
        $cl->addPsr4("Doctrine\\Common\\Lexer\\", $vendorDir . '/doctrine/lexer/lib/Doctrine/Common/Lexer');
        $cl->addPsr4("Egulias\\EmailValidator\\", $vendorDir . '/email-validator/EmailValidator');
        $cl->addPsr4("Valitron\\", $vendorDir . '/valitron/src/Valitron');
        $cl->addPsr4("Firebase\\JWT\\", $vendorDir . '/firebase/php-jwt/src');
        $cl->addPsr4("ePayco\\Kernel\\", __DIR__);

        foreach ($clases as $namespace => $ruta) {
            $cl->addPsr4($namespace, $ruta);
        }

        $cl->register();

        $this['classLoader'] = $cl;
    }

    /**
     * Establece el nombre del proyecto, este nombre es el identificador base del namespace, ejemplo Si el
     * namespace es `\ePayco\App\Controlador` el nombre del proyecto debe ser `App`
     *
     * @param string $nombreProyecto El nomnre del proyecto a asignar.
     */
    public function setNombreProyecto(string $nombreProyecto)
    {
        $this->nombreProyecto = $nombreProyecto;
    }

    /**
     * Devuelve el Nombre del proyecto
     *
     * @return string
     */
    public function getNombreProyecto()
    {
        return $this->nombreProyecto;
    }

    /**
     * Agrega una carpeta de plantillas agrupadas por un alias
     *
     * @param string $nombre Alias de la carpeta a incluir.
     * @param string $ubicacion Ruta absoluta donde se encuentran las plantillas.
     */
    public function agregarDirPlantilla($nombre, $ubicacion)
    {
        $this->dirPlantillas[$nombre] = $ubicacion;
    }

    /**
     * Carga todas las clases necesarias para el framework
     */
    protected function cargarFuncionalidades()
    {
        $this['log'] = self::preload(function ($krn) {
            $log = new Logger($krn->nombreProyecto);
            $log->pushHandler(new ErrorLogHandler(ErrorLogHandler::OPERATING_SYSTEM, Logger::ERROR, true, true));
            if (is_string($krn['log.path'])) {
                $log->pushHandler(new StreamHandler($krn['log.path'], $krn['log.level']));
            }

            return $log;
        });

        $this['template'] = self::preload(function ($krn) {
            $e = new Engine();
            foreach ($krn->dirPlantillas as $alias => $ubicacion) {
                $e->addFolder($alias, $ubicacion);
            }

            return $e;
        });

        $this['db'] = self::preload(function ($krn) {
            return new ConnectionManager($krn['cnx.path']);
        });

        $this['mailer'] = self::preload(function ($knr) {
            include dirname(__DIR__) . '/vendor/swiftmailer/lib/swift_required.php';
            $transport = new \Swift_SmtpTransport($knr['mailer.smtp.server'], $knr['mailer.smtp.port']);
            if (isset($knr['mailer.smtp.user']) and isset($knr['mailer.smtp.password'])) {
                $transport->setUsername($knr['mailer.smtp.user']);
                $transport->setPassword($knr['mailer.smtp.password']);
            }

            return new \Swift_Mailer($transport);
        });


        Validator::lang('es');
    }

    /**
     * Procesa y analiza la peticion PHP, punto de inicio del kernel
     */
    public function iniciar()
    {
        $me = $this;
        set_exception_handler(function (\Throwable $ex) use ($me) {
            $me->doError($ex);
        });

        set_error_handler(function ($errno, $errstr, $errfile, $errline) use ($me) {
            $tmp = new TemporalException($errno, $errstr, $errfile, $errline);
            $me->doError($tmp);
        });

        $this->cargarFuncionalidades();
        $this->doKernel();
    }

    /**
     * Gestiona el error correspondiente
     *
     * @param Throwable $ex La excepcion a gestionar.
     */
    abstract protected function doError(\Throwable $ex);

    /**
     * Inicializa el kernel
     */
    abstract protected function doKernel();
}
