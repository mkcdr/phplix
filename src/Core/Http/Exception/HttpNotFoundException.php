<?php

namespace App\Core\Http\Exception;

class HttpNotFoundException extends HttpException
{
    protected $code = 404;
    protected $message = 'Not Found';
    protected $title = '404 Not Found';
    protected $description = 'The requested resource could not be found. Please verify the URI and try again.';
}