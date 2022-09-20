<?php

namespace App\Middlewares;

class AuthMiddleware
{
    public function __invoke()
    {
        // Demo authentication
        if (!isset($_SESSION['usr'])) {
            redirect(url('user.login'));
        }
    }
};
