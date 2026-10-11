<?php

class HomeController extends Controller
{
    public function index(): void
    {
        $this->view('home/index', [
            'title'      => config('app.name') . ' - Jasa Konveksi & Sablon',
            'products'   => Product::latest(4),
            'categories' => Category::all(),
        ]);
    }
}
