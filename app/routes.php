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
$router->get('/admin/produk', 'AdminProductController@index', ['role:admin']);
$router->get('/admin/produk/tambah', 'AdminProductController@create', ['role:admin']);
$router->post('/admin/produk/tambah', 'AdminProductController@store', ['role:admin']);
$router->get('/admin/produk/{id}/ubah', 'AdminProductController@edit', ['role:admin']);
$router->post('/admin/produk/{id}/ubah', 'AdminProductController@update', ['role:admin']);
$router->post('/admin/produk/{id}/hapus', 'AdminProductController@destroy', ['role:admin']);

// Checkout & pesanan pelanggan
$router->get('/checkout', 'CheckoutController@form', ['auth']);
$router->post('/checkout', 'CheckoutController@place', ['auth']);
$router->get('/pesan/custom', 'CustomOrderController@form', ['auth']);
$router->post('/pesan/custom', 'CustomOrderController@store', ['auth']);
$router->get('/pesanan', 'OrderController@index', ['auth']);
$router->get('/pesanan/{kode}', 'OrderController@show', ['auth']);
$router->post('/pesanan/{kode}/bayar', 'OrderController@pay', ['auth']);
$router->post('/pesanan/{kode}/mockup', 'OrderController@mockup', ['auth']);
$router->post('/pesanan/{kode}/batal', 'OrderController@cancel', ['auth']);

// Berkas privat (hak akses diperiksa di controller)
$router->get('/berkas/desain/{id}', 'FileController@design', ['auth']);
$router->get('/berkas/bukti/{id}', 'FileController@proof', ['auth']);

// Admin: pesanan
$router->get('/admin/pesanan', 'AdminOrderController@index', ['admin']);
$router->get('/admin/pesanan/{kode}', 'AdminOrderController@show', ['admin']);
$router->post('/admin/pesanan/{kode}/status', 'AdminOrderController@updateStatus', ['role:admin,produksi']);
$router->post('/admin/pesanan/{kode}/mockup', 'AdminOrderController@uploadMockup', ['role:admin,produksi']);
$router->post('/admin/pesanan/{kode}/ongkir', 'AdminOrderController@setShipping', ['role:admin,keuangan']);
$router->post('/admin/pembayaran/{id}/verifikasi', 'AdminOrderController@verifyPayment', ['role:admin,keuangan']);

// Dokumen cetak
$router->get('/pesanan/{kode}/invoice', 'DocumentController@invoice', ['auth']);
$router->get('/admin/pesanan/{kode}/spk', 'DocumentController@spk', ['admin']);
$router->post('/admin/pesanan/{kode}/estimasi', 'AdminOrderController@setEstimate', ['role:admin,produksi']);

// Produksi
$router->get('/admin/produksi', 'AdminProductionController@board', ['role:admin,produksi']);
$router->post('/admin/produksi/{id}', 'AdminProductionController@update', ['role:admin,produksi']);
