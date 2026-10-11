<?php

/** Ekspor CSV (UTF-8 dengan BOM agar Excel membaca karakter dengan benar). */
class Csv
{
    /**
     * Kirim $rows sebagai unduhan lalu hentikan eksekusi.
     * @param array $header daftar judul kolom
     * @param iterable $rows  baris data (array nilai)
     * @param string $sep     ',' atau ';' (Excel berlokal Indonesia umumnya memakai ';')
     */
    public static function download(string $filename, array $header, iterable $rows, string $sep = ','): never
    {
        $sep = $sep === ';' ? ';' : ',';
        $filename = preg_replace('/[^A-Za-z0-9_.-]/', '_', $filename);

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store');

        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, array_map([self::class, 'cell'], $header), $sep, '"', '');
        foreach ($rows as $row) {
            fputcsv($out, array_map([self::class, 'cell'], array_values($row)), $sep, '"', '');
        }
        fclose($out);
        exit;
    }

    /**
     * Cegah CSV/formula injection: teks yang berawalan = + - @ tab atau CR diberi tanda petik
     * agar tidak dieksekusi sebagai rumus oleh Excel/Sheets. Angka dikirim apa adanya.
     */
    public static function cell($value)
    {
        if (is_int($value) || is_float($value)) {
            return $value;
        }
        $value = (string) $value;
        if ($value !== '' && strpbrk($value[0], "=+-@\t\r") !== false) {
            return "'" . $value;
        }
        return $value;
    }
}
