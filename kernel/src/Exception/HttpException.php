<?php

namespace ePayco\Kernel\Exception;

/**
 * Excepcion encargada de gestionar errores HTTP
 **/
class HttpException extends \RuntimeException
{
    private $statusCode;
    private $options = [];

    public function __construct(int $statusCode, string $message = null, array $options = [])
    {
        $this->statusCode = $statusCode;
        $this->options = $options;

        parent::__construct($message, 0);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getOptions(): array
    {
        return $this->options;
    }
}
