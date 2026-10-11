<?php

class CartController extends Controller
{
    public function index(): void
    {
        $rows = Cart::detailed();
        $this->view('cart/index', [
            'title' => 'Keranjang',
            'rows'  => $rows,
            'total' => Cart::total($rows),
        ]);
    }

    public function add(): void
    {
        $product = Product::find((int) $this->input('product_id', 0));
        if (!$product || !$product['aktif']) {
            abort(404);
        }
        Cart::add(
            (int) $product['id'],
            mb_substr(trim((string) $this->input('ukuran', '')), 0, 20),
            mb_substr(trim((string) $this->input('warna', '')), 0, 30),
            (int) $this->input('qty', 1)
        );
        flash('success', 'Produk ditambahkan ke keranjang.');
        redirect('/keranjang');
    }

    public function update(): void
    {
        Cart::update((string) $this->input('key', ''), (int) $this->input('qty', 1));
        redirect('/keranjang');
    }

    public function remove(): void
    {
        Cart::remove((string) $this->input('key', ''));
        redirect('/keranjang');
    }
}
