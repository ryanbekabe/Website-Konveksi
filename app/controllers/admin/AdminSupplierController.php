<?php

/** Supplier bahan baku (khusus admin; lihat app/routes.php). */
class AdminSupplierController extends Controller
{
    public function index(): void
    {
        $this->view('admin/suppliers/index', ['title' => 'Supplier', 'suppliers' => Supplier::all()], 'admin');
    }

    public function create(): void
    {
        $this->view('admin/suppliers/form', ['title' => 'Tambah Supplier', 'supplier' => null], 'admin');
    }

    public function store(): void
    {
        $id = Supplier::create($this->collect('/admin/supplier/tambah'));
        log_activity('tambah_supplier', 'supplier#' . $id);
        flash('success', 'Supplier ditambahkan.');
        redirect('/admin/supplier');
    }

    public function edit(string $id): void
    {
        $supplier = Supplier::find((int) $id) ?? abort(404);
        $this->view('admin/suppliers/form', ['title' => 'Ubah Supplier', 'supplier' => $supplier], 'admin');
    }

    public function update(string $id): void
    {
        $supplier = Supplier::find((int) $id) ?? abort(404);
        Supplier::update((int) $supplier['id'], $this->collect("/admin/supplier/{$supplier['id']}/ubah"));
        log_activity('ubah_supplier', 'supplier#' . $supplier['id']);
        flash('success', 'Supplier diperbarui.');
        redirect('/admin/supplier');
    }

    public function destroy(string $id): void
    {
        $supplier = Supplier::find((int) $id) ?? abort(404);
        Supplier::delete((int) $supplier['id']);
        log_activity('hapus_supplier', 'supplier#' . $supplier['id'], $supplier['nama']);
        flash('success', 'Supplier dihapus. Bahan terkait kini tanpa supplier.');
        redirect('/admin/supplier');
    }

    private function collect(string $back): array
    {
        $v = new Validator($_POST, ['nama' => 'required|max:100', 'kontak' => 'max:100', 'alamat' => 'max:500'],
            ['nama' => 'Nama supplier', 'kontak' => 'Kontak', 'alamat' => 'Alamat']);
        if ($v->fails()) {
            with_old($_POST);
            foreach ($v->errors() as $msg) {
                flash('error', $msg);
            }
            redirect($back);
        }
        return [
            'nama'   => trim($_POST['nama']),
            'kontak' => trim((string) ($_POST['kontak'] ?? '')),
            'alamat' => trim((string) ($_POST['alamat'] ?? '')),
        ];
    }
}
