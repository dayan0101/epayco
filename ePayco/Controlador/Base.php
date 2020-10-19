<?php

namespace ePayco\ePaycoPortal\Controlador;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use ePayco\Kernel\Exception\HttpException;

/**
 * Clase base para los controladores
 **/
class Base
{
    /**
     * @var array Data json incluida en el cuerpo de la peticion
     */
    protected $json = [];
    /**
     * @var array Listado de errores JSON
     */
    private $jsonErrors = [
        JSON_ERROR_DEPTH                 => 'Se ha excedido la profundidad máxima de la pila',
        JSON_ERROR_STATE_MISMATCH        => 'JSON con formato incorrecto o inválido',
        JSON_ERROR_CTRL_CHAR             => 'Error del carácter de control, posiblemente se ha codificado de forma incorrecta',
        JSON_ERROR_SYNTAX                => 'Error de sintaxis',
        JSON_ERROR_UTF8                  => 'Caracteres UTF-8 mal formados, posiblemente codificados de forma incorrecta',
        JSON_ERROR_RECURSION             => 'Una o más referencias recursivas en el valor a codificar',
        JSON_ERROR_INF_OR_NAN            => 'Uno o más valores NAN o INF en el valor a codificar',
        JSON_ERROR_UNSUPPORTED_TYPE      => 'Se proporciona un valor de un tipo que no se puede codificar',
        JSON_ERROR_INVALID_PROPERTY_NAME => 'Se dio un nombre de una propiedad que no puede ser codificada',
        JSON_ERROR_UTF16                 => 'Caracteres UTF-16 malformados, posiblemente codificados de forma incorrecta',
    ];

    /**
     * Valida si existe el token en el request
     *
     * @return string token
     */
    public function check(Request $request)
    {
        if ($request->headers->has('Authorizations') && strpos($request->headers->get('Authorizations'), 'Bearer ') === 0) {
            $authorizationHeader = $request->headers->get('Authorizations');
            return substr($authorizationHeader, 7);
        } else {
            throw new HttpException(Response::HTTP_UNAUTHORIZED, 'token inválido');
        }
        
    }

    /**
     * Valida los parametros y configuaciones antes de iniciar con el controlador
     *
     * @throws HttpException Si no cumple con algunos de los requerimientos definidos en la funcion
     */
    public function validar(Request $request, \Kernel $kernel)
    {
        $this->getJson($request->getContent(), $kernel);
    }

    /**
     * Analiza la informacion de la peticion y la extrae en un JSON.
     *
     * @param string $content Contenido de la peticion a analizar
     * @param Kernel $kernel Instancia del Kernel
     */
    private function getJson(string $content, \Kernel $kernel)
    {
        $this->json = json_decode($content, true);

        $error = json_last_error();
        if ($error != JSON_ERROR_NONE) {
            $kernel['log']->addInfo('Error en analisis del JSON: ' . $this->jsonErrors[$error]);
            $options = ['message' => false, 'extra' => [
                'campo'    => 'json',
                'recibido' => $content,
                'message'  => 'Error en analisis del JSON: ' . $this->jsonErrors[$error]
            ]];
            throw new HttpException(Response::HTTP_BAD_REQUEST, 'Error en extraccion del JSON', $options);
        }
    }

    /**
     * Aplica un filtro y devuelve una variable del array json de la clase
     *
     * @see http://php.net/manual/es/function.filter-var.php
     * @param string $campo El nombre del campo a consultar.
     * @param int $filtro El filtro a aplicar, los mismos filtros de la funcion `filter_var`.
     * @param mixed $options Array asociativo de opciones o disyunción lógica de flags o closure aplicable a un filtro.
     * @return mixed|null
     */
    protected function filtrarCampoJson(string $campo, int $filtro, $options = [])
    {
        if (isset($this->json[$campo])) {
            $filtrado = filter_var($this->json[$campo], $filtro, $options);

            return $filtrado !== false? $filtrado : null;
        }

        return null;
    }
}
