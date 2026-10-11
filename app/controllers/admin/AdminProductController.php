<?php

class AdminProductController extends Controller
{
    public function index(): void
    {
        $result = Product::paginate(['q' => trim((string) $this->input('q', ''))], (int) $this->input('halaman', 1), 20);
        $this->view('admin/products/index', [
            'title'  => 'Produk',
            'result' => $result,
            'q'      => trim((string) $this->input('q', '')),
        ], 'admin');
    }

    public function create(): void
    {
        $this->view('admin/products/form', [
            'title'      => 'Tambah Produk',
            'product'    => null,
            'categories' => Category::all(),
        ], 'admin');
    }

    public function store(): void
    {
        [$data, $image] = $this->collect(null);
        $id = Product::create($data, $image);
        flash('success', 'Produk berhasil ditambahkan.');
        redirect('/admin/produk');
    }

    public function edit(string $id): void
    {
        $product = Product::find((int) $id) ?? abort(404);
        $this->view('admin/products/form', [
            'title'      => 'Ubah Produk',
            'product'    => $product,
            'categories' => Category::all(),
        ], 'admin');
    }

    public function update(string $id): void
    {
        $product = Product::find((int) $id) ?? abort(404);
        [$data, $image] = $this->collect($product);
        Product::update((int) $product['id'], $data, $image);
        flash('success', 'Produk berhasil diperbarui.');
        redirect('/admin/produk');
    }

    public function destroy(string $id): void
    {
        $product = Product::find((int) $id) ?? abort(404);
        try {
            Product::delete((int) $product['id']);
            flash('success', 'Produk dihapus.');
        } catch (PDOException $e) {
            // Produk yang sudah dipakai di pesanan tidak boleh dihapus (foreign key).
            flash('error', 'Produk tidak dapat dihapus karena sudah dipakai pada pesanan. Nonaktifkan saja.');
        }
        redirect('/admin/produk');
    }

    /** Validasi input & unggahan; redirect balik ke form bila gagal. */
    private function collect(?array $existing): array
    {
        $v = new Validator($_POST, [
            'nama'        => 'required|max:150',
            'category_id' => 'required|numeric',
            'bahan'       => 'max:100',
            'harga_dasar' => 'required|numeric',
            'tipe'        => 'required|in:ready,custom',
        ], ['nama' => 'Nama produk', 'category_id' => 'Kategori', 'bahan' => 'Bahan', 'harga_dasar' => 'Harga dasar', 'tipe' => 'Tipe']);

        $errors = $v->errors();
        if (!isset($errors['harga_dasar']) && (float) $_POST['harga_dasar'] < 0) {
            $errors['harga_dasar'] = 'Harga dasar tidak boleh negatif.';
        }
        if (!isset($errors['category_id']) && !Database::value('SELECT id FROM categories WHERE id = ?', [(int) $_POST['category_id']])) {
            $errors['category_id'] = 'Kategori tidak ditemukan.';
        }

        $image = null;
        if (!$errors) {
            try {
                $image = Upload::image($_FILES['gambar'] ?? null, 'products');
            } catch (RuntimeException $e) {
                $errors['gambar'] = $e->getMessage();
            }
        }

        if ($errors) {
            with_old($_POST);
            foreach ($errors as $msg) {
                flash('error', $msg);
            }
            redirect($existing ? "/admin/produk/{$existing['id']}/ubah" : '/admin/produk/tambah');
        }

        $nama = trim($_POST['nama']);
        $data = [
            'category_id' => (int) $_POST['category_id'],
            'nama'        => $nama,
            'slug'        => Product::uniqueSlug($nama, $existing ? (int) $existing['id'] : null),
            'deskripsi'   => trim((string) ($_POST['deskripsi'] ?? '')),
            'bahan'       => trim((string) ($_POST['bahan'] ?? '')),
            'harga_dasar' => (float) $_POST['harga_dasar'],
            'tipe'        => $_POST['tipe'],
            'aktif'       => isset($_POST['aktif']) ? 1 : 0,
        ];
        return [$data, $image];
    }
}
