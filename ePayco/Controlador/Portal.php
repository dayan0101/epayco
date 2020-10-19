<?php

namespace ePayco\ePaycoPortal\Controlador;


use ePayco\ePaycoPortal\Modelo\Usuario;
use ePayco\ePaycoPortal\Modelo\Authorization;
use ePayco\ePaycoPortal\Modelo\Facturacion;
use ePayco\Kernel\Exception\HttpException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Valitron\Validator;

/**
 * Controlador Gestion creacion y acceso de usuarios
 **/
class Portal extends Base
{
   /**
     * Registra un usuario en el sistema
     * @param Request $request El objeto de la peticiÛn.
     * @param Kernel $kernel Instancia principal de la aplicaciÛn.
     * @return JsonResponse
     */
    public function registroUsuario (Request $request, \Kernel $kernel): JsonResponse
    {
        $kernel->forzarMetodos(['POST']);
        $this->validar($request, $kernel);
        $v = new Validator($this->json);
        $v->rule('required', ['Documento','Nombres','Email', 'Celular'])
          ->rule('numeric', 'Documento')                  
          ->rule('email', 'Email')
          ->rule('numeric', 'Celular');
        if ($v->validate()) {            
            $usuario = new Usuario;
            $usuario->registrarUsuario($this->json);
            $auth = new Authorization;
            $exp = 60 * 60 * 24 * 30; //un mes para confirmar el correo
            $token = $auth->generarToken($exp, $this->json);             
            $this->email($this->json['email'], 'ConfirmaciÛn de email en ePayco.co', 'tpl::confirma_email.html', ['token' => $token, 'Nombres' => $this->json['Nombres']],$kernel);         
            $json['data'] = 'verificaciÛn enviada';            
            return new JsonResponse($json, JsonResponse::HTTP_OK);
        } else {
            $options = ['message' => true, 'extra' => ['campo' => $v->errors()]];
            throw new HttpException(Response::HTTP_BAD_REQUEST, "El documento JSON no es v·lido", $options);
        }
    }
    /**
     * Valida la url de confirmacion de email
     * @param Request $request El objeto de la peticiÛn.
     * @param Kernel $kernel Instancia principal de la aplicaciÛn.
     * @return JsonResponse
     */
    public function validarUrl(Request $request, \Kernel $kernel): JsonResponse
    {
        $kernel->forzarMetodos(['GET']);
        $token = $request->query->get('token'); 
        $auth = new Authorization;
        $auth->validarToken($token);            
        return new JsonResponse(['status' => 'ok'], JsonResponse::HTTP_OK);        
    }
    
    /**
     * Creacion de contraseÒa de usuario
     * @param Request $request El objeto de la peticiÛn.
     * @param Kernel $kernel Instancia principal de la aplicaciÛn.
     * @return JsonResponse
     */
    public function crearPass(Request $request, \Kernel $kernel): JsonResponse
    {
        $token = $this->check($request);
        $kernel->forzarMetodos(['POST']);
        $auth = new Authorization;
        $data = $auth->validarToken($token);
        $this->validar($request, $kernel);
        $v = new Validator($this->json);
        $v->rule('required', 'password');
        if ($v->validate()) {
            $usuario = new Usuario;
            $usuario->crearPass($data, $this->json['password']);
            $json['data'] = 'ContraseÒa creada correctamente';         
            return new JsonResponse($json, JsonResponse::HTTP_OK);  
        } else {
            $options = ['message' => true, 'extra' => ['campo' => $v->errors()]];
            throw new HttpException(Response::HTTP_BAD_REQUEST, "El documento JSON no es v·lido", $options);
        }       
    }
    
