<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

$app->get('/', function() use ($app) {
    return view('reptile.index');
});

// 爬虫首页
$app->get('/reptile', 'ReptileController@index');

// 爬虫结果
$app->get('/result', 'ReptileController@handle');
