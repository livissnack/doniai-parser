<?php

namespace App;

use App\Controllers\IndexController;

class Router extends \ManaPHP\Http\Router
{
    public function __construct()
    {
        parent::__construct();
        $this->add('/', 'Index::index');
        $this->add('/api', 'Api::index');
    }
}