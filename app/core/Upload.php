<?php

class Upload
{
    /**
     * Simpan gambar dari $_FILES[...] ke public/uploads/$subdir.
     * Mengembalikan nama file, atau melempar RuntimeException bila tidak valid.
     * Mengembalikan null bila tidak ada file yang diunggah.
     */
    public static function image(?array $file, string $subdir): ?string
    {
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Gagal mengunggah file.');
        }

        $cfg = $GLOBALS['config']['upload'];
        if ($file['size'] > $cfg['max_size']) {
            throw new RuntimeException('Ukuran file melebihi ' . round($cfg['max_size'] / 1048576, 1) . ' MB.');
        }

        // Validasi MIME dari isi file, bukan dari ekstensi / header klien.
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if (!isset($cfg['image_mime'][$mime]) || @getimagesize($file['tmp_name']) === false) {
            throw new RuntimeException('Format file harus JPG, PNG, atau WebP.');
        }

        $dir = BASE_DIR . '/public/uploads/' . $subdir;
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            throw new RuntimeException('Folder upload tidak dapat dibuat.');
        }

        $name = bin2hex(random_bytes(12)) . '.' . $cfg['image_mime'][$mime];
        if (!move_uploaded_file($file['tmp_name'], "$dir/$name")) {
            throw new RuntimeException('File tidak dapat disimpan.');
        }
        return $name;
    }

    public static function remove(string $subdir, ?string $name): void
    {
        if ($name && preg_match('/^[a-f0-9]+\.(jpg|png|webp)$/', $name)) {
            @unlink(BASE_DIR . "/public/uploads/$subdir/$name");
        }
    }
}
