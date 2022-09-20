<?php

namespace App\Middlewares;

use App\Core\Security;

class CsrfProtectMiddleware
{
    public function __invoke()
    {
        $security = new Security();
        $security->csrfProtect();
    }
}