     /**
     * Creacion de contraseÒa de usuario
     * @param Request $request El objeto de la peticiÛn.
     * @param Kernel $kernel Instancia principal de la aplicaciÛn.
     * @return JsonResponse
     */
    public function loguearUsuario(Request $request, \Kernel $kernel): JsonResponse
    {
        $kernel->forzarMetodos(['POST']);
        $this->validar($request, $kernel);
        $v = new Validator($this->json);
        $v->rule('required', ['email', 'password']);
        if ($v->validate()) {
            $usuario = new Usuario;
            if ($usuario->validarPass($this->json)){
                $exp = 60 * 60; //token con una hora para expirar
                $auth = new Authorization;
                $json['token'] = $auth->generarToken($exp, $this->json);           
                return new JsonResponse($json, JsonResponse::HTTP_OK);  
            } else {
                return new JsonResponse(['codigo' => 'NOT_FOUND'], JsonResponse::HTTP_NOT_FOUND);
            }            
        } else {
            $options = ['message' => true, 'extra' => ['campo' => $v->errors()]];
            throw new HttpException(Response::HTTP_BAD_REQUEST, "El documento JSON no es v·lido", $options);
        }       
    }
    /**
     * Crea un factura nueva
     *
     * @param Request $request El objeto de la peticiÛn.
     * @param Kernel $kernel Instancia principal de la aplicaciÛn.
     * @return JsonResponse
     */
    public function crearFactura(Request $request, \Kernel $kernel): JsonResponse
    {
        $token = $this->check($request);
        $kernel->forzarMetodos(['POST']);
        $auth = new Authorization;
        $data = $auth->validarToken($token);
        $this->validar($request, $kernel);
        $v = new Validator($this->json);
        $v->rule('required', ['email_cliente','nombre_cliente', 'direccion', 'tipo_documento','documento','telefono_cliente','nombre','descripcion','moneda','monto','tax_base','tax', 'pais','lang', 'email'])
            ->rule('numeric','documento')
            ->rule('numeric', 'telefono_cliente')
            ->rule('numeric', 'factura')
            ->rule('email', 'email_cliente');
        if ($v->validate()) {
            $facturas = new Facturacion;
            $id = $facturas->crearFacturas($data, $this->json);
            $tokenFactura = $auth->generarTokenFactura($id); 
            $this->email($this->json['email_cliente'], 'Pago de Factura en ePayco.co', 'tpl::solicitud_pago.html', ['token' => $tokenFactura, 'Nombres' => $this->json['nombre_cliente']], $kernel); 
            return new JsonResponse(['status' => 'ok']);         
        } else {
            $options = ['message' => true, 'extra' => ['campo' => $v->errors()]];
            throw new HttpException(Response::HTTP_BAD_REQUEST, "El documento JSON no es v·lido", $options);
        }
    }  
    /**
     * lista las facturas existentes     *
     * @param Request $request El objeto de la peticiÛn.
     * @param Kernel $kernel Instancia principal de la aplicaciÛn.
     * @return JsonResponse
     */
    public function listarFacturas(Request $request, \Kernel $kernel): JsonResponse 
    {
        $token = $this->check($request);
        $kernel->forzarMetodos(['GET']);
        $auth = new Authorization;
        $data = $auth->validarToken($token);
        $facturas = new Facturacion;
        $result = $facturas->obtenerFacturas($data);
        $datos = array();
        foreach ($result as $row) {
            $datos[] = $row;
        }
        $json['data'] = $datos;
        return new JsonResponse($json, JsonResponse::HTTP_OK);        
    }
    /**
     * Busca el detalle de una factura
     *
     * @param Request $request El objeto de la peticiÛn.
     * @param Kernel $kernel Instancia principal de la aplicaciÛn.
     * @return JsonResponse
     */
    public function detalleFactura(Request $request, \Kernel $kernel): JsonResponse
    {
        $token = $this->check($request);
        $kernel->forzarMetodos(['POST']);
        $auth = new Authorization;
        $auth->validarToken($token);
        $this->validar($request, $kernel);
        $v = new Validator($this->json);
        $v->rule('required', 'id')
            ->rule('numeric', 'id');
        if ($v->validate()) {
            $facturas = new Facturacion;
            $detalleFacturas = $facturas->detalleFacturas($this->json['id']);
            $json['data'] = $detalleFacturas;            
            return new JsonResponse($json, JsonResponse::HTTP_OK);            
        } else {
            $options = ['message' => true, 'extra' => ['campo' => $v->errors()]];
            throw new HttpException(Response::HTTP_BAD_REQUEST, "El documento JSON no es v·lido", $options);
        }
    }
    /**
     * Obtiene el detalle de la factura del cliente para pagar   *
     * @param Request $request El objeto de la peticiÛn.
     * @param Kernel $kernel Instancia principal de la aplicaciÛn.
     * @return JsonResponse
     */
    public function clienteFactura(Request $request, \Kernel $kernel): JsonResponse 
    {
        $kernel->forzarMetodos(['GET']);
        $token = $request->query->get('token'); 
        $auth = new Authorization;
        $data = $auth->validarToken($token);
        $facturas = new Facturacion;
        $result = $facturas->detalleFacturas($data['id']);
        $datos = array();
        foreach ($result as $row) {
            $datos[] = $row;
        }
        $json['data'] = $datos;
        return new JsonResponse($json, JsonResponse::HTTP_OK);        
    }
    /**
     * Envia un email a un destinatario
     *
     * @param string $destino El email de destino.
     * @param string $asunto El asunto del email.
     * @param string $template Namespace donde est· ubicado la plantilla del correo.
     * @param array $data Array asociativo con la informacion incluida en la plantilla.
     * @param Kernel $kernel Instancia principal de la aplicaciÛn.
     * @return void
     */
    private function email(string $destino, string $asunto, string $template, array $data, \Kernel $kernel)
    {
        $mailer = $kernel['mailer'];

        $message = new \Swift_Message();
        $message->setSubject($asunto)
                ->setFrom(['no-reply@epay.co' => 'EpayCo'])
                ->setTo($kernel['email.prueba'] ?? $destino)
                ->setBody($kernel['template']->render($template, array_merge($data, ['message' => $message])), 'text/html');

        $result = $mailer->send($message);
    }

