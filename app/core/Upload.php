<?php

class Upload
{
    /**
     * Simpan gambar publik dari $_FILES[...] ke public/uploads/$subdir.
     * Mengembalikan nama file, atau null bila tidak ada file yang diunggah.
     * Melempar RuntimeException bila file tidak valid.
     */
    public static function image(?array $file, string $subdir): ?string
    {
        $cfg = $GLOBALS['config']['upload'];
        return self::save($file, PUBLIC_DIR . '/uploads/' . $subdir, $cfg['image_mime'], $cfg['max_size'], true);
    }

    /**
     * Simpan dokumen PRIVAT (desain, mockup, bukti bayar) ke storage/uploads/$subdir.
     * Berkas tidak dapat diakses langsung lewat URL; sajikan lewat FileController.
     * Menerima JPG/PNG/WebP/PDF.
     */
    public static function document(?array $file, string $subdir): ?string
    {
        $cfg = $GLOBALS['config']['upload'];
        $mimes = $cfg['document_mime'] ?? ($cfg['image_mime'] + ['application/pdf' => 'pdf']);
        $max = $cfg['document_max_size'] ?? (5 * 1024 * 1024);
        return self::save($file, BASE_DIR . '/storage/uploads/' . $subdir, $mimes, $max, false);
    }

    private static function save(?array $file, string $dir, array $mimes, int $maxSize, bool $imageOnly): ?string
    {
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
            throw new RuntimeException('Ukuran file melebihi batas server.');
        }
        if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
            throw new RuntimeException('Gagal mengunggah file.');
        }
        if ($file['size'] > $maxSize) {
            throw new RuntimeException('Ukuran file melebihi ' . round($maxSize / 1048576, 1) . ' MB.');
        }

        // Validasi tipe dari isi file, bukan dari ekstensi / header klien.
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if (!isset($mimes[$mime])) {
            throw new RuntimeException('Format file tidak didukung (' . implode('/', array_unique($mimes)) . ').');
        }
        if ($mime === 'application/pdf') {
            $head = file_get_contents($file['tmp_name'], false, null, 0, 5);
            if ($head !== '%PDF-') {
                throw new RuntimeException('Berkas PDF tidak valid.');
            }
        } elseif (@getimagesize($file['tmp_name']) === false) {
            throw new RuntimeException('Berkas gambar tidak valid.');
        }

        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            throw new RuntimeException('Folder upload tidak dapat dibuat.');
        }

        $name = bin2hex(random_bytes(12)) . '.' . $mimes[$mime];
        if (!move_uploaded_file($file['tmp_name'], "$dir/$name")) {
            throw new RuntimeException('File tidak dapat disimpan.');
        }
        return $name;
    }

    public static function remove(string $subdir, ?string $name): void
    {
        if ($name && preg_match('/^[a-f0-9]+\.(jpg|png|webp)$/', $name)) {
            @unlink(PUBLIC_DIR . "/uploads/$subdir/$name");
        }
    }

    public static function removePrivate(string $subdir, ?string $name): void
    {
        if ($name && preg_match('/^[a-f0-9]+\.(jpg|png|webp|pdf)$/', $name)) {
            @unlink(BASE_DIR . "/storage/uploads/$subdir/$name");
        }
    }
}
