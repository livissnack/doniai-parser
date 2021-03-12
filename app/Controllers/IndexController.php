<?php

namespace App\Controllers;

use ManaPHP\Version;

class IndexController extends Controller
{
    public function indexAction()
    {
        $this->view->setVars(['version' => Version::get()]);
        return 0;
    }

    public function videoAction()
    {
        return 'adas';
    }
}
