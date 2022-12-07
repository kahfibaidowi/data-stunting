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
    $router->get("/generate_kependudukan_system_token", ['uses'=>"AuthController@generate_kependudukan_system_token"]);
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

//SKRINING BALITA
$router->group(['prefix'=>'/skrining_balita', 'middleware'=>'auth'], function()use($router){
    $router->get("/", ['uses'=>"SkriningBalitaController@gets"]);
    $router->post("/", ['uses'=>"SkriningBalitaController@add"]);
    $router->post("/type/multiple", ['uses'=>"SkriningBalitaController@add_multiple"]);
    $router->delete("/{id}", ['uses'=>"SkriningBalitaController@delete"]);
    $router->put("/{id}", ['uses'=>"SkriningBalitaController@update"]);
    $router->get("/{id}", ['uses'=>"SkriningBalitaController@get"]);
});

//STUNTING
$router->group(['prefix'=>'/stunting', 'middleware'=>'auth'], function()use($router){
    $router->get("/summary_region", ['uses'=>"StuntingController@gets_stunting_by_region"]);
    $router->get("/", ['uses'=>"StuntingController@gets_stunting"]);
});

//STUNTING 4118
$router->group(['prefix'=>'/stunting_4118', 'middleware'=>'auth'], function()use($router){
    $router->post("/type/multiple", ['uses'=>"Stunting4118Controller@add_multiple"]);
    $router->get("/", ['uses'=>"Stunting4118Controller@gets"]);
    $router->get("/summary_kecamatan", ['uses'=>"Stunting4118Controller@gets_stunting_by_kecamatan"]);
});

//INTERVENSI RENCANA KEGIATAN
$router->group(['prefix'=>'/intervensi_rencana_kegiatan', 'middleware'=>'auth'], function()use($router){
    $router->post("/", ['uses'=>"IntervensiRencanaKegiatanController@add"]);
    $router->put("/{id}", ['uses'=>"IntervensiRencanaKegiatanController@update"]);
    $router->delete("/{id}", ['uses'=>"IntervensiRencanaKegiatanController@delete"]);
    $router->get("/{id}", ['uses'=>"IntervensiRencanaKegiatanController@get"]);
    $router->get("/", ['uses'=>"IntervensiRencanaKegiatanController@gets"]);
});


//INTERVENSI RENCANA BANTUAN
$router->group(['prefix'=>'/intervensi_rencana_bantuan', 'middleware'=>'auth'], function()use($router){
    $router->post("/", ['uses'=>"IntervensiRencanaBantuanController@add"]);
    $router->put("/{id}", ['uses'=>"IntervensiRencanaBantuanController@update"]);
    $router->delete("/{id}", ['uses'=>"IntervensiRencanaBantuanController@delete"]);
    $router->get("/{id}", ['uses'=>"IntervensiRencanaBantuanController@get"]);
    $router->get("/", ['uses'=>"IntervensiRencanaBantuanController@gets"]);
});

//INTERVENSI REALISASI BANTUAN
$router->group(['prefix'=>'/intervensi_realisasi_bantuan', 'middleware'=>'auth'], function()use($router){
    $router->post("/", ['uses'=>"IntervensiRealisasiBantuanController@add"]);
    $router->put("/{id}", ['uses'=>"IntervensiRealisasiBantuanController@update"]);
    $router->delete("/{id}", ['uses'=>"IntervensiRealisasiBantuanController@delete"]);
    $router->get("/{id}", ['uses'=>"IntervensiRealisasiBantuanController@get"]);
    $router->get("/", ['uses'=>"IntervensiRealisasiBantuanController@gets"]);
});