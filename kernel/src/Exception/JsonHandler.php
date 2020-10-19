<?php

namespace ePayco\Kernel\Exception;


use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Visualiza el detalle del error en un formato JSON
 **/
class JsonHandler extends AbstractExceptionHandler
{
    /**
     * Procesa el error para mostrarlo al usuario
     */
    public function handle()
    {
        $json = [
            'error' => true,
            'clase' => $this->clase,
            'mensaje' => $this->mensaje,
        ];
        $response = new JsonResponse($json, $this->codigoHttp);

        $response->send();
    }

}
