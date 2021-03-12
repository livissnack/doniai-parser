<?php

namespace ManaPHP\Exception;

use ManaPHP\Exception;

class MethodNotAllowedHttpException extends Exception
{
    /**
     * @param array $verbs
     */
    public function __construct($verbs)
    {
        parent::__construct('This URL can only handle the following request methods: ' . implode(', ', $verbs));
    }

    /**
     * @return int
     */
    public function getStatusCode()
    {
        return 405;
    }
}