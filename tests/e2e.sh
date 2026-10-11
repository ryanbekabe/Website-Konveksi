#!/usr/bin/env bash
# Uji end-to-end: menyalin aplikasi ke folder sementara, membuat database sementara dari schema.sql + seed.sql,
# menjalankan server PHP bawaan, lalu menguji alur lengkap lewat HTTP (curl).
#
# Kebutuhan: php, mysql (klien), curl, rsync atau tar, python3 (untuk berkas PNG uji tidak wajib).
# Variabel (opsional): DB_HOST (localhost), DB_USER (root), DB_PASS (kosong), PORT (acak).
# Pengguna MySQL harus boleh CREATE/DROP DATABASE. Database sementara dihapus otomatis.
#
# Jalankan: bash tests/e2e.sh        (exit code 0 = semua lulus)

set -u
ROOT=$(cd "$(dirname "$0")/.." && pwd)
DB_HOST=${DB_HOST:-localhost}
DB_USER=${DB_USER:-root}
DB_PASS=${DB_PASS:-}
DB_NAME="konveksi_e2e_$$"
PORT=${PORT:-$((20000 + RANDOM % 10000))}
TMP=$(mktemp -d)
APP="$TMP/app"
B="http://127.0.0.1:$PORT"
SRV_PID=""

mysql_cmd() { mysql -h"$DB_HOST" -u"$DB_USER" ${DB_PASS:+-p"$DB_PASS"} "$@"; }
sql() { mysql_cmd -N "$DB_NAME" -e "$1"; }

cleanup() {
  [ -n "$SRV_PID" ] && { kill "$SRV_PID"; wait "$SRV_PID"; } 2>/dev/null
  mysql_cmd -e "DROP DATABASE IF EXISTS \`$DB_NAME\`" 2>/dev/null
  rm -rf "$TMP"
}
trap cleanup EXIT

PASS=0; FAIL=0
ok()   { PASS=$((PASS + 1)); }
bad()  { FAIL=$((FAIL + 1)); echo "  GAGAL: $1"; }
eq()   { if [ "$2" == "$3" ]; then ok; else bad "$1 (harapan '$2', hasil '$3')"; fi; }
has()  { if grep -qF -- "$3" <<<"$2"; then ok; else bad "$1 (tidak memuat '$3')"; fi; }
hasnt(){ if grep -qF -- "$3" <<<"$2"; then bad "$1 (memuat '$3')"; else ok; fi; }
section() { echo "== $1"; }
rp() { echo "Rp $(echo "${1%.*}" | sed ':a;s/\B[0-9]\{3\}\>/.&/;ta')"; }

