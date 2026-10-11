<?php

abstract class Controller
{
    /** Render view di dalam layout. $view relatif terhadap app/views tanpa .php */
    protected function view(string $view, array $data = [], string $layout = 'main'): void
    {
        $file = BASE_DIR . "/app/views/$view.php";
        if (!is_file($file)) {
            abort(500, "View tidak ditemukan: $view");
        }

        extract($data, EXTR_SKIP);
        ob_start();
        require $file;
        $content = ob_get_clean();

        require BASE_DIR . "/app/views/layouts/$layout.php";
    }

    protected function input(string $key, $default = null)
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }
}
