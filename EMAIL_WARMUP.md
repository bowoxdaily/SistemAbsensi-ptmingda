# Email Warmup System - Dokumentasi

## Pendahuluan

**Email Warmup** adalah sistem untuk meningkatkan reputasi sender dan mencegah email masuk folder spam. Sistem ini mengontrol volume pengiriman email secara bertahap selama periode warmup (default: 30 hari) dengan monitoring real-time terhadap delivery rate, bounce rate, dan spam complaints.

## Fitur Utama

### 1. **Pengiriman Gradual (Progressive Volume)**
- Mulai dengan volume kecil (default: 10 email/hari)
- Meningkat secara bertahap setiap hari (default: +15% per hari)
- Mencapai target maksimal (default: 500 email/hari)
- Menghindari sudden spike yang trigger spam filters

### 2. **Rate Limiting Intelligent**
- Minimum 5 detik antar email
- Throttling otomatis di job queue
- Prevent ISP rate limiting

### 3. **Deliverability Monitoring**
- Track delivery rate (target: >95%)
- Monitor bounce rate (alert: >5%)
- Monitor spam complaints (alert: >2%)
- Automatic reputation scoring (0-100)

### 4. **Warmup Schedule Management**
- Start/Pause/Resume/Stop operasi
- Real-time progress tracking
- Daily email limit enforcement
- Auto-advance to next day

## Instalasi & Setup

### 1. Jalankan Migration

```bash
php artisan migrate
```

Ini akan membuat 3 tabel baru:
- `email_warmup_schedules` - Konfigurasi & progress
- `email_warmup_logs` - Log setiap email yang dikirim
- `email_warmup_stats` - Statistik dan reputation score

### 2. Konfigurasi Queue (Opsional)

Jika menggunakan queue, pastikan queue connection sudah dikonfigurasi di `.env`:

```env
QUEUE_CONNECTION=database  # atau redis, sync, dll
```

### 3. Setup SMTP/Mail Driver

Pastikan `.env` sudah memiliki konfigurasi email yang valid:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="PT Mingda"
```

## Cara Penggunaan

### Via CLI

#### Start Warmup

```bash
# Default: 30 days, start 10/day, max 500/day, +15% daily
php artisan email:warmup start

# Custom parameters
php artisan email:warmup start --days=14 --start=5 --max=200 --increase=10
```

Parameter:
- `--days` - Total hari warmup (1-90)
- `--start` - Email per hari di hari 1 (1-100)
- `--max` - Target volume akhir (10-5000)
- `--increase` - % peningkatan harian (0.1-50)

#### Pause

```bash
php artisan email:warmup pause
```

Menghentikan sementara tanpa reset progress.

#### Resume

```bash
php artisan email:warmup resume
```

Melanjutkan dari hari terakhir.

#### Stop

```bash
php artisan email:warmup stop
```

Menghentikan dan mereset ke status inactive.

#### Check Status

```bash
php artisan email:warmup status
```

Menampilkan:
```
Email Warmup Status:
─────────────────────────────────────
Status: active
Progress: 33% (Day 10/30)
Emails sent today: 15/23
Can send now: Yes

Statistics:
  Total sent: 450
  Delivered: 435
  Bounce rate: 2.5%
  Spam rate: 0.8%
  Delivery rate: 96.7%
  Sender reputation: 85/100 (good)

Recommendation: Warmup progressing normally. Continue with scheduled sends.
```

### Via Web Dashboard

#### Akses Dashboard

```
Admin > Settings > Email Warmup Manager
```

URL: `/admin/settings/email-warmup`

#### Fitur Dashboard

1. **Status Overview**
   - Current status (active/paused/inactive/completed)
   - Progress bar
   - Emails sent hari ini
   - Reputation score

2. **Control Buttons**
   - Start Warmup (modal dengan custom parameters)
   - Pause
   - Resume
   - Stop
   - Refresh

3. **Statistics Display**
   - Total Sent
   - Delivered
   - Bounced
   - Spam Complaints
   - Delivery Rate
   - Spam Rate

4. **Smart Recommendations**
   - Berdasarkan metrics atual
   - Alert jika ada masalah
   - Saran aksi yang perlu diambil

5. **Warmup Logs**
   - Real-time log pengiriman
   - Status setiap email (sent/delivered/bounced/spam)
   - Pagination dengan 20 logs per page

### Via API

#### 1. Get Status

```bash
GET /api/email-warmup/status

# Response
{
  "success": true,
  "data": {
    "status": "active",
    "current_day": 10,
    "total_days": 30,
    "progress_percentage": 33,
    "emails_sent_today": 15,
    "emails_allowed_today": 23,
    "can_send_now": true,
    "started_at": "2025-07-15T10:30:00Z",
    "completed_at": null,
    "statistics": {
      "total_sent": 450,
      "total_delivered": 435,
      "total_bounced": 12,
      "total_spam": 3,
      "delivery_rate": 96.67,
      "bounce_rate": 2.67,
      "spam_rate": 0.67,
      "sender_reputation": 85.5,
      "reputation_status": "good"
    }
  }
}
```

#### 2. Start Warmup

```bash
POST /api/email-warmup/start
Content-Type: application/json
X-CSRF-TOKEN: {token}

