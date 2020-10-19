<?php

namespace ePayco\Kernel\Net;

include_once dirname(dirname(__DIR__)) . '/vendor/composer/ClassLoader.php';

use Tqdev\PhpCrudApi\Api;
//use Tqdev\PhpCrudApi\Config;
use Tqdev\PhpCrudApi\RequestFactory;
use Tqdev\PhpCrudApi\ResponseUtils;

/**
 * Clase que genera un controlador REST enerico para acceder a una base de datos, se apoya de la libreria
 * php-crud-api no necesita de la instancia Kernel ya que el tiene su propia gestion del CRUD
 *
 * @see https://treeql.org/
 * @see https://github.com/mevdschee/php-crud-api
 **/
class RestCrud
{
    // Middlewares
    const MIDDLEWARE_FIREWALL      = "firewall";
    const MIDDLEWARE_CORS          = "cors";
    const MIDDLEWARE_XSRF          = "xsrf";
    const MIDDLEWARE_AJAX_ONLY     = "ajaxOnly";
    const MIDDLEWARE_JWT_AUTH      = "jwtAuth";
    const MIDDLEWARE_BASIC_AUTH    = "basicAuth";
    const MIDDLEWARE_AUTHORIZATION = "authorization";
    const MIDDLEWARE_VALIDATION    = "validation";
    const MIDDLEWARE_IP_ADDRESS    = "ipAddress";
    const MIDDLEWARE_SANITATION    = "sanitation";
    const MIDDLEWARE_MULTI_TENANCY = "multiTenancy";
    const MIDDLEWARE_PAGE_LIMITS   = "pageLimits";
    const MIDDLEWARE_JOIN_LIMITS   = "joinLimits";
    const MIDDLEWARE_CUSTOMIZATION = "customization";

    // Config Middlewares
    const FIREWALL_REVERSE_PROXY        = "firewall.reverseProxy";
    const FIREWALL_ALLOWED_IP_ADDRESSES = "firewall.allowedIpAddresses";
    const CORS_ALLOWED_ORIGINS          = "cors.allowedOrigins";
    const CORS_ALLOW_HEADERS            = "cors.allowHeaders";
    const CORS_ALLOW_METHODS            = "cors.allowMethods";
    const CORS_ALLOW_CREDENTIALS        = "cors.allowCredentials";
    const CORS_EXPOSE_HEADERS           = "cors.exposeHeaders";
    const CORS_MAX_AGE                  = "cors.maxAge";
    const XSRF_EXCLUDE_METHODS          = "xsrf.excludeMethods";
    const XSRF_COOKIE_NAME              = "xsrf.cookieName";
    const XSRF_HEADER_NAME              = "xsrf.headerName";
    const AJAX_ONLY_EXCLUDE_METHODS     = "ajaxOnly.excludeMethods";
    const AJAX_ONLY_HEADER_NAME         = "ajaxOnly.headerName";
    const AJAX_ONLY_HEADER_VALUE        = "ajaxOnly.headerValue";
    const JWT_AUTH_MODE                 = "jwtAuth.mode";
    const JWT_AUTH_HEADER               = "jwtAuth.header";
    const JWT_AUTH_LEEWAY               = "jwtAuth.leeway";
    const JWT_AUTH_TTL                  = "jwtAuth.ttl";
    const JWT_AUTH_SECRET               = "jwtAuth.secret";
    const JWT_AUTH_ALGORITHMS           = "jwtAuth.algorithms";
    const JWT_AUTH_AUDIENCES            = "jwtAuth.audiences";
    const JWT_AUTH_ISSUERS              = "jwtAuth.issuers";
    const BASIC_AUTH_MODE               = "basicAuth.mode";
    const BASIC_AUTH_REALM              = "basicAuth.realm";
    const BASIC_AUTH_PASSWORD_FILE      = "basicAuth.passwordFile";
    const AUTHORIZATION_TABLE_HANDLER   = "authorization.tableHandler";
    const AUTHORIZATION_COLUMN_HANDLER  = "authorization.columnHandler";
    const AUTHORIZATION_RECORD_HANDLER  = "authorization.recordHandler";
    const VALIDATION_HANDLER            = "validation.handler";
    const IP_ADDRESS_TABLES             = "ipAddress.tables";
    const IP_ADDRESS_COLUMNS            = "ipAddress.columns";
    const SANITATION_HANDLER            = "sanitation.handler";
    const MULTI_TENANCY_HANDLER         = "multiTenancy.handler";
    const PAGE_LIMITS_PAGES             = "pageLimits.pages";
    const PAGE_LIMITS_RECORDS           = "pageLimits.records";
    const JOIN_LIMITS_DEPTH             = "joinLimits.depth";
    const JOIN_LIMITS_TABLES            = "joinLimits.tables";
    const JOIN_LIMITS_RECORDS           = "joinLimits.records";
    const CUSTOMIZATION_BEFORE_HANDLER  = "customization.beforeHandler";
    const CUSTOMIZATION_AFTER_HANDLER   = "customization.afterHandler";


