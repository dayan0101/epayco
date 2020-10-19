<?php

namespace ePayco\Kernel\Database;

use Envms\FluentPDO\Query;

/**
 * Clase que extiende las funcionalidades de PDO
 *
 * @see http://php.net/manual/es/class.pdo.php
 **/
class ePayPdo extends \PDO
{
    /**
     * @var object Clase que realiza la traza de logs
     */
    public $logger = null;
    /**
     * @var array Proveedores de conexion a base de datos
     */
    private $dbProviders = array(
        'mysql'      => 'mysql:host=#{host};dbname=#{database};charset=utf8mb4',
        'postgresql' => 'pgsql:host=#{host};dbname=#{database}',
    );

    /**
     * Realiza la conexion a la base de datos
     *
     * @param string $host     Host de la bases de datos
     * @param string $dbname   Nombre de la base de datos
     * @param string $username Nombre del usuario utilizado para la conexion
     * @param string $password Contraseña utilizada para la conexion
     */
    public function __construct($host, $dbname, $username, $password, $provider='mysql')
    {
        $params = array(
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES   => true,
        );
        if ($provider == 'mysql') {
            $params[\PDO::MYSQL_ATTR_LOCAL_INFILE] = true;
            $params[\PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
        }
        $this->provider = $provider;
        parent::__construct(
            $this->replace($this->dbProviders[$provider], array('host' => $host, 'database' => $dbname)),
            $username,
            $password,
            $params
        );
    }

    /**
     * Prepara una sentencia SQL
     *
     * @return string
     */
    private function prepareQuery($statement, array $params)
    {
        $me = $this;
        foreach ($params as $param) {
            if (is_array($param)) {
                $param = implode(', ', array_map(function ($value) use ($me) {
                    return $me->quote($value);
                }, $param));
            } else {
                $param = $this->quote($param);
            }
            // Ajuste a funcionalidad de preg_replace frente a patrones $[0-9]:
            // http://php.net/manual/es/function.preg-replace.php
            $statement = preg_replace('/\?/', str_replace('$', '__DOLLAR__', $param), $statement, 1);
        }

        return str_replace('__DOLLAR__', '$', $statement);
    }

    /**
     * Ejecuta una sentencia SQL, devolviendo un conjunto de resultados como un objeto Result.
     *
     * @return \PDOStatement
     */
    public function query($statement, array $params = array())
    {
        $sql = $this->prepareQuery($statement, $params);

        if (is_callable($this->logger)) {
            call_user_func($this->logger, $sql);
        }

        return parent::query($sql);
    }

    /**
     * Ejecuta una sentencia SQL y devuelve el número de filas afectadas.
     *
     * @param string $statement
     *
     * @return integer
     */
    public function exec($statement, array $params = array())
    {
        $sql = $this->prepareQuery($statement, $params);

        if (is_callable($this->logger)) {
            call_user_func($this->logger, $sql);
        }

        return parent::exec($sql);
    }

    /**
     * Realiza una consulta sql y devuelve el primer elemento del primer resultado que encuentre. Esta funcion
     * es util cuando se realizen consultas en las que necesita devolver un unico valor (como un count).
     *
     * @param string $statement La sentencia SQL
     * @param array  $params    Los parámetros de la consulta.
     *
     * @return mixed El valor solicitado de la primera columna del primer resultado encontrado
     **/
    public function getSingleResult($statement, array $params = array())
    {
        $res = $this->query($statement, $params)->fetch(\PDO::FETCH_NUM);
        if (is_array($res)) {
            return $res[0];
        }

        return null;
    }

    /**
     * Prepara una sentencia para su ejecución y devuelve un objeto sentencia.
     *
     * @see http://php.net/manual/es/pdo.prepare.php
     * @param string $statement Una plantilla de sentencia SQL válida para el servidor de base de datos.
     * @param array $driverOptions Este array guarda uno o más pares clave=>valor para establecer el valor de los
     *                              atributos del objeto PDOStatement que este método devuelve.
     *
     * @return \PDOStatement
     */
    public function prepareStatement($statement, array $driverOptions = array())
    {
        if ($this->logger != null) {
            call_user_func($this->logger, 'PREPARED STATEMENT: ' . $statement);
        }

        return $this->prepare($statement, $driverOptions);
    }

    /**
     * Ingresa un registro a una tabla en la base de datos
     *
     * @param string $table  El nombre de la tabla a la cual se realizara el INSERT
     * @param array  $fields Array asociativo (`columna=>valor`) con la informacion a ingresar
     * @param boolean $ignore Se realiza una sentencia `INSERT IGNORE INTO`
     *
     * @return integer el numero de filas afectadas
     **/
    public function insert($table, array $fields, $ignore = false, $lastID = true)
    {
        $columns = array();
        $values = array();
        $params = array();
        foreach ($fields as $col => $val) {
            $columns[] = $col;
            $values[]  = $val;
            $params[]  = '?';
        }

        $sql = sprintf(
            'INSERT%s INTO %s(%s) VALUES(%s)',
            $ignore? ' IGNORE' : '',
            $table,
            implode(', ', $columns),
            implode(', ', $params)
        );
        $rows = $this->exec($sql, $values);

        return ($this->provider == 'postgresql' or !$lastID) ? $rows : $this->lastInsertId();
    }

    /**
     * Actualiza un registro en una tabla de la base de datos
     *
     * @param array $fields   Array asociativo (`columna=>valor`) con la informacion a ingresar
     * @param array $criteria Array asociativo columna => valor la cual sera transformada a una condicion
     *                        `columna=valor` enlazados a la condicional AND, si valor es un array la condicion
     *                        se convierte en una sentencia like (`columna IN (valores-del-array-valor)`)
     *
     * @return integer el numero de filas afectadas
     **/
    public function update($table, $fields, $criteria = array())
    {
        $columns = array();
        $values = array();
        foreach ($fields as $col => $val) {
            $columns[] = $col . '=?';
            $values[]  = $val;
        }

        $sql = sprintf(
            'UPDATE %s SET %s %s',
            $table,
            implode(', ', $columns),
            $this->getCriteria($criteria)
        );

        return $this->exec($sql, $values);
    }

    /**
     * Elimina uno o varios registros en una tabla de la base de datos
     *
     * @param array $criteria Array asociativo columna => valor la cual sera transformada a una condicion
     *                        `columna=valor` enlazados a la condicional AND, si valor es un array la condicion
     *                        se convierte en una sentencia like (`columna IN (valores-del-array-valor)`)
     *
     * @return integer el numero de filas afectadas
     **/
    public function delete($table, array $criteria)
    {
        if (empty($criteria)) {
            throw new \InvalidArgumentException('No se permite la eliminación de datos sin criterio definido');
        }
        $sql = sprintf(
            'DELETE FROM `%s` %s',
            $table,
            $this->getCriteria($criteria)
        );

        return $this->exec($sql);
    }

    /**
     * Crea una instancia de la clase MultiInsert con el query de insercion
     *
     * @param string $tabla Nombre de la tabla.
     * @param array $campos Listado de los campos a insertar.
     * @param integer $limite La cantidad de registros que se van a preparar antes de enviar.
     **/
    public function multiInsert($tabla, array $campos, $limite = 100)
    {
        return new MultiInsert($this, $tabla, $campos, $limite);
    }

    /**
     * Devuelve una instancia Query para construir consultas sql
     *
     * @return Query Instancia Query de la libreria FluentPDO
     */
    public function queryBuilder(): Query
    {
        return new Query($this);
    }

    /**
     * Define las llamadas a las funciones mágicas findXxx, findOneXxx, insertXxx y updateXxx donde Xxx representa la tabla a la
     * cual se le va a realizar la consulta. Si la tabla contiene '_' en la funcion se debe eliminar y poner
     *      *
     * Las funciones find aceptan tres parámetros:
     * - Criterios de consulta: Array asociativo columna => valor la cual sera transformada a una condicion
     *     `columna=valor` enlazados a la condicional AND, si valor es un array la condicion se convierte en
     *     una sentencia in (`columna IN (valores-del-array-valor)`)
     * - Columnas Array donde se agregan las columnas que seran traidas en esta consulta, si no se define,
     *     devolvera todas (`SELECT * FROM ...`)
     * - ColumnaOrden String con el nombre de la columna a ordenar precedido por un + si el orden es ascendente
     *     o - si la columna se ordena de forma descendente
     *
     * Las funciones insertXxx acepta un parámetro:
     * - Datos a ingresar: Array asociativo (`columna=>valor`) con la informacion a ingresar
     *
     * Las funciones updateXxx acepta dos parametros:
     * - Datos a actualizar: Array asociativo (`columna=>valor`) con la informacion a actualizar
     * - Criterios de consulta: Array asociativo columna => valor la cual sera transformada a una condicion
     *     `columna=valor` enlazados a la condicional AND, si valor es un array la condicion se convierte en
     *     una sentencia in (`columna IN (valores-del-array-valor)`)
     *
     * @return array|Result El resultado de la consulta generada, si es unmetodo findXxx(), devuelve un objeto
     *                      \Core\Database\Result, si es un metodo findOneXXX() devuelve un array
     *                      asociativo
     **/
    public function __call($name, array $arguments)
    {
        if ($this->startsWith($name, 'findOne') or $this->startsWith($name, 'find')) {
            $criteria = (isset($arguments[0]) ? $arguments[0] : array());
            $columns  = (isset($arguments[1]) ? $arguments[1] : array());
            $orderBy  = (isset($arguments[2]) ? $arguments[2] : null);
            $me       = $this;
            $one      = false;

            $sql = 'SELECT ';
            if (!empty($columns)) {
                $sql .= implode(
                    ', ',
                    array_map(
                        function ($value) use ($me) {
                            return "`$value`";
                        },
                        $columns
                    )
                );
            } else {
                $sql .= '*';
            }

            if (strncmp($name, 'findOne', 7) === 0) {
                $table = strtolower(preg_replace('/\B([A-Z])/', '_$1', substr($name, 7)));
                $one = true;
            } elseif (strncmp($name, 'find', 4) === 0) {
                $table = strtolower(preg_replace('/\B([A-Z])/', '_$1', substr($name, 4)));
            }

            $sql .= ' FROM ' . $table;

            $sql.= $this->getCriteria($criteria);

            if (!empty($orderBy)) {
                $order = $orderBy[0] == '-' ? 'DESC' : 'ASC';
                $column = substr($orderBy, 1);

                $sql .= " ORDER BY $column $order";
            }

            if ($one) {
                $sql .= ' LIMIT 1';
            }

            $res = $this->query($sql);
            return ($one ? $res->fetch() : $res);
        } elseif ($this->startsWith($name, 'insert') or $this->startsWith($name, 'update')) {
            $table = strtolower(preg_replace('/\B([A-Z])/', '_$1', substr($name, 6)));
            $params = array_merge(array($table), $arguments);
            return call_user_func_array(array($this, $this->startsWith($name, 'insert') ? 'insert' : 'update'), $params);
        } else {
            throw new \BadFunctionCallException("Función '$name' no existe.");
        }
    }

    /**
     * Crea la cadena SQL de la condicion WHERE basada en el array asociativo pasado
     *
     * @param array $criteria Array asociativo columna => valor la cual sera transformada a una condicion
     *                        `columna=valor` enlazados a la condicional AND, si valor es un array la condicion
     *                        se convierte en una sentencia like (`columna IN (valores-del-array-valor)`)
     *
     * @return String La condicion WHERE generada
     **/
    private function getCriteria(array $criteria)
    {
        $sql = '';
        if (!empty($criteria)) {
            $sql .= ' WHERE ';
            $me = $this;
            $criteriaTmp = array();
            foreach ($criteria as $col => $value) {
                if (is_array($value)) {
                    $criteriaTmp[] = sprintf(
                        '%s IN (%s)',
                        $this->quote($col),
                        implode(
                            ', ',
                            array_map(
                                function ($val) use ($me) {
                                    return $me->quote($val);
                                },
                                $value
                            )
                        )
                    );
                } else {
                    $criteriaTmp[] = $col . ' = ' . $this->quote($value);
                }
            }

            $sql .= implode(' AND ', $criteriaTmp);
        }
        return $sql;
    }

   /**
    * Realiza un reemplazo dependiendo de las variables definidas en el texto, las variables a reemplazar
    * debe estar encerrado entre #{ y }:
    *
    * 'Esto es un #{valor} a reemplazar' si valor ='texto' el resultado seria: 'Esto es un texto a reemplazar'
     *
     *  @param string $text   El texto al cual se le aplica el reemplazo.
     *  @param array  $params Listado de parametros tipo `clave => valor` donde la clave es la variable a
     *                        reemplazar en el texto sin #{ y } y valor es es valor a ser reemplazado.
     *
     * @return string La cadena resultado
     **/
    public function replace($text, array $params)
    {
        $fields = array();
        $values = array();
        $keys = array_keys($params);
        foreach ($keys as $key) {
            $fields[] = '#{' . $key . '}';
            $values[] = $params[$key];
        }

        return str_replace($fields, $values, $text);
    }

    /**
     * Valida si un texto comienza con el texto indicado, la funcion es sensible a mayusculas y minusculas:
     *     startsWith('abcdef', 'abc'); //true
     *     startsWith('ABCDEF', 'abc'); //false
     *
     * @param string $str    El texto a validar.
     * @param string $prefix El texto a comprobar si comienza $str.
     *
     * @return boolean Si comienza o no con el texto seleccionado.
     */
    public function startsWith($str, $prefix)
    {
        return (null === $str and null === $prefix) ? true :
            substr($str, 0, strlen($prefix)) === $prefix;
    }
}
