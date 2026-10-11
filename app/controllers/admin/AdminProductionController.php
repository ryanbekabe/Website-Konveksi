<?php

/** Papan produksi dan aksi per tahap. Hanya admin dan staf produksi (lihat app/routes.php). */
class AdminProductionController extends Controller
{
    public function board(): void
    {
        $this->view('admin/production/board', [
            'title'  => 'Produksi',
            'board'  => ProductionStage::board(),
            'mine'   => ProductionStage::assignedTo((int) Auth::id()),
        ], 'admin');
    }

    /** POST /admin/produksi/{id}: aksi = simpan | mulai | selesai | reset */
    public function update(string $id): void
    {
        $stage = ProductionStage::find((int) $id) ?? abort(404);
        $order = Database::one('SELECT kode_order FROM orders WHERE id = ?', [$stage['order_id']]) ?? abort(404);

        $petugas = $this->input('petugas_id', '');
        $petugasId = ($petugas === '' || $petugas === null) ? null : (int) $petugas;

        try {
            $msg = ProductionStage::apply(
                (int) $stage['id'],
                (string) $this->input('aksi', ''),
                $petugasId,
                trim((string) $this->input('catatan', '')),
                Auth::user()
            );
            flash('success', $msg);
        } catch (DomainException $e) {
            flash('error', $e->getMessage());
        }

        // Kembali ke halaman asal (whitelist, bukan URL dari input).
        redirect($this->input('kembali') === 'papan' ? '/admin/produksi' : '/admin/pesanan/' . $order['kode_order']);
    }
}
