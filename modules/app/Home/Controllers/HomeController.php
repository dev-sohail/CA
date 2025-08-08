<?php

class HomeController
{
    public function index(): void
    {
        $template = new \Template('app', 'Home');
        $template->set('title', 'Home');
        $template->render('home/index');
    }
}