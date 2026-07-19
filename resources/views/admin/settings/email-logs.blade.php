@extends('layouts.app')

@section('title', 'Log Email Mailgun')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">
        <span class="text-muted fw-light">Pengaturan /</span> Log Email Mailgun
    </h4>

    @if(isset($storageReady) && !$storageReady)
        <div class="alert alert-warning">
            Kolom <strong>mailgun_api_key</strong> belum tersedia. Jalankan <code>php artisan migrate</code> terlebih dahulu.
        </div>
    @endif

    {{-- ── Konfigurasi Mailgun API ─────────────────────────────────────── --}}
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Konfigurasi Mailgun API</h5>
            <a href="/admin/settings/email-smtp" class="btn btn-sm btn-outline-secondary">
                <i class="bx bx-cog me-1"></i>Pengaturan SMTP
            </a>
        </div>
        <div class="card-body">
            <div class="alert alert-info mb-3">
                <strong>Catatan:</strong> Masukkan <strong>Mailgun Private API Key</strong> (bukan SMTP password) dan <strong>Sending Domain</strong> Anda.
                API Key tersedia di <a href="https://app.mailgun.com/settings/api_security" target="_blank" rel="noopener">Mailgun → Settings → API Keys</a>.
            </div>

            <div class="row g-4">
                @foreach(['notifications' => 'Notifikasi', 'interview' => 'Interview'] as $ctx => $label)
                <div class="col-12 col-md-6">
                    <div class="card border @if($configs[$ctx]['has_api_key'] ?? false) border-success @else border-secondary @endif">
                        <div class="card-header d-flex justify-content-between align-items-center py-2">
                            <span class="fw-semibold">{{ $label }}</span>
                            <span class="badge @if($configs[$ctx]['has_api_key'] ?? false) bg-success @else bg-secondary @endif" id="badge-config-{{ $ctx }}">
                                @if($configs[$ctx]['has_api_key'] ?? false) Terkonfigurasi @else Belum diatur @endif
                            </span>
                        </div>
                        <div class="card-body">
                            <form class="mailgun-config-form" data-context="{{ $ctx }}">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label">Mailgun Domain</label>
                                    <input type="text" class="form-control" name="mailgun_domain"
                                        value="{{ $configs[$ctx]['domain'] ?? '' }}"
                                        placeholder="mail.yourdomain.com">
                                    <div class="form-text">Domain yang digunakan untuk kirim email via Mailgun.</div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Mailgun Private API Key</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" name="mailgun_api_key"
                                            placeholder="{{ ($configs[$ctx]['has_api_key'] ?? false) ? '••••••••••••••••••••••• (tersimpan)' : 'key-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx' }}">
                                        <button class="btn btn-outline-secondary btn-toggle-pw" type="button">
                                            <i class="bx bx-show"></i>
                                        </button>
                                    </div>
                                    <div class="form-text">Kosongkan untuk tidak mengubah key yang tersimpan.</div>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
                                    <button type="button" class="btn btn-outline-info btn-sm btn-test-mailgun" data-context="{{ $ctx }}">
                                        <i class="bx bx-check-circle me-1"></i>Test Koneksi
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ── Filter Events ────────────────────────────────────────────────── --}}
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Filter Log</h5>
        </div>
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-6 col-md-3">
                    <label class="form-label">Konteks</label>
                    <select class="form-select" id="filter-context">
                        <option value="all">Semua</option>
                        <option value="notifications">Notifikasi</option>
                        <option value="interview">Interview</option>
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label">Tipe Event</label>
                    <select class="form-select" id="filter-event">
                        <option value="">Semua</option>
                        <option value="delivered">Delivered</option>
                        <option value="failed">Failed</option>
                        <option value="bounced">Bounced</option>
                        <option value="accepted">Accepted</option>
                        <option value="opened">Opened</option>
                        <option value="clicked">Clicked</option>
                        <option value="complained">Complained</option>
                        <option value="unsubscribed">Unsubscribed</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">Dari Tanggal</label>
                    <input type="date" class="form-control" id="filter-begin">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">Sampai Tanggal</label>
                    <input type="date" class="form-control" id="filter-end">
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label">Jumlah</label>
                    <select class="form-select" id="filter-limit">
                        <option value="25">25</option>
                        <option value="50" selected>50</option>
                        <option value="100">100</option>
                        <option value="300">300</option>
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">Recipient</label>
                    <input type="email" class="form-control" id="filter-recipient" placeholder="email@domain.com">
                </div>
                <div class="col-12 col-md-4">
                    <button type="button" class="btn btn-primary w-100" id="btn-fetch-logs">
                        <i class="bx bx-refresh me-1"></i>Muat Log
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Tabel Log ───────────────────────────────────────────────────── --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Riwayat Email</h5>
            <span class="badge bg-secondary" id="total-badge">-</span>
        </div>

        <div id="log-alert-container"></div>

        <div class="table-responsive">
            <table class="table table-hover table-sm align-middle mb-0" id="log-table">
                <thead class="table-light">
                    <tr>
                        <th style="width:140px">Waktu</th>
                        <th style="width:110px">Event</th>
                        <th style="width:80px">Konteks</th>
                        <th>Recipient</th>
                        <th>Subject</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody id="log-tbody">
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            Klik <strong>Muat Log</strong> untuk mengambil data dari Mailgun.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="card-footer d-flex justify-content-between align-items-center py-2" id="log-footer" hidden>
            <div class="text-muted" id="log-page-info" style="font-size:0.875rem"></div>
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item" id="pg-prev">
                        <button class="page-link" id="btn-prev-page" disabled>
                            <i class="bx bx-chevron-left"></i> Sebelumnya
                        </button>
                    </li>
                    <li class="page-item active" id="pg-num">
                        <span class="page-link" id="page-num-label">1</span>
                    </li>
                    <li class="page-item" id="pg-next">
                        <button class="page-link" id="btn-next-page" disabled>
                            Berikutnya <i class="bx bx-chevron-right"></i>
                        </button>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    'use strict';

    // ── Toggle password visibility ────────────────────────────────────
    document.querySelectorAll('.btn-toggle-pw').forEach(btn => {
        btn.addEventListener('click', function () {
            const input = this.previousElementSibling;
            if (input.type === 'password') {
                input.type = 'text';
                this.innerHTML = '<i class="bx bx-hide"></i>';
            } else {
                input.type = 'password';
                this.innerHTML = '<i class="bx bx-show"></i>';
            }
        });
    });

    // ── Save Mailgun Config ───────────────────────────────────────────
    document.querySelectorAll('.mailgun-config-form').forEach(form => {
        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            const ctx = this.dataset.context;
            const fd  = new FormData(this);

            try {
                const res = await fetch(`/api/settings/mailgun-logs/config/${ctx}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': this.querySelector('[name="_token"]').value,
                        'Accept': 'application/json',
                    },
                    body: fd,
                });
                const data = await res.json();
                if (!data.success) throw new Error(data.message || 'Gagal menyimpan');

                const badge = document.getElementById(`badge-config-${ctx}`);
                if (data.data?.has_api_key) {
                    badge.className = 'badge bg-success';
                    badge.textContent = 'Terkonfigurasi';
                }
                Swal.fire('Berhasil', data.message, 'success');
            } catch (err) {
                Swal.fire('Error', err.message || 'Terjadi kesalahan', 'error');
            }
        });
    });

    // ── Test Mailgun Connection ───────────────────────────────────────
    document.querySelectorAll('.btn-test-mailgun').forEach(btn => {
        btn.addEventListener('click', async function () {
            const ctx = this.dataset.context;
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Testing...';

            try {
                const csrf = document.querySelector(`[data-context="${ctx}"] [name="_token"]`)?.value
                    || document.querySelector('[name="_token"]')?.value;

                const res = await fetch(`/api/settings/mailgun-logs/test/${ctx}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                    },
                });
                const data = await res.json();
                Swal.fire(data.success ? 'Berhasil' : 'Gagal', data.message, data.success ? 'success' : 'error');
            } catch (err) {
                Swal.fire('Error', err.message, 'error');
            } finally {
                this.disabled = false;
                this.innerHTML = '<i class="bx bx-check-circle me-1"></i>Test Koneksi';
            }
        });
    });

    // ── Fetch Logs ────────────────────────────────────────────────────
    const btnFetch    = document.getElementById('btn-fetch-logs');
    const tbody       = document.getElementById('log-tbody');
    const totalBadge  = document.getElementById('total-badge');
    const alertBox    = document.getElementById('log-alert-container');
    const logFooter   = document.getElementById('log-footer');
    const btnPrev     = document.getElementById('btn-prev-page');
    const btnNext     = document.getElementById('btn-next-page');
    const pageLabel   = document.getElementById('page-num-label');
    const pageInfo    = document.getElementById('log-page-info');

    // Pagination state
    let currentPage    = 1;
    let nextCursor     = null;
    let prevCursor     = null;
    // Stack of previous-page cursors so "Prev" can go backwards
    let cursorStack    = [];  // [{next, prev}] per page visited

    const EVENT_BADGES = {
        delivered:    'bg-success',
        accepted:     'bg-primary',
        failed:       'bg-danger',
        bounced:      'bg-danger',
        complained:   'bg-warning text-dark',
        unsubscribed: 'bg-secondary',
        opened:       'bg-info text-dark',
        clicked:      'bg-info text-dark',
    };

    const CONTEXT_LABELS = {
        notifications: '<span class="badge bg-label-primary">Notif</span>',
        interview:     '<span class="badge bg-label-warning">Interview</span>',
    };

    function escHtml(str) {
        if (!str) return '-';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function renderRows(items) {
        if (!items.length) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">Tidak ada data event untuk filter yang dipilih.</td></tr>';
            return;
        }

        tbody.innerHTML = items.map(item => {
            const eventClass = EVENT_BADGES[item.event] || 'bg-secondary';
            const ctx = item._context || '';
            const ctxBadge = CONTEXT_LABELS[ctx] || `<span class="badge bg-secondary">${escHtml(ctx)}</span>`;
            const reason = [item.reason, item.description].filter(Boolean).join(' — ');

            return `<tr>
                <td class="text-nowrap" style="font-size:0.8rem">${escHtml(item.datetime)}</td>
                <td><span class="badge ${eventClass}">${escHtml(item.event)}</span></td>
                <td>${ctxBadge}</td>
                <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
                    title="${escHtml(item.recipient)}">${escHtml(item.recipient)}</td>
                <td style="max-width:250px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
                    title="${escHtml(item.subject)}">${escHtml(item.subject)}</td>
                <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
                    title="${escHtml(reason)}">${escHtml(reason) || '-'}</td>
            </tr>`;
        }).join('');
    }

    function updatePagination(data) {
        nextCursor = data.next_cursor || null;
        prevCursor = data.prev_cursor || null;

        btnNext.disabled = !nextCursor;
        btnPrev.disabled = currentPage <= 1;

        pageLabel.textContent = currentPage;
        pageInfo.textContent  = `Halaman ${currentPage} · ${data.total ?? 0} item`;

        // Show/hide footer: show when pagination is available or page > 1
        const hasPaging = nextCursor || currentPage > 1;
        logFooter.hidden = !hasPaging;
    }

    function buildBaseParams() {
        const params = new URLSearchParams();
        const context   = document.getElementById('filter-context').value;
        const event     = document.getElementById('filter-event').value;
        const begin     = document.getElementById('filter-begin').value;
        const end       = document.getElementById('filter-end').value;
        const limit     = document.getElementById('filter-limit').value;
        const recipient = document.getElementById('filter-recipient').value.trim();

        if (context)   params.set('context', context);
        if (event)     params.set('event', event);
        if (begin)     params.set('begin', begin);
        if (end)       params.set('end', end);
        if (limit)     params.set('limit', limit);
        if (recipient) params.set('recipient', recipient);

        return params;
    }

    async function doFetch(params, isLoading = true) {
        if (isLoading) {
            btnFetch.disabled = true;
            btnFetch.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Memuat...';
            btnPrev.disabled  = true;
            btnNext.disabled  = true;
        }
        alertBox.innerHTML = '';
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4"><span class="spinner-border spinner-border-sm me-2"></span>Mengambil data dari Mailgun...</td></tr>';

        try {
            const res  = await fetch(`/api/settings/mailgun-logs/events?${params.toString()}`, {
                headers: { 'Accept': 'application/json' },
            });
            const data = await res.json();

            if (!res.ok || !data.success) {
                alertBox.innerHTML = `<div class="alert alert-danger m-3">${escHtml(data.message || 'Gagal mengambil log')}</div>`;
                tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">Gagal memuat data.</td></tr>';
                totalBadge.textContent = '0';
                logFooter.hidden = true;
                return;
            }

            if (data.warning) {
                alertBox.innerHTML = `<div class="alert alert-warning m-3">${escHtml(data.warning)}</div>`;
            }

            totalBadge.textContent = data.total ?? data.data?.length ?? 0;
            renderRows(data.data || []);
            updatePagination(data);
        } catch (err) {
            alertBox.innerHTML = `<div class="alert alert-danger m-3">Terjadi kesalahan: ${escHtml(err.message)}</div>`;
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">Error.</td></tr>';
        } finally {
            btnFetch.disabled = false;
            btnFetch.innerHTML = '<i class="bx bx-refresh me-1"></i>Muat Log';
        }
    }

    // First-page / filter reset fetch
    btnFetch.addEventListener('click', function () {
        currentPage  = 1;
        nextCursor   = null;
        prevCursor   = null;
        cursorStack  = [];
        logFooter.hidden = true;
        doFetch(buildBaseParams());
    });

    // Next page
    btnNext.addEventListener('click', function () {
        if (!nextCursor) return;
        // Save current cursors so we can go back
        cursorStack.push({ next: nextCursor, prev: prevCursor });
        currentPage++;
        const params = buildBaseParams();
        params.set('cursor', nextCursor);
        doFetch(params, false);
    });

    // Previous page
    btnPrev.addEventListener('click', function () {
        if (currentPage <= 1) return;
        cursorStack.pop();
        currentPage--;
        const params = buildBaseParams();
        if (currentPage > 1 && cursorStack.length > 0) {
            // Use the saved "next" cursor of the previous page
            params.set('cursor', cursorStack[cursorStack.length - 1].next);
        }
        // page 1 has no cursor — just fetch fresh
        doFetch(params, false);
    });

    // Reset pagination when any filter changes
    ['filter-context','filter-event','filter-begin','filter-end','filter-limit','filter-recipient']
        .forEach(id => {
            const el = document.getElementById(id);
            if (el) el.addEventListener('change', () => {
                currentPage = 1; nextCursor = null; prevCursor = null; cursorStack = [];
                logFooter.hidden = true;
            });
        });
})();
</script>
@endpush
@endsection
