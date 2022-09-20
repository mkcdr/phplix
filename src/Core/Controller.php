<?php

namespace App\Core;

class Controller
{
    /**
     * Redirect 
     * 
     * @param string $location
     * @return void
     */
    protected function redirect(string $location) : void
    {
        header("Location: $location");
        exit;
    }

    /**
     * Render template
     * 
     * @param string $template
     * @param array $data
     * @return string
     */
    protected function render(string $template, array $data=[]) : string
    {
        return View::create()->render($template, $data);
    }
}