# ---------- HTTP helpers ----------
JAR=""
newjar() { JAR="$TMP/jar_$1"; rm -f "$JAR"; }
# req [curl args...]  -> BODY, CODE, LOC
req() {
  local out; out=$(curl -s -o "$TMP/body" -w '%{http_code}|%{redirect_url}' -b "$JAR" -c "$JAR" "$@")
  CODE=${out%%|*}; LOC=${out#*|}; BODY=$(cat "$TMP/body")
}
tok() { curl -s -b "$JAR" -c "$JAR" "$B$1" | grep -o 'name="_token" value="[a-f0-9]*"' | head -1 | sed 's/.*value="//;s/"//'; }
post() { local path=$1; shift; local t; t=$(tok "${TOKPAGE:-$path}"); req -X POST --form-string "_token=$t" "$@" "$B$path"; }
flash() { grep -o 'alert-[a-z]*">[^<]*' <<<"$BODY" | head -1 | sed 's/.*">//'; }
login() { newjar "$1"; local t; t=$(tok /masuk); req -X POST -d "_token=$t&email=$2&password=$3" "$B/masuk"; }
code_of() { curl -s -o /dev/null -w '%{http_code}' -b "$1" "$B$2"; }

# ---------- Persiapan ----------
echo "Menyiapkan aplikasi uji di $TMP (port $PORT, database $DB_NAME)"
mkdir -p "$APP"
if command -v rsync >/dev/null; then
  rsync -a --exclude .git --exclude config/config.php --exclude 'storage/uploads/*' --exclude 'public/uploads/products/*' "$ROOT/" "$APP/"
else
  (cd "$ROOT" && tar --exclude=.git --exclude=config/config.php -cf - .) | tar -xf - -C "$APP"
fi
mkdir -p "$APP/storage"/{uploads/designs,uploads/payments,logs,cache,backups} "$APP/public/uploads/products"
rm -f "$APP/public/install.php"

mysql_cmd -e "CREATE DATABASE \`$DB_NAME\` CHARACTER SET utf8mb4" || { echo "Tidak dapat membuat database uji."; exit 2; }
mysql_cmd "$DB_NAME" < "$ROOT/database/schema.sql" || { echo "schema.sql gagal diimpor"; exit 2; }
mysql_cmd "$DB_NAME" < "$ROOT/database/seed.sql" || { echo "seed.sql gagal diimpor"; exit 2; }

cat > "$APP/config/config.php" <<EOF
<?php
return [
    'app' => ['name' => 'Uji', 'base_path' => '', 'debug' => false, 'timezone' => 'Asia/Jakarta', 'whatsapp' => '6281', 'min_qty_custom' => 12],
    'db' => ['host' => '$DB_HOST', 'name' => '$DB_NAME', 'user' => '$DB_USER', 'pass' => '$DB_PASS', 'charset' => 'utf8mb4'],
    'upload' => ['max_size' => 2097152, 'image_mime' => ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'],
                 'document_mime' => ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'application/pdf' => 'pdf'], 'document_max_size' => 5242880],
    'smtp' => [],
];
EOF
(cd "$APP/public" && php -S "127.0.0.1:$PORT" -t . index.php >"$TMP/server.log" 2>&1 &) ; sleep 1
SRV_PID=$(pgrep -f "php -S 127.0.0.1:$PORT" | head -1)
for i in 1 2 3 4 5; do curl -s -o /dev/null "$B/" && break; sleep 1; done

PW=$(php -r 'echo password_hash("Rahasia123", PASSWORD_BCRYPT);')
sql "INSERT INTO users (nama,email,no_hp,password_hash,role) VALUES ('Staf Produksi','prod@t.test','0811111111','$PW','produksi'),('Staf Keuangan','keu@t.test','0822222222','$PW','keuangan')"
printf '%s' 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==' | base64 -d > "$TMP/ok.png"
echo '<?php echo "x";' > "$TMP/evil.php"

# ---------- A. Publik ----------
section "Halaman publik"
newjar pub
req "$B/"; eq "beranda 200" 200 "$CODE"; has "beranda memuat nama usaha" "$BODY" "Konveksi"
req "$B/produk"; eq "katalog 200" 200 "$CODE"; has "katalog memuat produk" "$BODY" "Kaos Cotton Combed 30s"
req "$B/produk?q=kemeja"; has "pencarian produk" "$BODY" "Kemeja Seragam Kerja"; hasnt "pencarian menyaring" "$BODY" "Hoodie Fleece"
req "$B/produk/kaos-cotton-combed-30s"; eq "detail produk 200" 200 "$CODE"; has "harga grosir tampil" "$BODY" "Harga Grosir"
req "$B/produk/tidak-ada"; eq "produk tak ada 404" 404 "$CODE"
req "$B/halaman-tak-ada"; eq "rute tak dikenal 404" 404 "$CODE"
req "$B/assets/css/style.css"; eq "CSS dilayani" 200 "$CODE"
for p in /config/config.php /app/routes.php /database/schema.sql /storage/logs/error.log /.git/HEAD /cron/backup_db.php; do eq "tidak terekspos: $p" 404 "$(code_of "$JAR" $p)"; done
eq "path traversal ditolak" 404 "$(curl -s -o /dev/null -w '%{http_code}' --path-as-is "$B/assets/../../config/config.php")"

