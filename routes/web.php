<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/


//AUTHENTICATION
$router->group(['prefix'=>'/auth', 'middleware'=>'auth'], function()use($router){
    $router->get("/verify", ['uses'=>"AuthController@verify_login"]);
    $router->post("/request_stunting_madiunkab", ['uses'=>"AuthController@request_stunting_madiunkab"]);
    $router->get("/profile", ['uses'=>"AuthController@get_profile"]);
    $router->put("/profile", ['uses'=>"AuthController@update_profile"]);
    $router->delete("/logout", ['uses'=>"AuthController@logout"]);
});
$router->post("/auth/login", ['uses'=>"AuthController@login"]);

//REGION
$router->group(['prefix'=>'/region', 'middleware'=>'auth'], function()use($router){
    $router->get("/type/kecamatan", ['uses'=>"RegionController@gets_kecamatan"]);
    $router->get("/type/desa", ['uses'=>"RegionController@gets_desa"]);
    $router->get("/{id}", ['uses'=>"RegionController@get"]);
    $router->post("/", ['uses'=>"RegionController@add"]);
    $router->delete("/{id}", ['uses'=>"RegionController@delete"]);
    $router->put("/{id}", ['uses'=>"RegionController@update"]);
});

//USER
$router->group(['prefix'=>'/user', 'middleware'=>'auth'], function()use($router){
    $router->get("/", ['uses'=>"UserController@gets"]);
    $router->get("/{id}", ['uses'=>"UserController@get"]);
    $router->post("/", ['uses'=>"UserController@add"]);
    $router->delete("/{id}", ['uses'=>"UserController@delete"]);
    $router->put("/{id}", ['uses'=>"UserController@update"]);
});

//USER LOGIN
$router->group(['prefix'=>'/user_login', 'middleware'=>'auth'], function()use($router){
    $router->get("/", ['uses'=>"UserLoginController@gets"]);
    $router->delete("/{id}", ['uses'=>"UserLoginController@delete"]);
    $router->delete("/type/expired", ['uses'=>"UserLoginController@delete_expired"]);
});

//FILE
$router->group(['prefix'=>'/file', 'middleware'=>'auth'], function()use($router){
    $router->post("/upload", ['uses'=>"FileController@upload"]);
    $router->post("/upload_avatar", ['uses'=>"FileController@upload_avatar"]);
});
$router->get("/file/show/{file}", ['uses'=>"FileController@show"]);

//BALITA
$router->group(['prefix'=>'/balita', 'middleware'=>'auth'], function()use($router){
    $router->post("/", ['uses'=>"BalitaController@upsert"]);
    $router->delete("/{id}", ['uses'=>"BalitaController@delete"]);
    $router->get("/{id}", ['uses'=>"BalitaController@get"]);
});

//SKRINING BALITA
$router->group(['prefix'=>'/skrining_balita', 'middleware'=>'auth'], function()use($router){
    $router->get("/", ['uses'=>"BalitaSkriningController@gets"]);
    $router->post("/", ['uses'=>"BalitaSkriningController@add"]);
    $router->delete("/{id}", ['uses'=>"BalitaSkriningController@delete"]);
    $router->put("/{id}", ['uses'=>"BalitaSkriningController@update"]);
    $router->get("/{id}", ['uses'=>"BalitaSkriningController@get"]);
    $router->get("/summary/formula", ['uses'=>"BalitaSkriningController@get_formula"]);
});

//STUNTING
$router->group(['prefix'=>'/stunting', 'middleware'=>'auth'], function()use($router){
    $router->get("/summary_region", ['uses'=>"StuntingController@gets_stunting_by_region"]);
    $router->get("/", ['uses'=>"StuntingController@gets_stunting"]);
});

//HOME
$router->group(['prefix'=>'/home'], function()use($router){
    $router->get("/skrining_balita/data_masuk", ['uses'=>"HomeController@gets_skrining_data_masuk"]);
    $router->get("/summary_posyandu", ['uses'=>"HomeController@get_summary_posyandu"]);
});