{
  "total_days": 30,
  "start_volume": 10,
  "max_volume": 500,
  "increase_percentage": 15
}

# Response
{
  "success": true,
  "message": "Email warmup dimulai",
  "data": { ... status data ... }
}
```

#### 3. Pause

```bash
POST /api/email-warmup/pause
X-CSRF-TOKEN: {token}

# Response
{
  "success": true,
  "message": "Email warmup dijeda",
  "data": { ... status data ... }
}
```

#### 4. Resume

```bash
POST /api/email-warmup/resume
X-CSRF-TOKEN: {token}

# Response or error if already completed
{
  "success": true,
  "message": "Email warmup dilanjutkan",
  "data": { ... status data ... }
}
```

#### 5. Stop

```bash
POST /api/email-warmup/stop
X-CSRF-TOKEN: {token}

# Response
{
  "success": true,
  "message": "Email warmup dihentikan",
  "data": { ... status data ... }
}
```

#### 6. Get Recommendations

```bash
GET /api/email-warmup/recommendations

# Response
{
  "success": true,
  "recommendation": "Warmup progressing normally. Continue with scheduled sends.",
  "data": { ... status data ... }
}
```

#### 7. Get Logs

```bash
GET /api/email-warmup/logs?per_page=50

# Response
{
  "success": true,
  "data": {
    "data": [
      {
        "id": 1,
        "recipient_email": "karyawan@example.com",
        "subject": "Selamat Datang di PT Mingda",
        "status": "delivered",
        "message_id": "123456789@smtp.example.com",
        "warmup_day": 10,
        "sent_at": "2025-07-15T14:32:00Z",
        "created_at": "2025-07-15T14:32:00Z"
      }
    ],
    "current_page": 1,
    "per_page": 50,
    "total": 450,
    "last_page": 9
  }
}
```

## Integrasi dengan Pengiriman Email

### 1. Manual Dispatch Email dengan Warmup

```php
use App\Services\EmailWarmupService;
use App\Models\Karyawans;
use App\Mail\WelcomeEmployeeMail;
use Illuminate\Support\Facades\Mail;

$service = new EmailWarmupService();
$employee = Karyawans::find(1);

// Check if warmup is active
if ($service->getSchedule()->status !== 'active') {
    // Send tanpa throttling
    Mail::send(new WelcomeEmployeeMail($employee));
    return;
}

// Jika warmup aktif, respect throttling
if ($service->canSendEmail()) {
    Mail::send(new WelcomeEmployeeMail($employee));
    $service->recordEmailSent(
        $employee->email,
        'Welcome Email',
        $messageId
    );
} else {
    // Queue untuk nanti
    $delay = $service->getDelayBeforeNextEmail();
    Mail::send(new WelcomeEmployeeMail($employee))->delay(now()->addSeconds($delay));
}
```

### 2. Queue Integration

Tambahkan middleware ke queue config di `config/queue.php`:

```php
'middleware' => [
    \App\Queue\Middleware\ThrottleWarmupEmails::class,
],
```

Atau di individual job:

```php
class SendWelcomeEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public $middleware = [
        \App\Queue\Middleware\ThrottleWarmupEmails::class,
    ];

    public function handle()
    {
        // Send email
    }
}
```

### 3. Laravel Job

```php
// Dispatch email job
\App\Jobs\SendWarmupEmail::dispatch($employee, WelcomeEmployeeMail::class)
    ->onQueue('warmup-emails');
```

## Monitoring & Troubleshooting

### KPI Targets

| Metrik | Target | Alert Level |
|--------|--------|-------------|
| Delivery Rate | >95% | <90% |
| Bounce Rate | <5% | >5% |
| Spam Rate | <2% | >2% |
| Reputation Score | >75 | <50 (poor) |

### Troubleshooting

#### Email Tidak Terkirim

**Check 1:** Warmup aktif?
```bash
php artisan email:warmup status
```

**Check 2:** Sudah mencapai daily limit?
```php
$service = new EmailWarmupService();
if (!$service->canSendEmail()) {
    echo "Daily limit reached. Can send again tomorrow.";
}
```

**Check 3:** Mail driver working?
```bash
php artisan tinker
>>> Mail::raw('Test', function($msg) { $msg->to('test@example.com'); })
```

#### Reputation Score Menurun

**Analisis:**
1. Cek bounce rate tinggi?
   - Clean email list
   - Validate recipient addresses

2. Cek spam rate tinggi?
   - Review email content (avoid spam words)
   - Check sender authentication (SPF, DKIM, DMARC)
   - Improve unsubscribe link

3. Cek delivery rate rendah?
   - Monitor ISP feedback
   - May need to reduce sending volume

### Logs

Email warmup logs disimpan di database dan bisa diakses via:

```bash
# View logs
php artisan tinker
>>> App\Models\EmailWarmupLog::latest()->limit(50)->get()

