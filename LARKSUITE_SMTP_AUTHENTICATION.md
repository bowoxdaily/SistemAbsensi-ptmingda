# Email Warmup - Larksuite SMTP Authentication Fix

## Problem

Email warmup system selalu bounce dengan error:
```
Message was bounced back
Failed to send "s" to the following recipients:
Recipient: ikhwanudinaldi04@gmail.com
Reason: The mail is suspected to be spam.
Solution: Modify the content and send it again.
Administrator diagnostic information: 912 This mail is rejected by antispam system
```

**Root Cause:** Larksuite SMTP reject karena email authentication gagal (SPF/DKIM missing)

---

## Solution: Setup SPF & DKIM

### Step 1: Login ke Larksuite (Feishu) Admin Console

1. Buka https://admin.larksuite.com
2. Masuk dengan akun admin

### Step 2: Setup Domain Email

1. Cari menu **Email Settings** atau **Domain Management**
2. Pilih domain `mingda.id` (atau domain email Anda)
3. Klik **Verify Domain**

### Step 3: Tambah SPF Record

**Copy record ini ke DNS provider Anda (cPanel, Cloudflare, dll):**

```
Domain: mingda.id
Type: TXT
Name: @ (atau kosong)
Value: v=spf1 include:larksuite.com ~all
TTL: 3600
```

**Di cPanel:**
- Login cPanel
- Cari **Zone Editor** atau **DNS**
- Tambah record baru
- Pilih Type = TXT
- Name = mingda.id (atau kosong)
- Value = v=spf1 include:larksuite.com ~all

### Step 4: Setup DKIM

1. Di Larksuite admin console, cari **DKIM Setup**
2. Larksuite akan generate DKIM record (biasanya ada 3 domain/selector)
3. Copy record tersebut

**Contoh DKIM record yang akan digenerate:**
```
Type: TXT
Name: default._domainkey.mingda.id (atau default._domainkey)
Value: v=DKIM1; p=MIGfMA0BGQQBAoDEF...
```

Tambah ke DNS seperti langkah SPF.

### Step 5: Verify di Larksuite

1. Setelah DNS record ter-add, tunggu 15-30 menit propagasi
2. Di Larksuite admin, klik **Verify** untuk SPF & DKIM
3. Tunggu hingga status = **Verified** ✅

### Step 6: Test Email

```bash
cd /var/www/absensi && php artisan tinker

$emp = \App\Models\Karyawans::where('email', 'ikhwanudinaldi04@gmail.com')->first();
dispatch(new \App\Jobs\SendWarmupEmail($emp));
```

Jalankan queue:
```bash
php artisan queue:work database --queue=warmup-emails --verbose
```

Email seharusnya berhasil tanpa error 912.

---

## Verification

Untuk check apakah SPF/DKIM setup benar, bisa gunakan tools online:

1. **MXToolbox:** https://mxtoolbox.com/spf.aspx
   - Masukkan domain mingda.id
   - Check SPF record

2. **DKIM Validator:** https://www.mail-tester.com/
   - Kirim email test
   - Lihat scoring SPF/DKIM

---

## Troubleshooting

**Jika masih bounce error 912:**

1. Pastikan SPF/DKIM record **ter-publish di DNS** (bisa check via MXToolbox)
2. Tunggu full DNS propagation (24 jam worst case)
3. Check di Larksuite apakah domain sudah **Verified**
4. Jika belum, coba **Re-verify** di Larksuite admin
5. Cek MAIL_USERNAME dan MAIL_FROM_ADDRESS cocok dengan domain ter-verify

**Jika masih tetap error:**
- Domain reputation butuh waktu build-up
- Mulai dengan volume kecil (5-10 email/hari)
- Gradually increase sesuai warmup schedule
- Monitor bounce rate di dashboard `/admin/settings/email-warmup`

---

## Laravel Email Warmup Configuration

Current setup:
```
MAIL_MAILER=smtp
MAIL_HOST=smtp.larksuite.com
MAIL_PORT=465
MAIL_ENCRYPTION=tls
MAIL_USERNAME=adminhrd@mingda.id
MAIL_PASSWORD=TmLWH2swqWMu6HpM
MAIL_FROM_ADDRESS="adminhrd@mingda.id"
MAIL_FROM_NAME="Pt. Mingda International Footwear"
```

**PENTING:** `MAIL_FROM_ADDRESS` harus dari domain yang sudah ter-verify di Larksuite!

Jika pakai domain berbeda, update di `.env`:
```
MAIL_FROM_ADDRESS="noreply@mingda.id"  # Harus domain ter-verify
```

---

## Additional Tips

- **Mulai slow:** Warmup system sudah handle ini dengan volume increment
- **Plain text lebih baik:** Template sudah simplified ke plain text only
- **Rate limiting:** 5 detik delay antar email (sudah built-in)
- **Monitor dashboard:** `/admin/settings/email-warmup` untuk tracking

---

## References

- Larksuite Documentation: https://open.larksuite.com/
- SPF Record Guide: https://www.dmarcian.com/spf-survey/
- DKIM Setup: https://knowledge.validity.com/hc/en-us/articles/221560228
