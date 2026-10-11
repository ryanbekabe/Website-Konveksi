<?php

/**
 * Bahan baku. Lihat: admin/produksi/keuangan. Ubah data master: admin. Catat mutasi: admin/produksi.
 * Hak akses per rute diatur di app/routes.php.
 */
class AdminMaterialController extends Controller
{
    public function index(): void
    {
        $q = trim((string) $this->input('q', ''));
        $supplierId = (int) $this->input('supplier', 0);
        $menipis = $this->input('menipis') === '1';
        $this->view('admin/materials/index', [
            'title'     => 'Bahan Baku',
            'result'    => Material::paginate($q, $supplierId, $menipis, (int) $this->input('halaman', 1)),
            'suppliers' => Supplier::all(),
            'q' => $q, 'supplierId' => $supplierId, 'menipis' => $menipis,
            'lowCount'  => Material::countLow(),
        ], 'admin');
    }

    public function create(): void
    {
        $this->view('admin/materials/form', ['title' => 'Tambah Bahan', 'material' => null, 'suppliers' => Supplier::all()], 'admin');
    }

    public function store(): void
    {
        [$data, $initial] = $this->collect(null, '/admin/bahan/tambah');
        $id = Material::create($data, $initial);
        log_activity('tambah_bahan', 'bahan#' . $id, $data['nama']);
        flash('success', 'Bahan ditambahkan.');
        redirect("/admin/bahan/$id");
    }

    public function show(string $id): void
    {
        $material = Material::find((int) $id) ?? abort(404);
        $this->view('admin/materials/show', [
            'title'     => $material['nama'],
            'material'  => $material,
            'movements' => Material::movements((int) $material['id'], (int) $this->input('halaman', 1)),
            'canMove'   => Auth::hasRole(['admin', 'produksi']),
            'canEdit'   => Auth::hasRole(['admin']),
            'today'     => date('Y-m-d'),
        ], 'admin');
    }

    public function edit(string $id): void
    {
        $material = Material::find((int) $id) ?? abort(404);
        $this->view('admin/materials/form', ['title' => 'Ubah Bahan', 'material' => $material, 'suppliers' => Supplier::all()], 'admin');
    }

    public function update(string $id): void
    {
        $material = Material::find((int) $id) ?? abort(404);
        [$data] = $this->collect($material, "/admin/bahan/{$material['id']}/ubah");
        Material::update((int) $material['id'], $data);
        log_activity('ubah_bahan', 'bahan#' . $material['id'], $data['nama']);
        flash('success', 'Bahan diperbarui.');
        redirect("/admin/bahan/{$material['id']}");
    }

    public function destroy(string $id): void
    {
        $material = Material::find((int) $id) ?? abort(404);
        try {
            Material::delete((int) $material['id']);
            log_activity('hapus_bahan', 'bahan#' . $material['id'], $material['nama']);
            flash('success', 'Bahan dihapus.');
            redirect('/admin/bahan');
        } catch (DomainException $e) {
            flash('error', $e->getMessage());
            redirect("/admin/bahan/{$material['id']}");
        }
    }

    /** POST /admin/bahan/{id}/mutasi: aksi = masuk | keluar | koreksi */
    public function move(string $id): void
    {
        $material = Material::find((int) $id) ?? abort(404);
        $back = "/admin/bahan/{$material['id']}";
        $aksi = (string) $this->input('aksi', '');

        $tanggal = trim((string) $this->input('tanggal', date('Y-m-d')));
        $d = DateTime::createFromFormat('Y-m-d', $tanggal);
        if (!$d || $d->format('Y-m-d') !== $tanggal || $tanggal > date('Y-m-d')) {
            flash('error', 'Tanggal tidak valid (tidak boleh di masa depan).');
            redirect($back);
        }
        $ket = trim((string) $this->input('keterangan', ''));
        $raw = trim((string) $this->input($aksi === 'koreksi' ? 'stok_fisik' : 'qty', ''));
        if ($raw === '' || !is_numeric($raw)) {
            flash('error', 'Jumlah harus berupa angka.');
            redirect($back);
        }
        $number = (float) $raw;

        try {
            if ($aksi === 'koreksi') {
                if ($number < 0) {
                    throw new DomainException('Stok hasil hitung tidak boleh negatif.');
                }
                $new = Material::adjust((int) $material['id'], $number, $tanggal, $ket);
                $msg = 'Stok dikoreksi menjadi ';
            } elseif ($aksi === 'masuk' || $aksi === 'keluar') {
                $orderId = null;
                $kode = trim((string) $this->input('kode_order', ''));
                if ($aksi === 'keluar' && $kode !== '') {
                    $order = Order::findByCode(strtoupper($kode));
                    if (!$order) {
                        throw new DomainException('Kode pesanan tidak ditemukan.');
                    }
                    $orderId = (int) $order['id'];
                }
                $new = Material::move((int) $material['id'], $aksi, $number, $tanggal, $ket, $orderId);
                $msg = $aksi === 'masuk' ? 'Stok masuk dicatat. Stok sekarang ' : 'Pemakaian dicatat. Stok sekarang ';
            } else {
                throw new DomainException('Aksi tidak dikenal.');
            }
            log_activity('stok_' . $aksi, 'bahan#' . $material['id'], Material::fmt($number) . ' ' . $material['satuan']);
            flash('success', $msg . Material::fmt($new) . ' ' . $material['satuan'] . '.');
        } catch (DomainException $e) {
            flash('error', $e->getMessage());
        }
        redirect($back);
    }

    /** @return array{0: array, 1: float} [data bahan, stok awal] */
    private function collect(?array $existing, string $back): array
    {
        $v = new Validator($_POST, [
            'nama'         => 'required|max:100',
            'satuan'       => 'required|max:20',
            'stok_minimum' => 'required|numeric',
        ], ['nama' => 'Nama bahan', 'satuan' => 'Satuan', 'stok_minimum' => 'Stok minimum']);
        $errors = $v->errors();

        if (!isset($errors['stok_minimum']) && ((float) $_POST['stok_minimum'] < 0 || (float) $_POST['stok_minimum'] > 1000000)) {
            $errors['stok_minimum'] = 'Stok minimum harus antara 0 dan 1.000.000.';
        }
        $supplierId = (int) ($_POST['supplier_id'] ?? 0);
        if ($supplierId > 0 && !Supplier::find($supplierId)) {
            $errors['supplier'] = 'Supplier tidak ditemukan.';
        }
        $initial = 0.0;
        if ($existing === null && trim((string) ($_POST['stok_awal'] ?? '')) !== '') {
            if (!is_numeric($_POST['stok_awal']) || (float) $_POST['stok_awal'] < 0 || (float) $_POST['stok_awal'] > 1000000) {
                $errors['stok_awal'] = 'Stok awal harus angka antara 0 dan 1.000.000.';
            } else {
                $initial = round((float) $_POST['stok_awal'], 2);
            }
        }

        if ($errors) {
            with_old($_POST);
            foreach ($errors as $msg) {
                flash('error', $msg);
            }
            redirect($back);
        }
        return [[
            'nama'         => trim($_POST['nama']),
            'satuan'       => trim($_POST['satuan']),
            'stok_minimum' => round((float) $_POST['stok_minimum'], 2),
            'supplier_id'  => $supplierId > 0 ? $supplierId : null,
        ], $initial];
    }
}
