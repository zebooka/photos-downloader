<?php

namespace Zebooka\Utils\Executor;

class Command
{
    private $cmd;
    private $successMessage;
    private $errorMessage;

    public function __construct($cmd, $errorMessage = null, $successMessage = null)
    {
        $this->cmd = $cmd;
        $this->successMessage = $successMessage;
        $this->errorMessage = $errorMessage;
    }

    public function command()
    {
        return $this->cmd;
    }

    public function errorMessage()
    {
        return $this->errorMessage;
    }

    public function successMessage()
    {
        return $this->successMessage;
    }
}
