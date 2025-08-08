<?php

Routes::get('/', 'HomeController@index');
Routes::get('/app/home', 'HomeController@index');
Routes::get('/app/home/index', 'HomeController@index');

Routes::get('/health', function () {
    \Response::json(['status' => 'ok']);
});