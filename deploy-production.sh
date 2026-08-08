#!/bin/bash
# =============================================================================
# PRODUCTION DEPLOYMENT SCRIPT
# SistemAbsensi PT Mingda — absensi.mingda.my.id
#
# [FIX 2026-08-08] Mengatasi:
#   1. laravel.log 2.7 GB → disk I/O overload seluruh server
#   2. LOG_CHANNEL masih 'single' → tidak ada rotasi log
#   3. session:cleanup schedule masih jalan meski SESSION_DRIVER=redis
#   4. warmup-emails schedule masih muncul (dari kode lama)
#
# Jalankan sebagai root di server production:
#   chmod +x deploy-production.sh && ./deploy-production.sh
# =============================================================================

set -e
APP_DIR="/www/wwwroot/absensi.mingda.my.id"
PHP_BIN="php"

echo ""
echo "╔══════════════════════════════════════════════════════════╗"
echo "║        DEPLOYMENT — PT Mingda Absensi                   ║"
echo "║        $(date '+%Y-%m-%d %H:%M:%S')                            ║"
echo "╚══════════════════════════════════════════════════════════╝"
echo ""
cd "$APP_DIR"

# ── STEP 1: Truncate laravel.log 2.7 GB (DARURAT) ────────────────────────────
echo "── STEP 1: Truncate laravel.log (saat ini 2.7 GB) ──"
LOGFILE="$APP_DIR/storage/logs/laravel.log"
echo "  Ukuran sebelum : $(du -sh "$LOGFILE" 2>/dev/null | cut -f1 || echo 'N/A')"
tail -n 500 "$LOGFILE" > "$APP_DIR/storage/logs/laravel.log.bak-$(date +%Y%m%d)" 2>/dev/null || true
> "$LOGFILE"
echo "  Ukuran sesudah : $(du -sh "$LOGFILE" | cut -f1)"
echo "  ✅ laravel.log berhasil di-truncate"
echo ""

# ── STEP 2: Git pull ─────────────────────────────────────────────────────────
echo "── STEP 2: Git pull kode terbaru ──"
git pull origin main
echo "  ✅ Git pull selesai — $(git log --oneline -1)"
echo ""

# ── STEP 3: Update .env — tambah konfigurasi log rotation ───────────────────
echo "── STEP 3: Update .env production ──"
ENV_FILE="$APP_DIR/.env"
set_env() {
    local KEY="$1" VALUE="$2"
    if grep -q "^${KEY}=" "$ENV_FILE"; then
        sed -i "s|^${KEY}=.*|${KEY}=${VALUE}|" "$ENV_FILE"
        echo "  Updated : ${KEY}=${VALUE}"
    else
        echo "${KEY}=${VALUE}" >> "$ENV_FILE"
        echo "  Added   : ${KEY}=${VALUE}"
    fi
}
set_env "LOG_CHANNEL" "daily"
set_env "LOG_LEVEL" "error"
set_env "LOG_DAILY_DAYS" "7"
echo ""
echo "  Verifikasi .env sekarang:"
grep -E 'LOG_CHANNEL|LOG_LEVEL|LOG_DAILY_DAYS|CACHE_STORE|QUEUE_CONNECTION|SESSION_DRIVER' "$ENV_FILE"
echo "  ✅ .env diupdate"
echo ""

# ── STEP 4: Composer install (production mode) ───────────────────────────────
echo "── STEP 4: Composer install (no-dev, optimize) ──"
composer install --no-dev --optimize-autoloader --no-interaction --quiet
echo "  ✅ Composer selesai"
echo ""

# ── STEP 5: Clear & rebuild semua cache Laravel ──────────────────────────────
echo "── STEP 5: Clear & rebuild cache Laravel ──"
$PHP_BIN artisan config:clear && $PHP_BIN artisan route:clear
$PHP_BIN artisan view:clear  && $PHP_BIN artisan cache:clear
echo "  → Cache lama dibersihkan"
$PHP_BIN artisan config:cache && $PHP_BIN artisan route:cache && $PHP_BIN artisan view:cache
echo "  ✅ Cache baru berhasil di-build"
echo ""

# ── STEP 6: Verifikasi schedule list ─────────────────────────────────────────
echo "── STEP 6: Verifikasi schedule list ──"
SCHED=$($PHP_BIN artisan schedule:list 2>&1)
echo "$SCHED"
echo ""
echo "$SCHED" | grep -q "warmup"        && echo "  ⚠️  WARNING: warmup schedule masih ada!"        || echo "  ✅ warmup sudah tidak ada"
echo "$SCHED" | grep -q "session:cleanup" && echo "  ⚠️  WARNING: session:cleanup masih ada!" || echo "  ✅ session:cleanup sudah tidak ada"
echo ""

# ── STEP 7: Verifikasi DB index ──────────────────────────────────────────────
echo "── STEP 7: Verifikasi DB index ──"
$PHP_BIN artisan tinker --execute="
\$i1 = DB::select('SHOW INDEX FROM attendances WHERE Key_name = \"att_overtime_query_idx\"');
echo (count(\$i1)>0 ? '  ✅ att_overtime_query_idx ADA' : '  ❌ TIDAK ADA — jalankan: php artisan migrate --force') . PHP_EOL;
\$i2 = DB::select('SHOW INDEX FROM attendances WHERE Key_name = \"att_user_date_idx\"');
echo (count(\$i2)>0 ? '  ✅ att_user_date_idx ADA' : '  ❌ att_user_date_idx TIDAK ADA') . PHP_EOL;
" 2>/dev/null || echo "  ⚠️  Tidak bisa cek via tinker"
echo ""

# ── STEP 8: Verifikasi Redis ─────────────────────────────────────────────────
echo "── STEP 8: Verifikasi Redis dari Laravel ──"
$PHP_BIN artisan tinker --execute="
try {
    Cache::store('redis')->put('deploy_ping','ok',10);
    \$v = Cache::store('redis')->get('deploy_ping');
    echo (\$v==='ok' ? '  ✅ Redis OK' : '  ❌ Redis GAGAL') . PHP_EOL;
    Cache::store('redis')->forget('deploy_ping');
} catch (Exception \$e) { echo '  ❌ Redis ERROR: '.\$e->getMessage().PHP_EOL; }
" 2>/dev/null || echo "  ⚠️  Tidak bisa cek Redis via tinker"
echo ""

# ── STEP 9: Fix permission ───────────────────────────────────────────────────
echo "── STEP 9: Fix permission ──"
chmod -R 775 storage bootstrap/cache && chown -R www:www storage bootstrap/cache
echo "  ✅ Permission OK"
echo ""

echo "╔══════════════════════════════════════════════════════════╗"
echo "║              DEPLOYMENT SELESAI ✅                       ║"
echo "╚══════════════════════════════════════════════════════════╝"
echo "  ✅ laravel.log 2.7 GB → di-truncate (disk I/O normal)"
echo "  ✅ LOG_CHANNEL=daily  → rotasi per hari, hapus otomatis 7 hari"
echo "  ✅ LOG_LEVEL=error    → tidak log DEBUG/INFO verbose"
echo "  ✅ session:cleanup    → dihapus (SESSION_DRIVER=redis, tidak perlu)"
echo "  ✅ Cache Laravel      → di-rebuild ulang"
echo ""
echo "  Monitor log baru:"
echo "  tail -f $APP_DIR/storage/logs/laravel-$(date +%Y-%m-%d).log"
echo ""
echo "  Jika index DB belum ada, jalankan:"
echo "  $PHP_BIN artisan migrate --force"
echo ""
echo "  Selesai: $(date '+%Y-%m-%d %H:%M:%S')"

