<?php

/** Manajemen pesanan oleh staf. Hak akses per aksi diatur di app/routes.php. */
class AdminOrderController extends Controller
{
    public function index(): void
    {
        $status = (string) $this->input('status', '');
        $q = trim((string) $this->input('q', ''));
        $this->view('admin/orders/index', [
            'title'  => 'Pesanan',
            'result' => Order::paginate($status, $q, (int) $this->input('halaman', 1)),
            'status' => $status,
            'q'      => $q,
        ], 'admin');
    }

    public function show(string $kode): void
    {
        $order = $this->find($kode);
        $id = (int) $order['id'];
        $this->view('admin/orders/show', [
            'title'    => 'Pesanan ' . $order['kode_order'],
            'order'    => $order,
            'items'    => Order::items($id),
            'designs'  => Order::designs($id, 'desain'),
            'mockups'  => Order::designs($id, 'mockup'),
            'payments' => Order::payments($id),
            'shipment' => Order::shipment($id),
            'stages'   => Database::all('SELECT * FROM production_stages WHERE order_id = ? ORDER BY id', [$id]),
            'paid'     => Order::paidTotal($id),
            'next'     => Order::allowedNext($order['status']),
        ], 'admin');
    }

    public function updateStatus(string $kode): void
    {
        $order = $this->find($kode);
        $target = (string) $this->input('status', '');
        if (!in_array($target, Order::allowedNext($order['status']), true)) {
            flash('error', 'Perpindahan status tidak diizinkan.');
            redirect("/admin/pesanan/$kode");
        }

        if ($target === 'dikirim') {
            $kurir = trim((string) $this->input('kurir', ''));
            $resi = trim((string) $this->input('resi', ''));
            if ($kurir === '' || $resi === '') {
                flash('error', 'Kurir dan nomor resi wajib diisi.');
                redirect("/admin/pesanan/$kode");
            }
            $data = ['kurir' => mb_substr($kurir, 0, 50), 'resi' => mb_substr($resi, 0, 80), 'status' => 'dikirim'];
            if (Order::shipment((int) $order['id'])) {
                Database::update('shipments', $data, 'order_id = ?', [$order['id']]);
            } else {
                Database::insert('shipments', $data + ['order_id' => $order['id']]);
            }
        }

        Order::setStatus((int) $order['id'], $target);
        notify((int) $order['user_id'], "Status pesanan $kode: " . Order::label($target));
        log_activity('ubah_status', $kode, $order['status'] . ' -> ' . $target);
        flash('success', 'Status diperbarui: ' . Order::label($target));
        redirect("/admin/pesanan/$kode");
    }

    /** Terima / tolak bukti pembayaran. */
    public function verifyPayment(string $id): void
    {
        $payment = Database::one('SELECT * FROM payments WHERE id = ?', [(int) $id]) ?? abort(404);
        $order = Database::one('SELECT * FROM orders WHERE id = ?', [$payment['order_id']]) ?? abort(404);
        if ($payment['status'] !== 'menunggu') {
            flash('error', 'Pembayaran ini sudah diproses.');
            redirect('/admin/pesanan/' . $order['kode_order']);
        }

        if ($this->input('aksi') === 'terima') {
            Database::update('payments', [
                'status'       => 'terverifikasi',
                'verified_by'  => Auth::id(),
                'dibayar_pada' => date('Y-m-d H:i:s'),
            ], 'id = ?', [$payment['id']]);

            if ($payment['jenis'] === 'dp' && $order['status'] === 'menunggu_pembayaran') {
                Order::setStatus((int) $order['id'], 'dp_dibayar');
            } elseif ($payment['jenis'] === 'pelunasan' && $order['status'] === 'menunggu_pelunasan') {
                Order::setStatus((int) $order['id'], 'siap_kirim');
            }
            notify((int) $order['user_id'], "Pembayaran {$payment['jenis']} pesanan {$order['kode_order']} terverifikasi.");
            log_activity('verifikasi_bayar', $order['kode_order'], $payment['jenis'] . ' diterima');
            flash('success', 'Pembayaran diterima.');
        } else {
            Database::update('payments', ['status' => 'ditolak', 'verified_by' => Auth::id()], 'id = ?', [$payment['id']]);
            notify((int) $order['user_id'], "Bukti pembayaran pesanan {$order['kode_order']} ditolak. Silakan unggah ulang.");
            log_activity('verifikasi_bayar', $order['kode_order'], $payment['jenis'] . ' ditolak');
            flash('success', 'Pembayaran ditolak.');
        }
        redirect('/admin/pesanan/' . $order['kode_order']);
    }

    public function uploadMockup(string $kode): void
    {
        $order = $this->find($kode);
        if (!in_array($order['status'], ['dp_dibayar', 'desain_mockup', 'menunggu_persetujuan'], true)) {
            flash('error', 'Mockup hanya dapat diunggah setelah DP terverifikasi dan sebelum produksi.');
            redirect("/admin/pesanan/$kode");
        }
        try {
            $file = Upload::document($_FILES['mockup'] ?? null, 'designs');
            if ($file === null) {
                throw new RuntimeException('Pilih file mockup.');
            }
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
            redirect("/admin/pesanan/$kode");
        }

        Database::insert('order_designs', ['order_id' => $order['id'], 'file' => $file, 'tipe' => 'mockup']);
        Order::setStatus((int) $order['id'], 'menunggu_persetujuan');
        notify((int) $order['user_id'], "Mockup pesanan $kode siap. Mohon periksa dan setujui.");
        log_activity('upload_mockup', $kode);
        flash('success', 'Mockup terkirim ke pelanggan.');
        redirect("/admin/pesanan/$kode");
    }

    public function setShipping(string $kode): void
    {
        $order = $this->find($kode);
        if (in_array($order['status'], ['dikirim', 'selesai', 'dibatalkan'], true)) {
            flash('error', 'Ongkir tidak dapat diubah pada status ini.');
            redirect("/admin/pesanan/$kode");
        }
        $ongkir = (float) $this->input('ongkir', 0);
        if ($ongkir < 0 || $ongkir > 100000000) {
            flash('error', 'Nilai ongkir tidak valid.');
            redirect("/admin/pesanan/$kode");
        }
        Database::update('orders', [
            'ongkir' => $ongkir,
            'total'  => (float) $order['subtotal'] + $ongkir - (float) $order['diskon'],
        ], 'id = ?', [$order['id']]);
        log_activity('set_ongkir', $kode, (string) $ongkir);
        flash('success', 'Ongkir diperbarui.');
        redirect("/admin/pesanan/$kode");
    }

    private function find(string $kode): array
    {
        return Order::findByCode($kode) ?? abort(404);
    }
}
