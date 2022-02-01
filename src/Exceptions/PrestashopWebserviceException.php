<?php


namespace Pdik\LaravelPrestaShop\Exceptions;

use Exception;
use Throwable;

class PrestashopWebserviceException extends Exception
{
    public function __construct(string $message = 'Prestashop error', int $code = 404, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
