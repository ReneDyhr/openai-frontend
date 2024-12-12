<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('search');
});
Route::get('/floating', function () {
    return view('floating');
});

Route::post('/threads', 'App\Http\Controllers\ThreadController@store');
Route::get('/threads/{thread}', 'App\Http\Controllers\ThreadController@show');

Route::post('/files/upload', 'App\Http\Controllers\FileController@upload');
