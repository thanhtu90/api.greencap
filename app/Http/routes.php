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

Route::get('/', function () {
    return view('welcome');
});

Route::group(['prefix' => 'v1'], function () {
    Route::post('account/login', 'AccountController@login');
    Route::post('account/register', 'AccountController@register');
    Route::post('account/fb-register', 'AccountController@facebookRegister');
    Route::put('account/change-password', 'AccountController@changePassword');
    Route::post('account/upload-avatar', 'AccountController@uploadAvatar');
    Route::post('account/upload-baby-avatar', 'AccountController@uploadBabyAvatar');
    Route::put('account/update', 'AccountController@updateAccount');
    Route::get('location/cities', 'AccountController@getCityList');
    Route::get('location/districts', 'AccountController@getDistrictList');
    Route::get('location/district-by-city/{cityID}', 'AccountController@getDistrictByCity');
    Route::get('account/token', 'AccountController@getToken');

    Route::get('post/{postID}', 'PostController@getPost');
    Route::get('technical_new/{limit?}', 'PostController@getTechnicalNews');
    Route::get('posts/{limit?}', 'PostController@getBlogNews');
    Route::get('library', 'PostController@getLibrary');

});