<?php

class ProductController extends Controller
{
    public function index(): void
    {
        $filters = [
            'q'           => trim((string) $this->input('q', '')),
            'category'    => trim((string) $this->input('kategori', '')),
            'only_active' => true,
        ];
        $result = Product::paginate($filters, (int) $this->input('halaman', 1));

        $this->view('products/index', [
            'title'      => 'Katalog Produk',
            'result'     => $result,
            'filters'    => $filters,
            'categories' => Category::all(),
        ]);
    }

    public function show(string $slug): void
    {
        $product = Product::findBySlug($slug);
        if (!$product) {
            abort(404);
        }

        $variants = Product::variants((int) $product['id']);
        $this->view('products/show', [
            'title'    => $product['nama'],
            'product'  => $product,
            'tiers'    => Product::priceTiers((int) $product['id']),
            'sizes'    => array_values(array_unique(array_column($variants, 'ukuran'))) ?: ['S', 'M', 'L', 'XL'],
            'colors'   => array_values(array_unique(array_column($variants, 'warna'))),
        ]);
    }
}
