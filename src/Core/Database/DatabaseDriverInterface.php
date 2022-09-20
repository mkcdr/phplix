<?php

namespace App\Core\Database;

interface DatabaseDriverInterface
{
    public function name();
    public function version();
    public function getConnection();
}