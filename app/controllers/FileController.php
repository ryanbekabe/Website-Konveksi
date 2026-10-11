<?php

/** Menyajikan berkas privat (desain, mockup, bukti bayar) setelah memeriksa hak akses. */
class FileController extends Controller
{
    public function design(string $id): void
    {
        $row = Database::one(
            'SELECT d.file, o.user_id FROM order_designs d JOIN orders o ON o.id = d.order_id WHERE d.id = ?',
            [(int) $id]
        );
        $this->serve($row, 'designs');
    }

    public function proof(string $id): void
    {
        $row = Database::one(
            'SELECT p.bukti AS file, o.user_id FROM payments p JOIN orders o ON o.id = p.order_id WHERE p.id = ?',
            [(int) $id]
        );
        $this->serve($row, 'payments');
    }

    private function serve(?array $row, string $subdir): void
    {
        $isStaff = Auth::hasRole(['admin', 'produksi', 'keuangan']);
        if (!$row || empty($row['file']) || (!$isStaff && (int) $row['user_id'] !== Auth::id())) {
            abort(404);
        }
        if (!preg_match('/^[a-f0-9]+\.(jpg|png|webp|pdf)$/', $row['file'])) {
            abort(404);
        }
        $path = BASE_DIR . "/storage/uploads/$subdir/" . $row['file'];
        if (!is_file($path)) {
            abort(404);
        }

        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($path);
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($path));
        header('Content-Disposition: inline; filename="' . $row['file'] . '"');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, max-age=3600');
        readfile($path);
        exit;
    }
}