# ---------- B. Autentikasi ----------
section "Autentikasi"
newjar a
req -X POST -d 'email=x' "$B/masuk"; eq "POST tanpa token CSRF ditolak" 403 "$CODE"
post /masuk --form-string "email=admin@konveksi.local" --form-string "password=salah"; eq "login salah dialihkan" 302 "$CODE"; req "$B/masuk"; has "pesan login salah" "$(flash)" "salah"
TOKPAGE=/daftar post /daftar --form-string "nama=A" --form-string "email=bukan-email" --form-string "no_hp=1" --form-string "password=abc" --form-string "password_confirm=abc"; req "$B/daftar"; has "validasi pendaftaran" "$BODY" "alert-error"
for u in "c1|Pelanggan Satu" "c2|Pelanggan Dua"; do
  newjar "${u%%|*}"; TOKPAGE=/daftar post /daftar --form-string "nama=${u#*|}" --form-string "email=${u%%|*}@t.test" --form-string "no_hp=0812345678" --form-string "password=Rahasia123" --form-string "password_confirm=Rahasia123"
  eq "daftar ${u%%|*} berhasil" 302 "$CODE"
done
newjar dup; TOKPAGE=/daftar post /daftar --form-string "nama=Dobel" --form-string "email=c1@t.test" --form-string "no_hp=0812345678" --form-string "password=Rahasia123" --form-string "password_confirm=Rahasia123"; req "$B/daftar"; has "email dobel ditolak" "$BODY" "sudah terdaftar"
eq "pelanggan baru berperan pelanggan" pelanggan "$(sql "SELECT role FROM users WHERE email='c1@t.test'")"
eq "kata sandi di-hash" '$2y$' "$(sql "SELECT LEFT(password_hash,4) FROM users WHERE email='c1@t.test'")"
# pembatasan percobaan login
newjar rl; for i in 1 2 3 4 5; do t=$(tok /masuk); req -X POST -d "_token=$t&email=keu@t.test&password=salah" "$B/masuk"; done
t=$(tok /masuk); req -X POST -d "_token=$t&email=keu@t.test&password=Rahasia123" "$B/masuk"; req "$B/masuk"; has "login dibatasi setelah 5 gagal" "$(flash)" "Terlalu banyak"
sql "DELETE FROM login_attempts"

# ---------- C. Matriks akses ----------
section "Matriks hak akses"
login anon x@x.x y >/dev/null; : > "$TMP/jar_anon"
login c1 c1@t.test Rahasia123; login prod prod@t.test Rahasia123; login keu keu@t.test Rahasia123; login admin admin@konveksi.local admin123
# kolom: anon pelanggan produksi keuangan admin
matrix='/admin 302 403 200 200 200
/admin/pesanan 302 403 200 200 200
/admin/produk 302 403 403 403 200
/admin/pengguna 302 403 403 403 200
/admin/produksi 302 403 200 403 200
/admin/bahan 302 403 200 200 200
/admin/bahan/tambah 302 403 403 403 200
/admin/supplier 302 403 403 403 200
/admin/laporan 302 403 200 200 200
/admin/laporan/penjualan 302 403 403 200 200
/admin/laporan/produksi 302 403 200 403 200
/admin/laporan/stok 302 403 200 200 200
/pesanan 302 200 200 200 200
/akun 302 200 200 200 200
/notifikasi 302 200 200 200 200'
while read -r path a c p k ad; do
  i=0; for who in anon c1 prod keu admin; do
    exp=$(echo "$a $c $p $k $ad" | cut -d' ' -f$((i + 1))); i=$((i + 1))
    eq "$path sebagai $who" "$exp" "$(code_of "$TMP/jar_$who" "$path")"
  done
done <<<"$matrix"

