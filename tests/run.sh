#!/usr/bin/env bash
# Menjalankan semua pemeriksaan: sintaks PHP, uji unit/integritas, dan uji end-to-end.
# Variabel opsional untuk e2e: DB_HOST, DB_USER, DB_PASS, PORT.  Gunakan SKIP_E2E=1 untuk melewati e2e.
cd "$(dirname "$0")/.." || exit 2
status=0

echo "== Sintaks PHP"
errors=$(find app public cron config tests -name '*.php' -print0 | xargs -0 -n1 php -l 2>&1 | grep -v '^No syntax errors')
if [ -n "$errors" ]; then echo "$errors"; status=1; else echo "OK"; fi

echo "== Uji unit dan integritas"
php tests/unit.php || status=1

echo "== Uji end-to-end"
if [ "${SKIP_E2E:-0}" = "1" ]; then
  echo "dilewati (SKIP_E2E=1)"
elif command -v mysql >/dev/null 2>&1 && mysql -h"${DB_HOST:-localhost}" -u"${DB_USER:-root}" ${DB_PASS:+-p"$DB_PASS"} -e 'SELECT 1' >/dev/null 2>&1; then
  bash tests/e2e.sh || status=1
else
  echo "dilewati: klien/server MySQL tidak dapat dihubungi (atur DB_HOST, DB_USER, DB_PASS)"
fi

echo
[ "$status" -eq 0 ] && echo "SEMUA PEMERIKSAAN LULUS" || echo "ADA PEMERIKSAAN GAGAL"
exit $status
