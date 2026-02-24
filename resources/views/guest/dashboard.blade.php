@extends('layouts.guest')
@section('title', 'Monitoring Dashboard')

@section('styles')
<style>
/* ── Hero Banner ── */
.hero-banner {
    background: linear-gradient(135deg, #696cff 0%, #8f8cff 50%, #9b59b6 100%);
    border-radius: 14px;
    padding: 1.6rem 2rem;
    color: #fff;
    margin-bottom: 1.8rem;
    position: relative;
    overflow: hidden;
}
.hero-banner::before {
    content: '';
    position: absolute;
    right: -40px; top: -40px;
    width: 200px; height: 200px;
    border-radius: 50%;
    background: rgba(255,255,255,.07);
}
.hero-banner::after {
    content: '';
    position: absolute;
    right: 60px; bottom: -60px;
    width: 150px; height: 150px;
    border-radius: 50%;
    background: rgba(255,255,255,.05);
}
.hero-banner .clock { font-size: 2.4rem; font-weight: 700; letter-spacing: .02em; line-height: 1; }
.hero-banner .date-text { font-size: .9rem; opacity: .85; margin-top: .2rem; }
.hero-banner .company { font-size: .78rem; opacity: .7; text-transform: uppercase; letter-spacing: .08em; }
.hero-banner .last-refresh { font-size: .72rem; opacity: .7; }

/* ── Section Header ── */
.sec-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: .9rem;
}
.sec-header .sec-title {
    font-size: .82rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .08em;
    color: #6e7891;
    display: flex; align-items: center; gap: .3rem;
}
.sec-header .sec-title i { font-size: 1rem; }