    /**
     * envia un Mensaje de texto a un numero especifico
     *
     * @param string $cel Numero celular destino.
     * @param string $mensaje El mensaje a enviar.
     * @param Kernel $kernel Instancia principal de la aplicaci√≥n.
     * @return void
     */
    private function sms(string $cel, string $mensaje, \Kernel $kernel)
    {
        $sms = [
            'from' => 'EPAYCO',
            'to'   => $kernel['sms.tel'] ?? $cel,
            'text' => $mensaje
        ];
        $curl = curl_init();
        // Reemplazar las XXXXXX en la autorizaciÛn
        curl_setopt_array($curl, [
            CURLOPT_URL            => "https://{$kernel['infobip.url']}/sms/2/text/single",
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => "",
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => "POST",
            CURLOPT_POSTFIELDS     => json_encode($sms),
            CURLOPT_HTTPHEADER     => [
                "accept: application/json",
                "authorization: Basic XXXXXXXXXX",
                "content-type: application/json"
            ],
        ]);
        if (isset($kernel['proxy.user'])) {
            curl_setopt($curl, CURLOPT_PROXY, $kernel['proxy.host']);
            curl_setopt($curl, CURLOPT_PROXYUSERPWD, $kernel['proxy.user']);
        }

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            $json = ['message' => true, 'extra' => ['error' => "Error con el envÌo del SMS: $err"]];
            throw new HttpException(Response::HTTP_BAD_REQUEST, "Error con el envÌo del SMS: $err", $json);
        }

    }
    /**
     * accion para ruta por defecto
     *
     * @return void
     */
    public function notFound(Request $request, \Kernel $kernel)
    {
        $this->render('templates/home.html');
        //return new JsonResponse(['codigo' => 'NOT_FOUND'], JsonResponse::HTTP_NOT_FOUND);
    }
    /**
     * Da formato a una fecha, manteniendo los nombres de los dias y meses en espa√±ol, funciona exactamente como la
     * funcion `date()`
     *
     * @param string $format El formato de salida.
     * @param integer $timestamp Marca unix integer a formatear.
     * @return string
     * @see http://php.net/manual/es/function.date.php
     */
    private static function dateEsp(string $format, $timestamp = null)
    {
        if ($timestamp == null) {
            $timestamp = time();
        }
        $dateFormat = date($format, $timestamp);
        $search = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday', 'January',
            'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November',
            'December', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun', 'Jan', 'Apr', 'Aug', 'Dec');
        $replace = array('Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado', 'Domingo', 'Enero',
            'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre',
            'Diciembre', 'Lun', 'Mar', 'Mie', 'Jue', 'Vie', 'Sab', 'Dom', 'Ene', 'Abr', 'Ago', 'Dic');

        return str_replace($search, $replace, $dateFormat);
    }

}
