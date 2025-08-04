<?php

class Firewall
{
    public function __construct()
    {
        $this->checkRequest();
    }

    private function checkRequest(){
        echo('hello');
    }
}