<?php

/** Form pemesanan custom: produk + tabel ukuran + desain + alamat dalam satu halaman. */
class CustomOrderController extends Controller
{
    public function form(): void
    {
        $products = Database::all("SELECT id, nama, slug, harga_dasar FROM products WHERE aktif = 1 AND tipe = 'custom' ORDER BY nama");
        $selected = 0;
        if (isset($_GET['produk'])) {
            foreach ($products as $p) {
                if ($p['slug'] === $_GET['produk']) {
                    $selected = (int) $p['id'];
                }
            }
        }
        $this->view('orders/custom', [
            'title'    => 'Pesan Custom',
            'products' => $products,
            'selected' => (int) old('product_id', $selected),
            'sizes'    => Order::SIZES,
            'minQty'   => (int) config('app.min_qty_custom', 12),
        ]);
    }

    public function store(): void
    {
        $errors = [];
        $product = Product::find((int) $this->input('product_id', 0));
        if (!$product || !$product['aktif'] || $product['tipe'] !== 'custom') {
            $errors['product'] = 'Pilih produk yang valid.';
        }

        $warna = mb_substr(trim((string) $this->input('warna', '')), 0, 30);
        if ($warna === '') {
            $errors['warna'] = 'Warna wajib diisi.';
        }

        // Tabel ukuran: satu baris pesanan per ukuran yang jumlahnya > 0.
        $lines = [];
        $qtyInput = $_POST['qty'] ?? [];
        foreach (Order::SIZES as $size) {
            $qty = is_array($qtyInput) ? (int) ($qtyInput[$size] ?? 0) : 0;
            if ($qty < 0 || $qty > 10000) {
                $errors['qty'] = 'Jumlah per ukuran harus antara 0 dan 10.000.';
            } elseif ($qty > 0 && $product) {
                $lines[] = ['product_id' => (int) $product['id'], 'ukuran' => $size, 'warna' => $warna, 'qty' => $qty];
            }
        }
        if (!$lines && !isset($errors['qty'])) {
            $errors['qty'] = 'Isi jumlah minimal pada satu ukuran.';
        }

        [$ship, $shipErrors] = Order::validateShipping($_POST);
        $errors += $shipErrors;

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
            redirect('/pesan/custom');
        }

        flash('success', 'Pesanan custom berhasil dibuat. Silakan lakukan pembayaran DP.');
        redirect('/pesanan/' . $order['kode_order']);
    }
}
