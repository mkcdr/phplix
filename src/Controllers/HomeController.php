<?php

namespace App\Controllers;

use App\Core\Controller;

class HomeController extends Controller
{
    /**
     * Index
     * 
     * @return string
     */
    public function index()
    {
        return $this->render('welcome.html');
    }
}