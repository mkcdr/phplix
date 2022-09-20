<?php

namespace App\Middlewares;

class AuthMiddleware
{
    public function __invoke()
    {
        // Demo authentication gose here
        if (!isset($_SESSION['usr'])) {
            redirect(url('user.login'));
        }
    }
};