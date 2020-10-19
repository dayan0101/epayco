<?php

namespace ePayco\Kernel\Util;

if(!defined('OPENSSL_RAW_DATA')) {
    define('OPENSSL_RAW_DATA', 1);
}

/**
 * Codifica/Decodifica una cadena de texto a traves del cifrado AES-256-CBC
 **/
class Codificador
{
    const CYPHER = 'AES-256-CBC';
    const SHA2LEN = 32;

    /**
     * Codifica un texto a traves del algoritmo AES-256-CBC
     *
     * @param string $texto El texto a ser cifrado.
     * @param string $clave La clave a utilizar en el cifrado.
     * @return string El texto cifrado
     */
    public static function codificar($texto, $clave)
    {
        $ivlen = openssl_cipher_iv_length(self::CYPHER);
        $iv = openssl_random_pseudo_bytes($ivlen);
        $ciphertextRaw = openssl_encrypt($texto, self::CYPHER, $clave, OPENSSL_RAW_DATA, $iv);
        $hmac = hash_hmac('sha256', $ciphertextRaw, $clave, true);

        return base64_encode($iv.$hmac.$ciphertextRaw);
    }

    /**
     * Decodifica un texto a traves del algoritmo AES-256-CBC cifrado a traves de la funcion Codificador::cifrar()
     *
     * @param string $texto El texto a ser descifrado.
     * @param string $clave La clave asociada.
     * @return string El texto descifrado
     */
    public static function decodificar($texto, $clave)
    {
        $cipherRaw = base64_decode($texto);
        $ivlen = openssl_cipher_iv_length(self::CYPHER);
        $iv = substr($cipherRaw, 0, $ivlen);
        $hmac = substr($cipherRaw, $ivlen, self::SHA2LEN);
        $ciphertextRaw = substr($cipherRaw, $ivlen + self::SHA2LEN);

        $desencriptado = openssl_decrypt($ciphertextRaw, self::CYPHER, $clave, OPENSSL_RAW_DATA, $iv);
        $returnCadena = trim(str_replace(array("\t", "\r", "\n"), "", $desencriptado));
        return $returnCadena;
    }

    /**
     * Convierte el texto y la clave cifrada en una sola 'palabra' para el envio de estos datos
     *
     * @param string $texto El texto cifrado.
     * @param string $clave La clave asociada.
     * @return string
     */
    public static function formatoEnvio(string $texto, string $clave): string
    {
        $lentxt = strlen($texto);
        $lenclv = strlen($clave);
        $medtxt = (int)floor($lentxt / 2);
        return sprintf('%s$%s$%s%s%s', $lentxt, $lenclv, substr($texto, 0, $medtxt), $clave, substr($texto, $medtxt));
    }

    /**
     * Extrae la clave y el texto cifrado de un texto creado con la funcion Codificador::formatoEnvio
     *
     * @param string $texto El texto a extraer.
     * @return array Array asociativo con la `clave` y `texto` cifrado
     */
    public static function extraerFormato(string $texto): array
    {
        if (preg_match('/^(\d+)\$(\d+)\$(.+)$/', $texto, $match)) {
            $med = (int)floor($match[1] / 2);
            $txt = substr($match[3], 0, $med) . substr($match[3], $med + $match[2]);
            $clv = substr($match[3], $med, $match[2]);

            return ['clave' => $clv, 'texto' => $txt];
        } else {
            throw new \InvalidArgumentException('Cadena no cumple con formato para extracción');
        }
    }


    /**
     * Devuelve un texto fijo criptográficamente seguro completamente aleatorio
     *
     * @param integer $len longitud del texto a generar.
     * @return string Devuelve un texto aleatorio de longitud ($len * 2)
     */
    public static function randomString(int $len = 10)
    {
        if ($len <= 0) {
            $len = function_exists('random_int')? random_int(10, 50) : mt_rand(10, 50);
        }
        return function_exists('random_bytes')? bin2hex(random_bytes($len)) :
            bin2hex(openssl_random_pseudo_bytes($length));
    }
}
