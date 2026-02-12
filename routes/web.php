<?php

use Illuminate\Support\Facades\Route;

Route::get('/install', function () {
    return view('install');
});



Route::get('/', function () {
    return "Sistema de API Orçamento";
});
