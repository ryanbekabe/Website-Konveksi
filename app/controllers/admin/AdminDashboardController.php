<?php

class AdminDashboardController extends Controller
{
    public function index(): void
    {
        $stats = [
            'produk'    => (int) Database::value('SELECT COUNT(*) FROM products'),
            'pelanggan' => (int) Database::value("SELECT COUNT(*) FROM users WHERE role = 'pelanggan'"),
            'pesanan'   => (int) Database::value('SELECT COUNT(*) FROM orders'),
            'omzet'     => (float) Database::value("SELECT COALESCE(SUM(total), 0) FROM orders WHERE status = 'selesai'"),
            'stok_menipis' => (int) Database::value('SELECT COUNT(*) FROM materials WHERE stok <= stok_minimum'),
        ] + ProductionStage::countActive();

        $this->view('admin/dashboard', [
            'title'  => 'Dashboard',
            'defaultPassword' => User::usesDefaultPassword((array) Auth::user()),
            'stats'  => $stats,
            'orders' => Database::all(
                'SELECT o.kode_order, o.status, o.total, o.created_at, u.nama
                 FROM orders o JOIN users u ON u.id = o.user_id
                 ORDER BY o.id DESC LIMIT 5'
            ),
        ], 'admin');
    }
}