# ---------- D. Siklus pesanan ----------
section "Pesanan custom end-to-end"
JAR="$TMP/jar_c1"
TOKPAGE=/pesan/custom post /pesan/custom --form-string product_id=1 --form-string warna=Hitam --form-string "qty[M]=5" --form-string penerima=Andi --form-string no_hp=0812345678 --form-string alamat=Jl.Mawar --form-string kota=Bandung --form-string provinsi=Jabar
req "$B/pesan/custom"; has "minimum order custom" "$(flash)" "minimal 12"
TOKPAGE=/pesan/custom post /pesan/custom --form-string product_id=1 --form-string warna=Hitam --form-string "qty[M]=6" --form-string "qty[L]=6" --form-string penerima=Andi --form-string no_hp=0812345678 --form-string alamat=Jl.Mawar --form-string kota=Bandung --form-string provinsi=Jabar -F "desain=@$TMP/ok.png;type=image/png"
eq "pesanan dibuat" 302 "$CODE"; K=$(sql "SELECT kode_order FROM orders ORDER BY id DESC LIMIT 1"); has "kode pesanan format" "$K" "KNV-"
eq "harga grosir (12 pcs x 48.000)" "576000.00" "$(sql "SELECT total FROM orders WHERE kode_order='$K'")"
eq "DP 50%" "288000.00" "$(sql "SELECT dp_jumlah FROM orders WHERE kode_order='$K'")"
eq "status awal" menunggu_pembayaran "$(sql "SELECT status FROM orders WHERE kode_order='$K'")"
OID=$(sql "SELECT id FROM orders WHERE kode_order='$K'")
# bayar DP
TOKPAGE=/pesanan/$K post /pesanan/$K/bayar; req "$B/pesanan/$K"; has "bukti wajib" "$(flash)" "wajib"
TOKPAGE=/pesanan/$K post /pesanan/$K/bayar -F "bukti=@$TMP/evil.php;type=application/pdf;filename=b.pdf"; req "$B/pesanan/$K"; has "file PHP menyamar ditolak" "$(flash)" "tidak didukung"
TOKPAGE=/pesanan/$K post /pesanan/$K/bayar -F "bukti=@$TMP/ok.png;type=image/png"; req "$B/pesanan/$K"; has "bukti DP diterima" "$(flash)" "menunggu verifikasi"
TOKPAGE=/pesanan/$K post /pesanan/$K/bayar -F "bukti=@$TMP/ok.png;type=image/png"; req "$B/pesanan/$K"; has "bayar ganda ditolak" "$(flash)" "sedang diverifikasi"
PID=$(sql "SELECT id FROM payments WHERE order_id=$OID ORDER BY id LIMIT 1")
# IDOR
JAR="$TMP/jar_c2"
eq "pelanggan lain: pesanan 404" 404 "$(code_of "$TMP/jar_c2" /pesanan/$K)"
eq "pelanggan lain: bukti 404" 404 "$(code_of "$TMP/jar_c2" /berkas/bukti/$PID)"
eq "pelanggan lain: invoice 404" 404 "$(code_of "$TMP/jar_c2" /pesanan/$K/invoice)"
eq "pemilik: bukti 200" 200 "$(code_of "$TMP/jar_c1" /berkas/bukti/$PID)"
eq "staf: bukti 200" 200 "$(code_of "$TMP/jar_keu" /berkas/bukti/$PID)"
# verifikasi: produksi tidak boleh, keuangan boleh
JAR="$TMP/jar_prod"; TOKPAGE=/admin/pesanan/$K post /admin/pembayaran/$PID/verifikasi --form-string aksi=terima; eq "produksi tak boleh verifikasi bayar" 403 "$CODE"
JAR="$TMP/jar_keu"; TOKPAGE=/admin/pesanan/$K post /admin/pembayaran/$PID/verifikasi --form-string aksi=terima; eq "keuangan verifikasi DP" 302 "$CODE"
eq "status dp_dibayar" dp_dibayar "$(sql "SELECT status FROM orders WHERE id=$OID")"
# mockup
JAR="$TMP/jar_prod"; TOKPAGE=/admin/pesanan/$K post /admin/pesanan/$K/mockup -F "mockup=@$TMP/ok.png;type=image/png"
eq "menunggu persetujuan mockup" menunggu_persetujuan "$(sql "SELECT status FROM orders WHERE id=$OID")"
JAR="$TMP/jar_c1"; TOKPAGE=/pesanan/$K post /pesanan/$K/mockup --form-string aksi=revisi --form-string catatan=; req "$B/pesanan/$K"; has "revisi butuh catatan" "$(flash)" "catatan revisi"
TOKPAGE=/pesanan/$K post /pesanan/$K/mockup --form-string aksi=revisi --form-string "catatan=Logo lebih besar"; eq "kembali ke desain_mockup" desain_mockup "$(sql "SELECT status FROM orders WHERE id=$OID")"
JAR="$TMP/jar_prod"; TOKPAGE=/admin/pesanan/$K post /admin/pesanan/$K/mockup -F "mockup=@$TMP/ok.png;type=image/png"
JAR="$TMP/jar_c1"; TOKPAGE=/pesanan/$K post /pesanan/$K/mockup --form-string aksi=setuju; eq "produksi dimulai" produksi "$(sql "SELECT status FROM orders WHERE id=$OID")"
eq "6 tahap produksi dibuat" 6 "$(sql "SELECT COUNT(*) FROM production_stages WHERE order_id=$OID")"
# tahapan
JAR="$TMP/jar_prod"; TOKPAGE=/admin/pesanan/$K
S=($(sql "SELECT id FROM production_stages WHERE order_id=$OID ORDER BY id"))
post /admin/produksi/${S[1]} --form-string aksi=mulai; req "$B/admin/pesanan/$K"; has "tak boleh lompat tahap" "$(flash)" "terlebih dahulu"
post /admin/produksi/${S[0]} --form-string aksi=selesai; req "$B/admin/pesanan/$K"; has "tak bisa selesai sebelum mulai" "$(flash)" "Mulai tahap"
for idx in 0 1 2 3; do post /admin/produksi/${S[$idx]} --form-string aksi=mulai; post /admin/produksi/${S[$idx]} --form-string aksi=selesai; done
eq "petugas otomatis staf produksi" "Staf Produksi" "$(sql "SELECT u.nama FROM production_stages s JOIN users u ON u.id=s.petugas_id WHERE s.id=${S[0]}")"
post /admin/produksi/${S[4]} --form-string aksi=mulai; eq "mulai QC -> status qc" qc "$(sql "SELECT status FROM orders WHERE id=$OID")"
post /admin/produksi/${S[4]} --form-string aksi=selesai; post /admin/produksi/${S[5]} --form-string aksi=mulai; post /admin/produksi/${S[5]} --form-string aksi=selesai
eq "semua tahap selesai -> menunggu_pelunasan" menunggu_pelunasan "$(sql "SELECT status FROM orders WHERE id=$OID")"
post /admin/produksi/${S[5]} --form-string aksi=reset; eq "tahap terkunci setelah menunggu pelunasan" menunggu_pelunasan "$(sql "SELECT status FROM orders WHERE id=$OID")"
# ongkir & pelunasan
JAR="$TMP/jar_prod"; TOKPAGE=/admin/pesanan/$K post /admin/pesanan/$K/ongkir --form-string ongkir=10000; eq "produksi tak boleh set ongkir" 403 "$CODE"
JAR="$TMP/jar_keu"; TOKPAGE=/admin/pesanan/$K post /admin/pesanan/$K/ongkir --form-string ongkir=10000
eq "total + ongkir" "586000.00" "$(sql "SELECT total FROM orders WHERE id=$OID")"
JAR="$TMP/jar_c1"; req "$B/pesanan/$K"; has "tagihan pelunasan Rp 298.000" "$BODY" "Rp 298.000"
TOKPAGE=/pesanan/$K post /pesanan/$K/bayar -F "bukti=@$TMP/ok.png;type=image/png"
PID2=$(sql "SELECT id FROM payments WHERE order_id=$OID AND jenis='pelunasan'")
JAR="$TMP/jar_keu"; TOKPAGE=/admin/pesanan/$K post /admin/pembayaran/$PID2/verifikasi --form-string aksi=terima
eq "pelunasan -> siap_kirim" siap_kirim "$(sql "SELECT status FROM orders WHERE id=$OID")"
JAR="$TMP/jar_prod"; TOKPAGE=/admin/pesanan/$K post /admin/pesanan/$K/status --form-string status=dikirim --form-string kurir= --form-string resi=; req "$B/admin/pesanan/$K"; has "kirim butuh resi" "$(flash)" "resi"
TOKPAGE=/admin/pesanan/$K post /admin/pesanan/$K/status --form-string status=selesai; req "$B/admin/pesanan/$K"; has "lompat status ditolak" "$(flash)" "tidak diizinkan"
post /admin/pesanan/$K/status --form-string status=dikirim --form-string kurir=JNE --form-string resi=JNE123; post /admin/pesanan/$K/status --form-string status=selesai
eq "pesanan selesai" selesai "$(sql "SELECT status FROM orders WHERE id=$OID")"
eq "total terbayar = total" "586000.00" "$(sql "SELECT SUM(jumlah) FROM payments WHERE order_id=$OID AND status='terverifikasi'")"
eq "resi tersimpan" JNE123 "$(sql "SELECT resi FROM shipments WHERE order_id=$OID")"
eq "notifikasi pelanggan terkirim" 1 "$([ "$(sql "SELECT COUNT(*) FROM notifications n JOIN users u ON u.id=n.user_id WHERE u.email='c1@t.test'")" -ge 5 ] && echo 1 || echo 0)"
# dokumen
JAR="$TMP/jar_c1"; req "$B/pesanan/$K/invoice"; eq "invoice pemilik 200" 200 "$CODE"; has "invoice LUNAS" "$BODY" "LUNAS"; has "invoice total" "$BODY" "Rp 586.000"
eq "SPK untuk pelanggan 403" 403 "$(code_of "$TMP/jar_c1" /admin/pesanan/$K/spk)"
JAR="$TMP/jar_prod"; req "$B/admin/pesanan/$K/spk"; eq "SPK staf 200" 200 "$CODE"; has "SPK memuat matriks ukuran" "$BODY" "Total pcs"