# Export logs
>>> App\Models\EmailWarmupLog::where('status', 'bounced')->export()
```

## Best Practices

### 1. Email List Quality
- Gunakan email yang valid dan aktif
- Remove hard bounces immediately
- Monitor list health regularly

### 2. Content Best Practices
- Personalize subject lines
- Use professional templates
- Include clear unsubscribe link
- Avoid spam trigger words

### 3. Sender Authentication
```bash
# Setup SPF, DKIM, DMARC
# SPF Record
v=spf1 include:smtp.example.com ~all

# DKIM
# Setup via mail provider

# DMARC
v=DMARC1; p=quarantine; rua=mailto:admin@example.com
```

### 4. Monitoring Regular
- Check dashboard daily during warmup
- Monitor delivery metrics hourly
- Review feedback loops
- Document any issues

### 5. Scale Up Carefully
- Don't rush warmup period
- If reputation good, consider extending max volume
- Test with small segments first

## Advanced Configuration

### Customize Volume Schedule

Edit di `app/Services/EmailWarmupService.php`:

```php
// Current: +15% per day, max 500
// For aggressive: +25% per day, max 1000
// For conservative: +10% per day, max 300

$service->start(
    totalDays: 60,      // Longer warmup
    startVolume: 5,     // Very conservative start
    maxVolume: 100,     // Lower target
    increasePercentage: 5  // Gradual increase
);
```

### Custom Reputation Calculation

Edit `app/Models/EmailWarmupStat.php`:

```php
public function calculate(): void
{
    // Current formula:
    // Score -= (bounce_rate * 0.5)
    // Score -= (spam_rate * 2)
    // Score += (delivery_rate * 0.1)
    
    // Customize weights based on ISP priorities
    $reputationScore = 100;
    $reputationScore -= ($this->bounce_rate * 0.8);      // Bounce is critical
    $reputationScore -= ($this->spam_rate * 5);          // Spam is very critical
    $reputationScore += ($this->delivery_rate * 0.2);    // Delivery helps
    
    $this->sender_reputation = max(0, min(100, $reputationScore));
    $this->save();
}
```

## Database Schema

### email_warmup_schedules

```sql
CREATE TABLE email_warmup_schedules (
    id BIGINT PRIMARY KEY,
    status ENUM('inactive', 'active', 'paused', 'completed'),
    current_day INT,
    total_days INT,
    emails_per_day INT,
    max_emails_per_day INT,
    increase_percentage FLOAT,
    emails_sent_today INT,
    last_send_at TIMESTAMP,
    started_at TIMESTAMP,
    completed_at TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### email_warmup_logs

```sql
CREATE TABLE email_warmup_logs (
    id BIGINT PRIMARY KEY,
    recipient_email VARCHAR(255),
    subject VARCHAR(255),
    status ENUM('sent', 'bounced', 'spam', 'delivered'),
    message_id VARCHAR(255),
    error_message TEXT,
    warmup_day INT,
    sent_at TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### email_warmup_stats

```sql
CREATE TABLE email_warmup_stats (
    id BIGINT PRIMARY KEY,
    total_sent INT,
    total_delivered INT,
    total_bounced INT,
    total_spam INT,
    delivery_rate FLOAT,
    bounce_rate FLOAT,
    spam_rate FLOAT,
    sender_reputation FLOAT,
    reputation_status VARCHAR(50),
    last_calculated_at TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

## Troubleshooting Umum

| Problem | Penyebab | Solusi |
|---------|---------|--------|
| "Daily limit reached" | Sudah mencapai max emails/hari | Tunggu sampai besok atau pause/resume |
| Email tidak terkirim | SMTP error | Cek mail configuration di .env |
| Reputation score tinggi tapi bounce banyak | List quality issue | Clean email list, remove invalid emails |
| Emails masuk spam | Authentication issue | Setup SPF/DKIM/DMARC |
| Warmup tidak advance | Bug atau stuck | Jalankan `php artisan email:warmup status` |

## Support & Issues

Untuk masalah atau pertanyaan:
1. Cek logs di database: `SELECT * FROM email_warmup_logs WHERE status = 'bounced';`
2. Check system logs: `storage/logs/laravel.log`
3. Review email configuration: `.env` file
4. Test SMTP: `php artisan tinker` → `Mail::raw('Test', fn($m) => $m->to('test@example.com'))`

---

**Last Updated:** 2025-07-15  
**Version:** 1.0  
**Author:** AI Coding Assistant
