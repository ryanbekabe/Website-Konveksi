<?php

class CheckoutController extends Controller
{
    public function form(): void
    {
        $rows = Cart::detailed();
        if (!$rows) {
            flash('error', 'Keranjang masih kosong.');
            redirect('/keranjang');
        }
        $this->view('checkout/index', [
            'title'  => 'Checkout',
            'rows'   => $rows,
            'total'  => Cart::total($rows),
            'dp'     => round(Cart::total($rows) * (float) Setting::get('dp_persen', 50) / 100),
            'dpPersen' => (float) Setting::get('dp_persen', 50),
        ]);
    }

    public function place(): void
    {
        $rows = Cart::detailed();
        if (!$rows) {
            redirect('/keranjang');
        }

        [$ship, $errors] = Order::validateShipping($_POST);
        $design = null;
        if (!$errors) {
            try {
                $design = Upload::document($_FILES['desain'] ?? null, 'designs');
            } catch (RuntimeException $e) {
                $errors['desain'] = $e->getMessage();
            }
        }

        $order = null;
        if (!$errors) {
            try {
                $lines = array_map(fn($r) => [
                    'product_id' => (int) $r['product_id'], 'ukuran' => $r['ukuran'], 'warna' => $r['warna'], 'qty' => (int) $r['qty'],
                ], $rows);
                $order = Order::place((int) Auth::id(), $lines, $ship, mb_substr(trim((string) $this->input('catatan', '')), 0, 1000), $design);
            } catch (InvalidArgumentException $e) {
                $errors['order'] = $e->getMessage();
            }
        }

        if ($errors || !$order) {
            Upload::removePrivate('designs', $design);
            with_old($_POST);
            foreach ($errors as $msg) {
                flash('error', $msg);
            }
            redirect('/checkout');
        }

        Cart::clear();
        flash('success', 'Pesanan berhasil dibuat. Silakan lakukan pembayaran DP.');
        redirect('/pesanan/' . $order['kode_order']);
    }
}
