<?php

/** Pesanan milik pelanggan: daftar, detail, pembayaran, respons mockup, pembatalan. */
class OrderController extends Controller
{
    public function index(): void
    {
        $this->view('orders/index', [
            'title'  => 'Pesanan Saya',
            'orders' => Order::forUser((int) Auth::id()),
        ]);
    }

    public function show(string $kode): void
    {
        $order = $this->own($kode);
        $id = (int) $order['id'];
        $mockups = Order::designs($id, 'mockup');
        $this->view('orders/show', [
            'title'    => 'Pesanan ' . $order['kode_order'],
            'order'    => $order,
            'items'    => Order::items($id),
            'designs'  => Order::designs($id, 'desain'),
            'mockups'  => $mockups,
            'mockup'   => $mockups ? end($mockups) : null,
            'payments' => Order::payments($id),
            'stages'   => ProductionStage::forOrder($id),
            'shipment' => Order::shipment($id),
            'next'     => Order::nextPayment($order),
            'paid'     => Order::paidTotal($id),
            'rekening' => Setting::get('rekening_bank', ''),
        ]);
    }

    public function pay(string $kode): void
    {
        $order = $this->own($kode);
        $next = Order::nextPayment($order);
        if (!$next) {
            flash('error', 'Tidak ada pembayaran yang perlu dilakukan saat ini.');
            redirect('/pesanan/' . $kode);
        }
        $pending = Database::value("SELECT COUNT(*) FROM payments WHERE order_id = ? AND jenis = ? AND status = 'menunggu'", [$order['id'], $next['jenis']]);
        if ($pending) {
            flash('error', 'Bukti pembayaran Anda sedang diverifikasi.');
            redirect('/pesanan/' . $kode);
        }

        try {
            $file = Upload::document($_FILES['bukti'] ?? null, 'payments');
            if ($file === null) {
                throw new RuntimeException('Bukti transfer wajib diunggah.');
            }
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
            redirect('/pesanan/' . $kode);
        }

        Database::insert('payments', [
            'order_id' => $order['id'],
            'jenis'    => $next['jenis'],
            'metode'   => 'transfer',
            'jumlah'   => $next['jumlah'],
            'bukti'    => $file,
        ]);
        notify_admins("Bukti {$next['jenis']} masuk untuk pesanan {$order['kode_order']}");
        flash('success', 'Bukti pembayaran terkirim dan menunggu verifikasi.');
        redirect('/pesanan/' . $kode);
    }

    /** Pelanggan menyetujui atau meminta revisi mockup terbaru. */
    public function mockup(string $kode): void
    {
        $order = $this->own($kode);
        $mockups = Order::designs((int) $order['id'], 'mockup');
        $latest = $mockups ? end($mockups) : null;
        if ($order['status'] !== 'menunggu_persetujuan' || !$latest) {
            flash('error', 'Tidak ada mockup yang menunggu persetujuan.');
            redirect('/pesanan/' . $kode);
        }

        if ($this->input('aksi') === 'setuju') {
            Database::update('order_designs', ['status_approval' => 'disetujui'], 'id = ?', [$latest['id']]);
            Order::setStatus((int) $order['id'], 'produksi');
            notify_admins("Mockup pesanan $kode disetujui. Siap produksi.");
            flash('success', 'Mockup disetujui. Pesanan masuk produksi.');
        } else {
            $catatan = trim((string) $this->input('catatan', ''));
            if ($catatan === '') {
                flash('error', 'Tuliskan catatan revisi.');
                redirect('/pesanan/' . $kode);
            }
            Database::update('order_designs', ['status_approval' => 'revisi', 'catatan' => mb_substr($catatan, 0, 1000)], 'id = ?', [$latest['id']]);
            Order::setStatus((int) $order['id'], 'desain_mockup');
            notify_admins("Revisi mockup diminta untuk pesanan $kode");
            flash('success', 'Permintaan revisi terkirim.');
        }
        redirect('/pesanan/' . $kode);
    }

    public function cancel(string $kode): void
    {
        $order = $this->own($kode);
        if ($order['status'] !== 'menunggu_pembayaran') {
            flash('error', 'Pesanan hanya dapat dibatalkan sebelum DP dibayar. Hubungi kami via WhatsApp.');
            redirect('/pesanan/' . $kode);
        }
        Order::setStatus((int) $order['id'], 'dibatalkan');
        notify_admins("Pesanan $kode dibatalkan pelanggan");
        flash('success', 'Pesanan dibatalkan.');
        redirect('/pesanan/' . $kode);
    }

    /** Pesanan harus milik pengguna yang login (404 agar tidak membocorkan keberadaan pesanan). */
    private function own(string $kode): array
    {
        $order = Order::findByCode($kode);
        if (!$order || (int) $order['user_id'] !== Auth::id()) {
            abort(404);
        }
        return $order;
    }
}
