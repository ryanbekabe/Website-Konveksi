<?php

/** Dokumen cetak: invoice (pemilik pesanan / staf) dan SPK (khusus staf). */
class DocumentController extends Controller
{
    public function invoice(string $kode): void
    {
        $order = Order::findByCode($kode) ?? abort(404);
        $isStaff = Auth::hasRole(['admin', 'produksi', 'keuangan']);
        if (!$isStaff && (int) $order['user_id'] !== Auth::id()) {
            abort(404);
        }

        $id = (int) $order['id'];
        $paid = Order::paidTotal($id);
        $this->view('documents/invoice', [
            'title'    => 'Invoice ' . $order['kode_order'],
            'order'    => $order,
            'items'    => Order::items($id),
            'payments' => array_values(array_filter(Order::payments($id), fn($p) => $p['status'] === 'terverifikasi')),
            'paid'     => $paid,
            'sisa'     => max(0, (float) $order['total'] - $paid),
            'usaha'    => $this->business(),
            'back'     => $isStaff ? "/admin/pesanan/$kode" : "/pesanan/$kode",
        ], 'print');
    }

    public function spk(string $kode): void
    {
        $order = Order::findByCode($kode) ?? abort(404);
        $id = (int) $order['id'];
        $items = Order::items($id);

        // Matriks jumlah: baris = produk + warna, kolom = ukuran.
        $groups = [];
        $present = [];
        foreach ($items as $i) {
            $size = trim((string) ($i['spec']['ukuran'] ?? '')) ?: '-';
            $color = trim((string) ($i['spec']['warna'] ?? '')) ?: '-';
            $key = $i['nama'] . '|' . $color;
            $groups[$key]['nama'] = $i['nama'];
            $groups[$key]['warna'] = $color;
            $groups[$key]['sizes'][$size] = ($groups[$key]['sizes'][$size] ?? 0) + (int) $i['qty'];
            $present[$size] = true;
        }
        // Urutan kolom: ukuran baku dulu, sisanya menyusul.
        $columns = array_values(array_filter(Order::SIZES, fn($s) => isset($present[$s])));
        foreach (array_keys($present) as $s) {
            if (!in_array($s, $columns, true)) {
                $columns[] = $s;
            }
        }

        $this->view('documents/spk', [
            'title'   => 'SPK ' . $order['kode_order'],
            'order'   => $order,
            'groups'  => array_values($groups),
            'columns' => $columns,
            'designs' => Order::designs($id),
            'stages'  => Database::all('SELECT tahap FROM production_stages WHERE order_id = ? ORDER BY id', [$id]) ?: array_map(fn($t) => ['tahap' => $t], Order::STAGES),
            'usaha'   => $this->business(),
        ], 'print');
    }

    private function business(): array
    {
        return [
            'nama'     => Setting::get('nama_usaha', config('app.name')),
            'alamat'   => Setting::get('alamat_usaha', ''),
            'telepon'  => Setting::get('telepon_usaha', ''),
            'rekening' => Setting::get('rekening_bank', ''),
        ];
    }
}
