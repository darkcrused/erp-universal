<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->make('<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>ERP Universal</title></head><body><h1>ERP Universal</h1><p>Sistema online.</p><p><a href="/api/ping">/api/ping</a></p></body></html>', 200, [
        'Content-Type' => 'text/html; charset=UTF-8',
    ]);
});
