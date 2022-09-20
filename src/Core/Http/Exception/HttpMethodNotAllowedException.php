<?php

namespace App\Core\Http\Exception;

class HttpMethodNotAllowedException extends HttpException
{
    protected $code = 405;
    protected $message = 'Method not allowed';
    protected $title = '405 Method Not Allowed';
    protected $description = 'The request method is not supported for the requested resource.';
}