    /**
     * @var ClassLoader Cargador dinamico de clases
     */
    private $classLoader = null;
    /**
     * @var array Parametros de configuracion del rest crud.
     */
    private $restCfg = [
        'driver' => 'mysql',
        'controllers' => 'records'
    ];


    /**
     * Carga las clases necesarias para iniciar
     *
     * @param array $clases Listado asociativo de clases a cargar (namespace => Ubicacion directorio).
     */
    public function cargarClases(array $clases = array())
    {
        $cl = new \Composer\Autoload\ClassLoader();
        $vendorDir = dirname(dirname(__DIR__)) . '/vendor';
        $cl->addPsr4("Psr\\Log\\", $vendorDir . '/psr/log/Psr/Log');
        $cl->addPsr4("Monolog\\", $vendorDir . '/monolog/src/Monolog');
        $cl->addPsr4("Envms\\FluentPDO\\", $vendorDir . '/fluentpdo/src');
        $cl->addPsr4('Tqdev\\PhpCrudApi\\' , $vendorDir . '/crud-api/Tqdev/PhpCrudApi');
        $cl->addPsr4('Psr\\Http\\Server\\' , [$vendorDir . '/psr/http-server-handler/src', $vendorDir . '/psr/http-server-middleware/src']);
        $cl->addPsr4('Psr\\Http\\Message\\', [$vendorDir . '/psr/http-message/src', $vendorDir . '/psr/http-factory/src']);
        $cl->addPsr4('Nyholm\\Psr7\\'      , $vendorDir . '/nyholm/psr7/src');
        $cl->addPsr4('Nyholm\\Psr7Server\\', $vendorDir . '/nyholm/psr7-server/src');
        $cl->addPsr4('Http\\Message\\'     , $vendorDir . '/php-http/message-factory/src');
        $cl->addPsr4("ePayco\\Kernel\\", dirname(__DIR__));


        foreach ($clases as $namespace => $ruta) {
            $cl->addPsr4($namespace, $ruta);
        }

        $this->classLoader = $cl;
        $this->classLoader->register();

        return $this;
    }

    /**
     * Establece la Base de datos a usar
     *
     * @param string $database Nombre de la base de datos a usar.
     * @return $this
     */
    public function setDatabase(string $database)
    {
        $conexiones = include '/var/www/AECSOFT/database.php';
        if (isset($conexiones[$database])) {
            $this->restCfg = array_merge($this->restCfg, $conexiones[$database]);
            $this->restCfg['address'] = $this->restCfg['host'];
            unset($this->restCfg['host']);
        } else {
            throw new \InvalidArgumentException("La conexion `$database` no esta definida en database.php");
        }

        return $this;
    }

    /**
     * Establece el driver de base de datos
     *
     * @param string $Driver El nombre del driver (mysql, postgres)
     *
     * @return $this
     */
    public function setDriver(string $driver)
    {
        if ($driver == 'mysql' or $driver == 'postgres') {
            $this->restCfg['driver'] = $driver;
        } else {
            throw new \InvalidArgumentException("Driver `$driver` no soportado");
        }

        return $this;
    }