# ---------- E. Checkout dari keranjang ----------
section "Keranjang dan checkout"
JAR="$TMP/jar_c2"
req "$B/checkout"; eq "checkout keranjang kosong dialihkan" 302 "$CODE"
TOKPAGE=/produk/kaos-cotton-combed-30s post /keranjang/tambah --form-string product_id=1 --form-string ukuran=M --form-string warna=Putih --form-string qty=6
TOKPAGE=/produk/kaos-cotton-combed-30s post /keranjang/tambah --form-string product_id=1 --form-string ukuran=L --form-string warna=Putih --form-string qty=6
req "$B/keranjang"; has "harga grosir lintas ukuran (12 pcs)" "$BODY" "Rp 48.000"
TOKPAGE=/checkout post /checkout --form-string 'penerima=Budi' --form-string no_hp=0812345678 --form-string 'alamat=<img src=x onerror=alert(1)>' --form-string kota=Bandung --form-string provinsi=Jabar
K2=$(sql "SELECT kode_order FROM orders ORDER BY id DESC LIMIT 1"); eq "checkout membuat pesanan baru" "576000.00" "$(sql "SELECT total FROM orders WHERE kode_order='$K2'")"
req "$B/pesanan/$K2"; hasnt "alamat HTML tidak dieksekusi (XSS)" "$BODY" "<img src=x"; has "alamat ter-escape" "$BODY" "&lt;img src=x"
TOKPAGE=/pesanan/$K2 post /pesanan/$K2/batal; eq "pembatalan sebelum DP" dibatalkan "$(sql "SELECT status FROM orders WHERE kode_order='$K2'")"

