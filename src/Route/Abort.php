<?php

namespace HardJunior\Route;

/**
 * Class HardJunior Abort
 *
 * Lançada por um middleware para interromper a requisição com um código HTTP.
 * O Dispatch captura-a, define error() e devolve false (o controller não corre).
 *
 * @author Ivamar Júnior <https://github.com/hardjunior>
 * @package HardJunior\route
 */
class Abort extends \RuntimeException
{
    /**
     * @param int $status Código HTTP (ex.: 401, 403, 404)
     * @param string $message
     */
    public function __construct(int $status = 403, string $message = "")
    {
        parent::__construct($message, $status);
    }
}
