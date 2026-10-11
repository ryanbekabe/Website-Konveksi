<?php

/**
 * Keranjang disimpan di session (sederhana, cocok untuk shared hosting).
 * Struktur: $_SESSION['cart'][key] = ['product_id' => int, 'ukuran' => string, 'warna' => string, 'qty' => int]
 */
class Cart
{
    public static function items(): array
    {
        return Session::get('cart', []);
    }

    public static function add(int $productId, string $ukuran, string $warna, int $qty): void
    {
        $qty = max(1, min(10000, $qty));
        $cart = self::items();
        $key = md5($productId . '|' . $ukuran . '|' . $warna);
        if (isset($cart[$key])) {
            $cart[$key]['qty'] = min(10000, $cart[$key]['qty'] + $qty);
        } else {
            $cart[$key] = ['product_id' => $productId, 'ukuran' => $ukuran, 'warna' => $warna, 'qty' => $qty];
        }
        Session::set('cart', $cart);
    }

    public static function update(string $key, int $qty): void
    {
        $cart = self::items();
        if (isset($cart[$key])) {
            $cart[$key]['qty'] = max(1, min(10000, $qty));
            Session::set('cart', $cart);
        }
    }

    public static function remove(string $key): void
    {
        $cart = self::items();
        unset($cart[$key]);
        Session::set('cart', $cart);
    }

    public static function count(): int
    {
        return array_sum(array_column(self::items(), 'qty'));
    }

    /** Item keranjang beserta data produk, harga satuan (bertingkat), dan subtotal. */
    public static function detailed(): array
    {
        $rows = [];
        foreach (self::items() as $key => $item) {
            $product = Product::find($item['product_id']);
            if (!$product || !$product['aktif']) {
                self::remove($key);
                continue;
            }
            $unit = Product::unitPrice($product, $item['qty']);
            $rows[] = $item + [
                'key'      => $key,
                'product'  => $product,
                'unit'     => $unit,
                'subtotal' => $unit * $item['qty'],
            ];
        }
        return $rows;
    }

    public static function total(array $rows): float
    {
        return array_sum(array_column($rows, 'subtotal'));
    }
}