# ---------- F. Stok bahan ----------
section "Stok bahan baku"
JAR="$TMP/jar_admin"
TOKPAGE=/admin/supplier/tambah post /admin/supplier/tambah --form-string "nama=Toko Kain" --form-string kontak=0812
TOKPAGE=/admin/bahan/tambah post /admin/bahan/tambah --form-string "nama=Kain Uji" --form-string satuan=meter --form-string stok_minimum=20 --form-string stok_awal=100 --form-string supplier_id=1
M=$(sql "SELECT id FROM materials WHERE nama='Kain Uji'"); eq "stok awal tercatat" "100.00" "$(sql "SELECT stok FROM materials WHERE id=$M")"
TOKPAGE=/admin/bahan/$M
post /admin/bahan/$M/mutasi --form-string aksi=keluar --form-string qty=150 --form-string tanggal=$(date +%F); req "$B/admin/bahan/$M"; has "keluar melebihi stok ditolak" "$(flash)" "Stok tidak cukup"
post /admin/bahan/$M/mutasi --form-string aksi=keluar --form-string qty=85 --form-string tanggal=$(date +%F) --form-string kode_order=$K
eq "stok setelah pemakaian" "15.00" "$(sql "SELECT stok FROM materials WHERE id=$M")"
eq "notifikasi stok menipis (sekali)" 1 "$(sql "SELECT COUNT(*) FROM notifications WHERE pesan LIKE 'Stok Kain Uji menipis%'")"
post /admin/bahan/$M/mutasi --form-string aksi=masuk --form-string qty=5 --form-string tanggal=$(date -d '+3 day' +%F); req "$B/admin/bahan/$M"; has "tanggal masa depan ditolak" "$(flash)" "Tanggal tidak valid"
post /admin/bahan/$M/mutasi --form-string aksi=koreksi --form-string stok_fisik=40 --form-string tanggal=$(date +%F)
eq "koreksi stok" "40.00" "$(sql "SELECT stok FROM materials WHERE id=$M")"
eq "stok = jumlah mutasi" "40.00" "$(sql "SELECT SUM(CASE tipe WHEN 'masuk' THEN qty ELSE -qty END) FROM material_movements WHERE material_id=$M")"
JAR="$TMP/jar_prod"; TOKPAGE=/admin/bahan/$M post /admin/bahan/$M/mutasi --form-string aksi=keluar --form-string qty=1 --form-string tanggal=$(date +%F); eq "produksi boleh mencatat pemakaian" 302 "$CODE"
JAR="$TMP/jar_keu"; TOKPAGE=/admin/bahan/$M post /admin/bahan/$M/mutasi --form-string aksi=keluar --form-string qty=1 --form-string tanggal=$(date +%F); eq "keuangan tak boleh mencatat stok" 403 "$CODE"
JAR="$TMP/jar_admin"; TOKPAGE=/admin/bahan/$M post /admin/bahan/$M/hapus; req "$B/admin/bahan/$M"; eq "bahan berriwayat tidak terhapus" 1 "$(sql "SELECT COUNT(*) FROM materials WHERE id=$M")"
# mutasi serentak: tidak boleh negatif
sql "INSERT INTO materials (nama,satuan,stok,stok_minimum) VALUES ('Uji Konkurensi','pcs',100,0)"
MC=$(sql "SELECT id FROM materials WHERE nama='Uji Konkurensi'")
cat > "$TMP/conc.php" <<'PHP'
<?php
define('BASE_DIR', $argv[2]); $GLOBALS['config'] = require BASE_DIR . '/config/config.php';
require BASE_DIR . '/app/core/Database.php'; require BASE_DIR . '/app/models/Material.php'; require BASE_DIR . '/app/helpers/functions.php';
try { Material::move((int) $argv[1], 'keluar', 30, date('Y-m-d'), 'konkurensi', null); echo "OK\n"; } catch (DomainException $e) { echo "TOLAK\n"; }
PHP
for i in 1 2 3 4 5 6; do php "$TMP/conc.php" "$MC" "$APP" > "$TMP/conc_$i" 2>&1 & done; wait
eq "mutasi serentak: tepat 3 berhasil" 3 "$(cat "$TMP"/conc_* | grep -c '^OK')"
eq "mutasi serentak: stok akhir 10" "10.00" "$(sql "SELECT stok FROM materials WHERE id=$MC")"

