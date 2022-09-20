<?php

namespace App\Core\Http\Exception;

class HttpCsrfForbiddenException extends HttpForbiddenException
{
    protected $message = 'CSRF token mismatch';
}