/* ── Metric Cards ── */
.metric-card {
    border-radius: 12px;
    border: none;
    transition: transform .15s, box-shadow .15s;
    overflow: hidden;
    position: relative;
}
.metric-card:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(0,0,0,.12) !important; }
.metric-card .mc-icon {
    width: 44px; height: 44px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.4rem; flex-shrink: 0;
}
.metric-card .mc-num { font-size: 1.8rem; font-weight: 800; line-height: 1; }
.metric-card .mc-label { font-size: .72rem; text-transform: uppercase; letter-spacing: .06em; color: #8a90a4; margin-top: .2rem; }
.metric-card .mc-sublabel { font-size: .65rem; color: #b0b7c8; margin-top: .05rem; }

/* ── Attendance Row ── */
.att-row { display: flex; align-items: center; padding: .65rem 1rem; border-bottom: 1px solid #f0f1f5; transition: background .1s; }
.att-row:hover { background: #fafafa; }
.att-row:last-child { border-bottom: none; }
.att-avatar {
    width: 36px; height: 36px; border-radius: 50%;
    background: linear-gradient(135deg, #696cff, #8f8cff);
    color: #fff; font-weight: 700; font-size: .85rem;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; margin-right: .75rem;
}
.att-badge { font-size: .7rem; min-width: 76px; text-align: center; border-radius: 20px; padding: .2rem .6rem; }

/* ── Interview Row ── */
.itv-row { display: flex; align-items: flex-start; padding: .75rem 1rem; border-bottom: 1px solid #f0f1f5; gap: .75rem; }
.itv-row:hover { background: #fafafa; }
.itv-row:last-child { border-bottom: none; }
.itv-date-box {
    min-width: 44px; text-align: center; background: #f5f5ff;
    border-radius: 8px; padding: .3rem .4rem; flex-shrink: 0;
}
.itv-date-box .day { font-size: 1.2rem; font-weight: 800; color: #696cff; line-height: 1; }
.itv-date-box .mon { font-size: .65rem; text-transform: uppercase; color: #a0a7c0; }

/* Refresh pulse */
@keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.4} }
.refreshing { animation: pulse 1s infinite; }
</style>
@endsection

@section('content')

<!-- ── Hero Banner ── -->
<div class="hero-banner shadow-sm mb-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <div class="company mb-1">考勤系统 &mdash; 实时监控 &nbsp;<span style="opacity:.5">|</span>&nbsp; Sistem Absensi &mdash; Live Monitoring</div>
            <div class="clock" id="liveClock">--:--:--</div>
            <div class="date-text" id="liveDate">...</div>
        </div>
        <div class="text-end">
            <div style="font-size:1rem; opacity:.85; font-weight:600">监控仪表盘 <span style="opacity:.5; font-size:.8em">Dashboard Monitoring</span></div>
            <div class="last-refresh mt-1"><i class='bx bx-refresh me-1'></i>更新时间 / Diperbarui: <span id="lastRefresh">-</span></div>
            <button class="btn btn-sm mt-2" style="background:rgba(255,255,255,.2);color:#fff;border:1px solid rgba(255,255,255,.3)" onclick="loadStats()">
                <i class='bx bx-refresh me-1'></i>刷新 / Refresh
            </button>
        </div>
    </div>
</div>

<!-- ── Karyawan Stats ── -->
<div class="sec-header mb-3">
    <div class="sec-title"><i class='bx bxs-group'></i> 员工状态 <span style="opacity:.5;font-weight:400">/ Status Karyawan</span></div>
    <a href="{{ route('guest.karyawan') }}" class="btn btn-xs btn-outline-secondary btn-sm py-0 px-2" style="font-size:.75rem">查看数据 / Lihat Data &rsaquo;</a>
</div>
<div class="row g-3 mb-4">
    @foreach([
        ['id'=>'sumKarTotal',   'label'=>'Total Karyawan', 'cn'=>'员工总数',     'icon'=>'bxs-group',            'bg'=>'linear-gradient(135deg,#696cff,#8f8cff)', 'icon_bg'=>'rgba(255,255,255,.2)', 'text'=>'#fff', 'cnsub'=>'rgba(255,255,255,.7)'],
        ['id'=>'sumKarActive',  'label'=>'Aktif',          'cn'=>'在职',         'icon'=>'bxs-user-check',        'bg'=>'#e8f8f0', 'icon_bg'=>'rgba(40,167,69,.15)', 'text'=>'#1a7a3c', 'cnsub'=>'#5aab80'],
        ['id'=>'sumKarResign',  'label'=>'Resign',         'cn'=>'已离职',       'icon'=>'bxs-user-minus',        'bg'=>'#fdf0f0', 'icon_bg'=>'rgba(220,53,69,.12)', 'text'=>'#b02a37', 'cnsub'=>'#c97070'],
        ['id'=>'sumKarInactive','label'=>'Tidak Aktif',    'cn'=>'未激活',       'icon'=>'bx-user-x',             'bg'=>'#f5f5f5', 'icon_bg'=>'rgba(108,117,125,.12)','text'=>'#555', 'cnsub'=>'#999'],
        ['id'=>'sumKarMangkir', 'label'=>'Mangkir',        'cn'=>'旷工',         'icon'=>'bxs-time-five',         'bg'=>'#fff8ec', 'icon_bg'=>'rgba(255,193,7,.18)', 'text'=>'#856404', 'cnsub'=>'#b89020'],
        ['id'=>'sumKarGagal',   'label'=>'Gagal Probation','cn'=>'试用期未通过', 'icon'=>'bxs-user-detail',       'bg'=>'#edf7fa', 'icon_bg'=>'rgba(23,162,184,.15)','text'=>'#0c6674', 'cnsub'=>'#3899a8'],
    ] as $c)
    <div class="col-6 col-sm-4 col-lg-2">
        <div class="card metric-card shadow-sm h-100" style="background:{{ $c['bg'] }}">
            <div class="card-body d-flex align-items-center gap-3 py-3 px-3">
                <div class="mc-icon" style="background:{{ $c['icon_bg'] }}; color:{{ $c['text'] }}">
                    <i class='bx {{ $c['icon'] }}'></i>
                </div>
                <div>
                    <div class="mc-num" style="color:{{ $c['text'] }}" id="{{ $c['id'] }}">
                        <span class="spinner-border spinner-border-sm"></span>
                    </div>
                    <div class="mc-label">{{ $c['cn'] }}</div>
                    <div class="mc-sublabel" style="color:{{ $c['cnsub'] }}">{{ $c['label'] }}</div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

<!-- ── Absensi Hari Ini ── -->
<div class="sec-header mb-3">
    <div class="sec-title"><i class='bx bxs-calendar-check'></i> 今日考勤 <span style="opacity:.5;font-weight:400">/ Absensi Hari Ini</span></div>
    <a href="{{ route('guest.absensi') }}" class="btn btn-xs btn-outline-secondary btn-sm py-0 px-2" style="font-size:.75rem">查看数据 / Lihat Data &rsaquo;</a>
</div>
<div class="row g-3 mb-4">
    @foreach([
        ['id'=>'sumHadir', 'label'=>'Hadir',          'cn'=>'出勤',          'icon'=>'bxs-check-circle',     'bg'=>'linear-gradient(135deg,#28a745,#48c767)', 'icon_bg'=>'rgba(255,255,255,.2)', 'text'=>'#fff'],
        ['id'=>'sumLate',  'label'=>'Terlambat',      'cn'=>'迟到',          'icon'=>'bxs-alarm-exclamation','bg'=>'linear-gradient(135deg,#fd7e14,#ff9a40)', 'icon_bg'=>'rgba(255,255,255,.2)', 'text'=>'#fff'],
        ['id'=>'sumAlpha', 'label'=>'Alpha',           'cn'=>'缺勤',          'icon'=>'bxs-x-circle',         'bg'=>'linear-gradient(135deg,#dc3545,#f05060)', 'icon_bg'=>'rgba(255,255,255,.2)', 'text'=>'#fff'],
        ['id'=>'sumIzin',  'label'=>'Izin/Sakit/Cuti','cn'=>'请假/病假/年假','icon'=>'bxs-file-doc',         'bg'=>'linear-gradient(135deg,#17a2b8,#36bdd4)', 'icon_bg'=>'rgba(255,255,255,.2)', 'text'=>'#fff'],
    ] as $c)
    <div class="col-6 col-md-3">
        <div class="card metric-card shadow-sm h-100" style="background:{{ $c['bg'] }}">
            <div class="card-body d-flex align-items-center gap-3 py-3 px-3">
                <div class="mc-icon" style="background:{{ $c['icon_bg'] }}; color:{{ $c['text'] }}">
                    <i class='bx {{ $c['icon'] }}'></i>
                </div>
                <div>
                    <div class="mc-num" style="color:{{ $c['text'] }}" id="{{ $c['id'] }}">
                        <span class="spinner-border spinner-border-sm"></span>
                    </div>
                    <div class="mc-label" style="color:rgba(255,255,255,.9)">{{ $c['cn'] }}</div>
                    <div class="mc-sublabel" style="color:rgba(255,255,255,.65)">{{ $c['label'] }}</div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

<!-- ── Interview Stats ── -->
<div class="sec-header mb-3">
    <div class="sec-title"><i class='bx bxs-conversation'></i> 面试统计 <span style="opacity:.5;font-weight:400">/ Statistik Interview</span></div>
    <a href="{{ route('guest.interview') }}" class="btn btn-xs btn-outline-secondary btn-sm py-0 px-2" style="font-size:.75rem">查看数据 / Lihat Data &rsaquo;</a>
</div>
<div class="row g-3 mb-4">
    @foreach([
        ['id'=>'sumItvTotal',    'label'=>'Total',        'cn'=>'总计',   'icon'=>'bxs-data',           'bg'=>'#f0f0ff', 'icon_bg'=>'rgba(105,108,255,.15)', 'text'=>'#4a4db8', 'sub'=>'#8a8fcc'],
        ['id'=>'sumItvScheduled','label'=>'Terjadwal',   'cn'=>'已安排', 'icon'=>'bxs-calendar-event', 'bg'=>'#fff8ec', 'icon_bg'=>'rgba(255,193,7,.18)',   'text'=>'#856404', 'sub'=>'#b89020'],
        ['id'=>'sumItvConfirmed','label'=>'Dikonfirmasi','cn'=>'已确认', 'icon'=>'bxs-badge-check',    'bg'=>'#e8f8f0', 'icon_bg'=>'rgba(40,167,69,.15)',   'text'=>'#1a7a3c', 'sub'=>'#5aab80'],
        ['id'=>'sumItvToday',   'label'=>'Hari Ini',    'cn'=>'今日',   'icon'=>'bxs-calendar-star',  'bg'=>'#edf7fa', 'icon_bg'=>'rgba(23,162,184,.15)',  'text'=>'#0c6674', 'sub'=>'#3899a8'],
    ] as $c)
    <div class="col-6 col-md-3">
        <div class="card metric-card shadow-sm h-100" style="background:{{ $c['bg'] }}">
            <div class="card-body d-flex align-items-center gap-3 py-3 px-3">
                <div class="mc-icon" style="background:{{ $c['icon_bg'] }}; color:{{ $c['text'] }}">
                    <i class='bx {{ $c['icon'] }}'></i>
                </div>
                <div>
                    <div class="mc-num" style="color:{{ $c['text'] }}" id="{{ $c['id'] }}">
                        <span class="spinner-border spinner-border-sm"></span>
                    </div>
                    <div class="mc-label">{{ $c['cn'] }}</div>
                    <div class="mc-sublabel" style="color:{{ $c['sub'] }}">{{ $c['label'] }}</div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

<!-- ── Bottom Two Columns ── -->
<div class="row g-4">

    <!-- Recent Attendance -->
    <div class="col-12 col-xl-7">
        <div class="card shadow-sm h-100" style="border-radius:12px;border:none">
            <div class="card-header bg-white d-flex justify-content-between align-items-center" style="border-radius:12px 12px 0 0; border-bottom:1px solid #f0f1f5">
                <div class="d-flex align-items-center gap-2">
                    <div style="width:32px;height:32px;border-radius:8px;background:#eef;display:flex;align-items:center;justify-content:center">
                        <i class='bx bx-time-five' style="color:#696cff;font-size:1.1rem"></i>
                    </div>
                    <span class="fw-semibold" style="font-size:.9rem">今日最新考勤 <span class="text-muted fw-normal" style="font-size:.78rem">/ Absensi Terbaru</span></span>
                </div>
                <a href="{{ route('guest.absensi') }}" class="btn btn-sm btn-primary" style="font-size:.75rem;border-radius:20px;padding:.2rem .8rem">全部 / Semua &rsaquo;</a>
            </div>
            <div class="card-body p-0" id="recentAttBody">
                <div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>
            </div>
        </div>
    </div>

    <!-- Upcoming Interviews -->
    <div class="col-12 col-xl-5">
        <div class="card shadow-sm h-100" style="border-radius:12px;border:none">
            <div class="card-header bg-white d-flex justify-content-between align-items-center" style="border-radius:12px 12px 0 0; border-bottom:1px solid #f0f1f5">
                <div class="d-flex align-items-center gap-2">
                    <div style="width:32px;height:32px;border-radius:8px;background:#eef;display:flex;align-items:center;justify-content:center">
                        <i class='bx bx-calendar-event' style="color:#696cff;font-size:1.1rem"></i>
                    </div>
                    <span class="fw-semibold" style="font-size:.9rem">即将到来的面试 <span class="text-muted fw-normal" style="font-size:.78rem">/ Interview Mendatang</span></span>
                </div>
                <a href="{{ route('guest.interview') }}" class="btn btn-sm btn-primary" style="font-size:.75rem;border-radius:20px;padding:.2rem .8rem">全部 / Semua &rsaquo;</a>
            </div>
            <div class="card-body p-0" id="upcomingInterviews">
                <div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>
            </div>
        </div>
    </div>

</div>
@endsection

@section('scripts')
<script>
const ATTENDANCE_STATUS = {
    hadir:     { label: '出勤',          cls: 'bg-success' },
    terlambat: { label: '迟到',          cls: 'bg-warning text-dark' },
    alpha:     { label: '缺勤',          cls: 'bg-danger' },
    izin:      { label: '请假',          cls: 'bg-info text-dark' },
    sakit:     { label: '病假',          cls: 'bg-secondary' },
    cuti:      { label: '年假',          cls: 'bg-primary' },
};
const INTERVIEW_STATUS = {
    scheduled:  { label: '已安排',  cls: 'bg-warning text-dark' },
    confirmed:  { label: '已确认',  cls: 'bg-success' },
    completed:  { label: '已完成',  cls: 'bg-primary' },
    cancelled:  { label: '已取消',  cls: 'bg-danger' },
};
const MONTHS = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
const DAYS   = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];

/* Live clock */
function tickClock() {
    const now = new Date();
    const hh = String(now.getHours()).padStart(2,'0');
    const mm = String(now.getMinutes()).padStart(2,'0');
    const ss = String(now.getSeconds()).padStart(2,'0');
    $('#liveClock').text(`${hh}:${mm}:${ss}`);
    $('#liveDate').text(`${DAYS[now.getDay()]}, ${now.getDate()} ${MONTHS[now.getMonth()]} ${now.getFullYear()}`);
}

$(function() {
    tickClock();
    setInterval(tickClock, 1000);
    loadStats();
    setInterval(loadStats, 120000);
});

function loadStats() {
    $('#liveClock').addClass('refreshing');
    $.get('/api/guest/stats').done(function(res) {
        $('#liveClock').removeClass('refreshing');
        if (!res.success) return;

        const k = res.karyawan  || {};
        const a = res.absensi   || {};
        const i = res.interview || {};

        $('#sumKarTotal').text(k.total           ?? 0);
        $('#sumKarActive').text(k.active          ?? 0);
        $('#sumKarResign').text(k.resign          ?? 0);
        $('#sumKarInactive').text(k.inactive      ?? 0);
        $('#sumKarMangkir').text(k.mangkir        ?? 0);
        $('#sumKarGagal').text(k.gagal_probation  ?? 0);

        $('#sumHadir').text(a.hadir     ?? 0);
        $('#sumLate').text(a.terlambat  ?? 0);
        $('#sumAlpha').text(a.alpha     ?? 0);
        $('#sumIzin').text((a.izin ?? 0) + (a.sakit ?? 0) + (a.cuti ?? 0));

        $('#sumItvTotal').text(i.total     ?? 0);
        $('#sumItvScheduled').text(i.scheduled ?? 0);
        $('#sumItvConfirmed').text(i.confirmed  ?? 0);
        $('#sumItvToday').text(i.hari_ini      ?? 0);

        const recentArr   = res.recent_attendance   ?? [];
        const upcomingArr = res.upcoming_interviews ?? [];
        renderRecentAttendance(recentArr);
        renderUpcomingInterviews(upcomingArr);

        const now = new Date();
        $('#lastRefresh').text(`${String(now.getHours()).padStart(2,'0')}:${String(now.getMinutes()).padStart(2,'0')}:${String(now.getSeconds()).padStart(2,'0')}`);
    }).fail(function() {
        $('#liveClock').removeClass('refreshing');
    });
}

function initials(name) {
    return (name || '?').split(' ').slice(0,2).map(w => w[0]).join('').toUpperCase();
}

function renderRecentAttendance(rows) {
    if (!rows || !rows.length) {
        $('#recentAttBody').html('<div class="text-center text-muted py-4" style="font-size:.85rem"><i class=\'bx bx-calendar-x\' style="font-size:2rem;display:block;margin-bottom:.3rem"></i>今日暂无考勤记录<br><span style="font-size:.75rem">Belum ada absensi hari ini</span></div>');
        return;
    }
    let html = '';
    rows.forEach(function(a) {
        const s = ATTENDANCE_STATUS[a.status] || { label: a.status, cls: 'bg-secondary' };
        const cin  = a.check_in  ? String(a.check_in).substring(0,5)  : '-';
        const cout = a.check_out ? String(a.check_out).substring(0,5) : '-';
        html += `<div class="att-row">
            <div class="att-avatar">${initials(a.name)}</div>
            <div class="flex-grow-1 overflow-hidden">
                <div class="fw-semibold text-truncate" style="font-size:.85rem">${a.name}</div>
                <div class="text-muted text-truncate" style="font-size:.72rem">${a.department ?? '-'}</div>
            </div>
            <div class="text-center mx-2" style="font-size:.78rem; min-width:80px">
                <div><i class='bx bx-log-in text-success'></i> ${cin}</div>
                <div><i class='bx bx-log-out text-danger'></i> ${cout}</div>
            </div>
            <span class="badge att-badge ${s.cls}">${s.label}</span>
        </div>`;
    });
    $('#recentAttBody').html(html);
}

function renderUpcomingInterviews(rows) {
    if (!rows || !rows.length) {
        $('#upcomingInterviews').html('<div class="text-center text-muted py-4" style="font-size:.85rem"><i class=\'bx bx-calendar-x\' style="font-size:2rem;display:block;margin-bottom:.3rem"></i>暂无即将到来的面试<br><span style="font-size:.75rem">Tidak ada interview mendatang</span></div>');
        return;
    }
    let html = '';
    rows.forEach(function(iv) {
        const s = INTERVIEW_STATUS[iv.status] || { label: iv.status, cls: 'bg-secondary' };
        const rawDate = String(iv.interview_date ?? '');
        const parts = rawDate.match(/(\d{4})-(\d{2})-(\d{2})/);
        let dayNum = '-', monStr = '';
        if (parts) {
            dayNum = parseInt(parts[3]);
            monStr = MONTHS[parseInt(parts[2]) - 1];
        }
        html += `<div class="itv-row">
            <div class="itv-date-box">
                <div class="day">${dayNum}</div>
                <div class="mon">${monStr}</div>
            </div>
            <div class="flex-grow-1 overflow-hidden">
                <div class="fw-semibold text-truncate" style="font-size:.85rem">${iv.candidate_name ?? '-'}</div>
                <div class="text-muted text-truncate" style="font-size:.72rem">${iv.position ?? '-'}</div>
                <div class="text-muted" style="font-size:.72rem">
                    <i class='bx bx-time'></i> ${iv.interview_time ? String(iv.interview_time).substring(0,5) : '-'}
                    ${iv.location ? `&nbsp;&bull;&nbsp;<i class='bx bx-map'></i> ${iv.location}` : ''}
                </div>
            </div>
            <span class="badge ${s.cls}" style="font-size:.7rem;border-radius:20px;padding:.2rem .55rem">${s.label}</span>
        </div>`;
    });
    $('#upcomingInterviews').html(html);
}
</script>
@endsection
