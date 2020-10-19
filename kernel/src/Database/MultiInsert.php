<?php

namespace ePayco\Kernel\Database;


/**
 * Clase que realiza la inserción multiple de datos, utilizando  inserciones en lotes
 **/
class MultiInsert
{
    /**
     * @var array Listado de los campos a insertar
     */
    private $campos = null;
    /**
     * @var string Nombre de la tabla a realizar insercion
     */
    private $tabla = null;
    /**
     * @var array Listado de datos a subir por lote
     */
    private $data = null;
    /**
     * @var integer Cantidad de registros a subir
     */
    private $limite = 100;
    /**
     * @var integer Variable que lleva el conteo de registros almacenados en el lote a enviar
     */
    private $contador = 1;
    /**
     * @var ePayPdo Conexion a BD
     */
    private $cnx = null;
    /**
     * @var array Array asociativo con los valores de actualizacion en caso de necesitar agtregar la condicion
     *            `ON DUPLICATE KEY UPDATE`
     */
    private $updates = null;
    /**
     * @var boolean Indica si se va a realizar un INSERT IGNORE INTO
     */
    private $isIgnore = false;
    /**
     * @var boolean Esta variable controla que se hagan cambios despues que empiece a agregar registros
     */
    private $lock = false;
    /**
     * @var PDOStatement Sentencia preparada asociada a esta instancia
     */
    private $sentencia = null;

    /**
     * Constructor
     *
     * @param ePayPdo $cnx Instancia de conexion a BD.
     * @param string $tabla Nombre de la tabla.
     * @param array $campos Listado de los campos a insertar.
     * @param integer $limite La cantidad de registros que se van a preparar antes de enviar.
     */
    public function __construct(ePayPdo $cnx, $tabla, array $campos, $limite = 100)
    {
        $this->cnx    = $cnx;
        $this->tabla  = $tabla;
        $this->campos = $campos;
        $this->limite = $limite;
        $this->data   = array();
    }

    /**
     * Indica que esta insercion debe incluir IGNORE en la consulta INSERT
     */
    public function ignoreInto()
    {
        if (!$this->lock) {
            $this->isIgnore = true;
        }
    }

    /**
     * Agrega la condicion ON DUPLICATE KEY UPDATE a la inserción masiva
     *
     * @param array $params Array asociativo con los valores a actualizar, tener en cuenta que al momento de
     *                      asignar el nombre del campo debe de estar encerrado entre la funcion MySQL VALUES():
     *                      ['campo' => 'VALUES(campo) + 3']
     */
    public function onDuplicateKeyUpdate(array $params)
    {
        if (!$this->lock) {
            $tmp = array();
            foreach ($params as $key => $value) {
                $tmp[] = "$key = $value";
            }

            $this->updates = $tmp;
        }
    }

    /**
     * Prepara y guarda las filas para insertar cuando alcanza el valor especificado en $limite, envia el sql de
     * inserción y prepara todo para seguir recibiendo datos
     *
     * @param array $values Datos a guardar, deben ser la misma cantidad de elementos definidos en el sql de
     *                      inserción.
     */
    public function agregar(array $values)
    {
        if (!$this->lock) {
            $this->lock = true;
            $this->preparar();
        }

        $this->data[] = $values;

        if (++$this->contador > $this->limite) {
            $this->insert();
            $this->contador = 1;
            $this->data = array();
        }
    }

    /**
     * Inserta la informacion que se encuentre en el array $data
     */
    public function finalizar()
    {
        $val = '(' . implode(',', array_fill(0, count($this->campos), '?')) . ')';
        $sql = 'INSERT ' . ($this->isIgnore ? 'IGNORE ' : '') . 'INTO ' . $this->tabla . ' (' .
            implode(', ', $this->campos) . ')  VALUES ' . implode(',', array_fill(0, count($this->data), $val));

        if (is_array($this->updates)) {
            $sql .= ' ON DUPLICATE KEY UPDATE ' . implode(', ' . $this->updates);
        }

        $sentencia = $this->cnx->prepare($sql);

        $ex = array();
        foreach ($this->data as $row) {
            $ex = array_merge($ex, array_values($row));
        }


        $sentencia->execute($ex);

        $this->contador = 1;
        $this->data = array();
    }

    /**
     * Crea la sentencia preparada
     */
    private function preparar()
    {
        $val = '(' . implode(',', array_fill(0, count($this->campos), '?')) . ')';
        $sql = 'INSERT ' . ($this->isIgnore ? 'IGNORE ' : '') . 'INTO ' . $this->tabla . ' (' .
            implode(', ', $this->campos) . ')  VALUES ' . implode(',', array_fill(0, $this->limite, $val));

        if (is_array($this->updates)) {
            $sql .= ' ON DUPLICATE KEY UPDATE ' . implode(', ' . $this->updates);
        }

        if (!empty($this->cnx->logger) and is_callable($this->cnx->logger)) {
            call_user_func($this->cnx->logger, $sql);
        }

        $this->sentencia = $this->cnx->prepare($sql);
    }

    /**
     * Realiza la insercion de los datos que se encuentren en el array $data
     */
    private function insert()
    {
        $ex = array();
        foreach ($this->data as $row) {
            $ex = array_merge($ex, array_values($row));
        }

        $this->sentencia->execute($ex);
    }
}
