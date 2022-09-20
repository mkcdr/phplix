<?php

namespace App\Core;

use App\Core\Http\Exception\HttpCsrfForbiddenException;

class Security
{
    /**
     * @var string CSRF token name
     */
    const CSRF_TOKEN_NAME = 'csrf_token';

    /**
     * Protect current request from CSRF attack
     * 
     * @throws HttpForbiddenExceptions
     * @return void
     */
    public function csrfProtect() : void
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST')
        {
            $token = filter_input(INPUT_POST, self::CSRF_TOKEN_NAME);

            // Check the request header for the CSRF token if not provided via $_POST
            if (!$token && isset($_SERVER['HTTP_X_CSRF_TOKEN']))
            {
                $token = filter_input(INPUT_SERVER, 'HTTP_X_CSRF_TOKEN');
            }
    
            if (!$this->verifyCsrfToken($token))
            {
                throw new HttpCsrfForbiddenException();
            }

            $this->removeCsrfToken();
        }
    }

    /**
     * Get the CSRF token from session and create one if not exists
     * 
     * @return string
     */
    public function getCsrfToken() : string
    {
        return base64_encode(
            isset($_SESSION[self::CSRF_TOKEN_NAME]) ? 
            $_SESSION[self::CSRF_TOKEN_NAME] : (
            $_SESSION[self::CSRF_TOKEN_NAME] = bin2hex(openssl_random_pseudo_bytes(32))
        ));
    }

    /**
     * Verify CSRF token
     * 
     * @param string $token Base64 encoded CSRF token
     * @return bool
     */
    public function verifyCsrfToken($token) : bool
    {
        return is_string($token) &&
                isset($_SESSION[self::CSRF_TOKEN_NAME]) && 
                hash_equals($_SESSION[self::CSRF_TOKEN_NAME], base64_decode($token));
    }

    /**
     * Remove CSRF token from session
     * 
     * @return void
     */
    public function removeCsrfToken() : void
    {
        unset($_SESSION[self::CSRF_TOKEN_NAME]);
    }
}