    /**
     * Agrega un nuevo middleware
     *
     * @param string $mdl Nombre del middleware (constantes `RestCrud::MIDDLEWARE_...`).
     * @return $this
     */
    public function addMiddleware(string $mdl)
    {
        $middlewares = [self::MIDDLEWARE_FIREWALL, self::MIDDLEWARE_CORS, self::MIDDLEWARE_XSRF,
            self::MIDDLEWARE_AJAX_ONLY, self::MIDDLEWARE_JWT_AUTH, self::MIDDLEWARE_BASIC_AUTH,
            self::MIDDLEWARE_AUTHORIZATION, self::MIDDLEWARE_VALIDATION, self::MIDDLEWARE_IP_ADDRESS,
            self::MIDDLEWARE_SANITATION, self::MIDDLEWARE_MULTI_TENANCY, self::MIDDLEWARE_PAGE_LIMITS,
            self::MIDDLEWARE_JOIN_LIMITS, self::MIDDLEWARE_CUSTOMIZATION];

        if (in_array($mdl, $middlewares)) {
            $this->restCfg['middlewares'][] = $mdl;
        } else {
            throw new \InvalidArgumentException("Middleware `$mdl` no es válido");
        }

        return $this;
    }

    /**
     * Configura un parametro de un middleware, el middlewaree debe estar ya cargado con `addMiddleware()`. Los
     * parametros son:
     *
     *    - "firewall.reverseProxy":       Set to "true" when a reverse proxy is used ("")
     *    - "firewall.allowedIpAddresses": List of IP addresses that are allowed to connect ("")
     *    - "cors.allowedOrigins":         The origins allowed in the CORS headers ("*")
     *    - "cors.allowHeaders":           The headers allowed in the CORS request ("Content-Type, X-XSRF-TOKEN")
     *    - "cors.allowMethods":           The methods allowed in the CORS request ("OPTIONS, GET, PUT, POST, DELETE, PATCH")
     *    - "cors.allowCredentials":       To allow credentials in the CORS request ("true")
     *    - "cors.exposeHeaders":          Whitelist headers that browsers are allowed to access ("")
     *    - "cors.maxAge":                 The time that the CORS grant is valid in seconds ("1728000")
     *    - "xsrf.excludeMethods":         The methods that do not require XSRF protection ("OPTIONS,GET")
     *    - "xsrf.cookieName":             The name of the XSRF protection cookie ("XSRF-TOKEN")
     *    - "xsrf.headerName":             The name of the XSRF protection header ("X-XSRF-TOKEN")
     *    - "ajaxOnly.excludeMethods":     The methods that do not require AJAX ("OPTIONS,GET")
     *    - "ajaxOnly.headerName":         The name of the required header ("X-Requested-With")
     *    - "ajaxOnly.headerValue":        The value of the required header ("XMLHttpRequest")
     *    - "jwtAuth.mode":                Set to "optional" if you want to allow anonymous access ("required")
     *    - "jwtAuth.header":              Name of the header containing the JWT token ("X-Authorization")
     *    - "jwtAuth.leeway":              The acceptable number of seconds of clock skew ("5")
     *    - "jwtAuth.ttl":                 The number of seconds the token is valid ("30")
     *    - "jwtAuth.secret":              The shared secret used to sign the JWT token with ("")
     *    - "jwtAuth.algorithms":          The algorithms that are allowed, empty means 'all' ("")
     *    - "jwtAuth.audiences":           The audiences that are allowed, empty means 'all' ("")
     *    - "jwtAuth.issuers":             The issuers that are allowed, empty means 'all' ("")
     *    - "basicAuth.mode":              Set to "optional" if you want to allow anonymous access ("required")
     *    - "basicAuth.realm":             Text to prompt when showing login ("Username and password required")
     *    - "basicAuth.passwordFile":      The file to read for username/password combinations (".htpasswd")
     *    - "authorization.tableHandler":  Handler to implement table authorization rules ("")
     *    - "authorization.columnHandler": Handler to implement column authorization rules ("")
     *    - "authorization.recordHandler": Handler to implement record authorization filter rules ("")
     *    - "validation.handler":          Handler to implement validation rules for input values ("")
     *    - "ipAddress.tables":            Tables to search for columns to override with IP address ("")
     *    - "ipAddress.columns":           Columns to protect and override with the IP address on create ("")
     *    - "sanitation.handler":          Handler to implement sanitation rules for input values ("")
     *    - "multiTenancy.handler":        Handler to implement simple multi-tenancy rules ("")
     *    - "pageLimits.pages":            The maximum page number that a list operation allows ("100")
     *    - "pageLimits.records":          The maximum number of records returned by a list operation ("1000")
     *    - "joinLimits.depth":            The maximum depth (length) that is allowed in a join path ("3")
     *    - "joinLimits.tables":           The maximum number of tables that you are allowed to join ("10")
     *    - "joinLimits.records":          The maximum number of records returned for a joined entity ("1000")
     *    - "customization.beforeHandler": Handler to implement request customization ("")
     *    - "customization.afterHandler":  Handler to implement response customization ("")
     *
     *
     * @param string $config Parametro del middleware a configurar.
     * @param mixed $value Valor asignado al parametro.
     * @return $this
     */
    public function configMiddleware(string $config, $value)
    {
        $configs = [self::FIREWALL_REVERSE_PROXY, self::FIREWALL_ALLOWED_IP_ADDRESSES, self::CORS_ALLOWED_ORIGINS,
            self::CORS_ALLOW_HEADERS, self::CORS_ALLOW_METHODS, self::CORS_ALLOW_CREDENTIALS,
            self::CORS_EXPOSE_HEADERS, self::CORS_MAX_AGE, self::XSRF_EXCLUDE_METHODS, self::XSRF_COOKIE_NAME,
            self::XSRF_HEADER_NAME, self::AJAX_ONLY_EXCLUDE_METHODS, self::AJAX_ONLY_HEADER_NAME,
            self::AJAX_ONLY_HEADER_VALUE, self::JWT_AUTH_MODE, self::JWT_AUTH_HEADER, self::JWT_AUTH_LEEWAY,
            self::JWT_AUTH_TTL, self::JWT_AUTH_SECRET, self::JWT_AUTH_ALGORITHMS, self::JWT_AUTH_AUDIENCES,
            self::JWT_AUTH_ISSUERS, self::BASIC_AUTH_MODE, self::BASIC_AUTH_REALM, self::BASIC_AUTH_PASSWORD_FILE,
            self::AUTHORIZATION_TABLE_HANDLER, self::AUTHORIZATION_COLUMN_HANDLER,
            self::AUTHORIZATION_RECORD_HANDLER, self::VALIDATION_HANDLER, self::IP_ADDRESS_TABLES,
            self::IP_ADDRESS_COLUMNS, self::SANITATION_HANDLER, self::MULTI_TENANCY_HANDLER,
            self::PAGE_LIMITS_PAGES, self::PAGE_LIMITS_RECORDS, self::JOIN_LIMITS_DEPTH, self::JOIN_LIMITS_TABLES,
            self::JOIN_LIMITS_RECORDS, self::CUSTOMIZATION_BEFORE_HANDLER, self::CUSTOMIZATION_AFTER_HANDLER];

        if (in_array($config, $configs)) {
            $this->restCfg[$config] = $value;
        } else {
            throw new \InvalidArgumentException("Middleware `$mdl` no es válido");
        }

        return $this;
    }

    /**
     * Inicia el servicio REST crud
     *
     * @return void
     */
    public function run()
    {
        $cfg = $this->restCfg;
        if (isset($cfg['middlewares']) and is_array($cfg['middlewares'])) {
            $cfg['middlewares'] = implode(',', $cfg['middlewares']);
        }

        $config = new \Tqdev\PhpCrudApi\Config($cfg);
        $request = RequestFactory::fromGlobals();
        $api = new Api($config);
        $response = $api->handle($request);
        ResponseUtils::output($response);
    }
}