# ---------- G. Laporan ----------
section "Laporan"
JAR="$TMP/jar_keu"
EXP_OMZET=$(sql "SELECT COALESCE(SUM(total),0) FROM orders WHERE status<>'dibatalkan'")
req "$B/admin/laporan/penjualan?dari=2000-01-01&sampai=$(date +%F)"; eq "laporan penjualan 200" 200 "$CODE"
has "omzet laporan = SUM(total) di database" "$BODY" "$(rp "$EXP_OMZET")"
req "$B/admin/laporan/penjualan?dari=$(date +%F)&sampai=$(date -d '-5 day' +%F)"; has "rentang terbalik ditukar" "$BODY" "$(date -d '-5 day' '+%d %b %Y')"
curl -s -b "$TMP/jar_keu" "$B/admin/laporan/penjualan?dari=2000-01-01&sampai=$(date +%F)&format=csv" -o "$TMP/s.csv" -D "$TMP/s.hdr"
has "CSV bertipe text/csv" "$(cat "$TMP/s.hdr")" "text/csv"; eq "CSV berBOM UTF-8" "efbbbf" "$(head -c 3 "$TMP/s.csv" | od -An -tx1 | tr -d ' \n')"
eq "baris CSV = jumlah pesanan non-batal + header" "$(( $(sql "SELECT COUNT(*) FROM orders WHERE status<>'dibatalkan'") + 1 ))" "$(wc -l < "$TMP/s.csv" | tr -d ' ')"
has "CSV titik koma" "$(curl -s -b "$TMP/jar_keu" "$B/admin/laporan/penjualan?dari=2000-01-01&sampai=$(date +%F)&format=csv&sep=;" | sed -n 2p)" ";"
req "$B/admin/laporan/produksi"; eq "keuangan tak boleh laporan produksi" 403 "$CODE"
JAR="$TMP/jar_prod"; req "$B/admin/laporan/produksi?dari=2000-01-01&sampai=$(date +%F)"; eq "laporan produksi 200" 200 "$CODE"; has "1 pesanan selesai produksi" "$BODY" "Pesanan selesai produksi (12 pcs)"
JAR="$TMP/jar_admin"; TOKPAGE=/admin/bahan/tambah post /admin/bahan/tambah --form-string "nama==HYPERLINK(\"http://x\")" --form-string satuan=pcs --form-string stok_minimum=1
curl -s -b "$TMP/jar_admin" "$B/admin/laporan/stok?format=csv" | grep -F "HYPERLINK" | head -1 > "$TMP/inj.txt"
has "CSV injection dinetralkan" "$(cat "$TMP/inj.txt")" "\"'=HYPERLINK"

