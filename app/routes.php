<?php
/** @var Router $router */

// Publik
$router->get('/', 'HomeController@index');
$router->get('/produk', 'ProductController@index');
$router->get('/produk/{slug}', 'ProductController@show');

// Auth
$router->get('/masuk', 'AuthController@loginForm', ['guest']);
$router->post('/masuk', 'AuthController@login', ['guest']);
$router->get('/daftar', 'AuthController@registerForm', ['guest']);
$router->post('/daftar', 'AuthController@register', ['guest']);
$router->post('/keluar', 'AuthController@logout');

// Keranjang
$router->get('/keranjang', 'CartController@index');
$router->post('/keranjang/tambah', 'CartController@add');
$router->post('/keranjang/ubah', 'CartController@update');
$router->post('/keranjang/hapus', 'CartController@remove');

// Admin
$router->get('/admin', 'AdminDashboardController@index', ['admin']);
$router->get('/admin/produk', 'AdminProductController@index', ['admin']);
$router->get('/admin/produk/tambah', 'AdminProductController@create', ['admin']);
$router->post('/admin/produk/tambah', 'AdminProductController@store', ['admin']);
$router->get('/admin/produk/{id}/ubah', 'AdminProductController@edit', ['admin']);
$router->post('/admin/produk/{id}/ubah', 'AdminProductController@update', ['admin']);
$router->post('/admin/produk/{id}/hapus', 'AdminProductController@destroy', ['admin']);