# ---------- H. Pengguna dan akun ----------
section "Pengguna dan akun"
JAR="$TMP/jar_admin"
req "$B/admin"; has "peringatan sandi bawaan admin" "$BODY" "kata sandi bawaan"
TOKPAGE=/admin/pengguna/tambah post /admin/pengguna/tambah --form-string 'nama=<b>Staf</b> Baru' --form-string email=baru@t.test --form-string no_hp=0811111111 --form-string role=produksi --form-string password=Rahasia123
req "$B/admin/pengguna"; hasnt "nama pengguna di-escape" "$BODY" "<b>Staf</b>"; has "pengguna baru tampil" "$BODY" "baru@t.test"
AID=$(sql "SELECT id FROM users WHERE email='admin@konveksi.local'")
TOKPAGE=/admin/pengguna/$AID/ubah post /admin/pengguna/$AID/ubah --form-string nama=Administrator --form-string email=admin@konveksi.local --form-string no_hp=081234567890 --form-string role=pelanggan --form-string status=nonaktif
eq "admin tak bisa turun peran/menonaktifkan diri" "admin|aktif" "$(sql "SELECT CONCAT(role,'|',status) FROM users WHERE id=$AID")"
UID2=$(sql "SELECT id FROM users WHERE email='keu@t.test'")
TOKPAGE=/admin/pengguna/$UID2/ubah post /admin/pengguna/$UID2/ubah --form-string nama="Staf Keuangan" --form-string email=keu@t.test --form-string no_hp=0822222222 --form-string role=keuangan --form-string status=nonaktif
JAR="$TMP/jar_keu"; req "$B/admin"; eq "akun dinonaktifkan langsung kehilangan sesi" 302 "$CODE"
sql "DELETE FROM login_attempts"; login keu2 keu@t.test Rahasia123; req "$B/masuk" ; has "akun nonaktif tak bisa login" "$(flash)" "salah"
JAR="$TMP/jar_admin"
TOKPAGE=/akun post /akun/sandi --form-string password_lama=admin123 --form-string password_baru=SandiBaru2026 --form-string password_konfirmasi=SandiBaru2026
req "$B/admin"; hasnt "peringatan hilang setelah ganti sandi" "$BODY" "kata sandi bawaan"; has "sesi tetap login setelah ganti sandi" "$BODY" "Dashboard"
sql "DELETE FROM login_attempts"; login old admin@konveksi.local admin123; req "$B/masuk"; has "sandi lama tidak berlaku" "$(flash)" "salah"

echo
echo "Selesai: $PASS lulus, $FAIL gagal"
[ "$FAIL" -eq 0 ]
