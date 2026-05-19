@extends('layouts.app')

@section('title', 'Kalender Ulang Tahun')

@push('styles')
<style>
    .calendar-grid {
        display: grid;
        grid-template-columns: repeat(7, minmax(0, 1fr));
        gap: 8px;
        width: 100%;
    }

    .calendar-weekday {
        text-align: center;
        font-size: 0.8rem;
        font-weight: 600;
        color: #697a8d;
        padding: 6px 4px;
    }

    .calendar-cell {
        min-height: 112px;
        border: 1px solid #e7e7ff;
        border-radius: 10px;
        padding: 8px;
        background: #fff;
        position: relative;
        min-width: 0;
        overflow: hidden;
    }

    .calendar-cell.is-muted {
        background: #f8f9fd;
        border-style: dashed;
    }

    .calendar-cell.is-today {
        border-color: #696cff;
        box-shadow: 0 0 0 1px #696cff inset;
    }

    .calendar-day-number {
        font-size: 0.85rem;
        font-weight: 700;
        color: #566a7f;
        margin-bottom: 6px;
    }

    .event-chip {
        display: block;
        width: 100%;
        max-width: 100%;
        min-width: 0;
        font-size: 0.73rem;
        padding: 3px 6px;
        border-radius: 6px;
        margin-bottom: 4px;
        line-height: 1.2;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        box-sizing: border-box;
    }

    .card-body {
        overflow-x: hidden;
    }

    .event-chip.agenda {
        background: #fff2d6;
        color: #8b5e00;
    }

    .event-chip.birthday {
        background: #ffe3ea;
        color: #a1204e;
    }

    .calendar-legend {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-right: 14px;
        margin-bottom: 8px;
        font-size: 0.8rem;
        color: #697a8d;
    }

    .calendar-legend-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        display: inline-block;
    }

    .dot-agenda {
        background: #ffb300;
    }

    .dot-birthday {
        background: #e91e63;
    }

    .calendar-month-title {
        font-size: 1.05rem;
        font-weight: 700;
        color: #2d3748;
    }

    .birth-list-empty {
        text-align: center;
        color: #8592a3;
        padding: 1.5rem;
    }

    @media (max-width: 992px) {
        .calendar-cell {
            min-height: 96px;
            padding: 6px;
        }

        .event-chip {
            font-size: 0.68rem;
        }
    }
</style>
@endpush

@section('content')
<div class="row">
    <div class="col-12 mb-4">
        <div class="card">
            <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h4 class="mb-1">Kalender Ulang Tahun</h4>
                    <p class="mb-0 text-muted">Lihat ulang tahun semua karyawan aktif per bulan.</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('admin.calendar.export-birthday') }}" class="btn btn-success btn-sm" id="btnExportBirthday">
                        <i class='bx bx-download'></i> Export Ulang Tahun
                    </a>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="btnPrevMonth">
                        <i class='bx bx-chevron-left'></i> Bulan Sebelumnya
                    </button>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="btnNextMonth">
                        Bulan Berikutnya <i class='bx bx-chevron-right'></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="m-0">Kalender Ulang Tahun</h5>
                <span class="calendar-month-title" id="birthdayMonthTitle">-</span>
            </div>
            <div class="card-body">
                <div class="calendar-grid mb-2" id="birthdayWeekdays"></div>
                <div class="calendar-grid" id="birthdayCalendar"></div>
            </div>
        </div>
    </div>

    <div class="col-12 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="m-0">Daftar Ulang Tahun Bulan Ini</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Nama Karyawan</th>
                                <th>NIK/Kode</th>
                                <th>Sub Departemen</th>
                                <th>Usia</th>
                            </tr>
                        </thead>
                        <tbody id="birthdayListBody">
                            <tr>
                                <td colspan="5" class="text-center text-muted">Memuat data...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        const state = {
            year: new Date().getFullYear(),
            month: new Date().getMonth() + 1,
        };

        const weekdayLabels = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];

        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function monthLabel(year, month) {
            return new Intl.DateTimeFormat('id-ID', {
                month: 'long',
                year: 'numeric'
            }).format(new Date(year, month - 1, 1));
        }

        function ymd(year, month, day) {
            const m = String(month).padStart(2, '0');
            const d = String(day).padStart(2, '0');
            return `${year}-${m}-${d}`;
        }

        function fillWeekdayHeader(elId) {
            const html = weekdayLabels.map((label) => {
                return `<div class="calendar-weekday">${label}</div>`;
            }).join('');

            document.getElementById(elId).innerHTML = html;
        }

        function renderCalendar(containerId, events, eventClassName) {
            const container = document.getElementById(containerId);
            const firstDayIndex = new Date(state.year, state.month - 1, 1).getDay();
            const daysInMonth = new Date(state.year, state.month, 0).getDate();
            const today = new Date();

            const map = {};
            events.forEach((item) => {
                if (!map[item.date]) {
                    map[item.date] = [];
                }
                map[item.date].push(item);
            });

            let html = '';

            for (let i = 0; i < firstDayIndex; i++) {
                html += `<div class="calendar-cell is-muted"></div>`;
            }

            for (let day = 1; day <= daysInMonth; day++) {
                const dateKey = ymd(state.year, state.month, day);
                const dayEvents = map[dateKey] || [];
                const isToday = today.getFullYear() === state.year
                    && (today.getMonth() + 1) === state.month
                    && today.getDate() === day;

                const eventHtml = dayEvents.slice(0, 2).map((evt) => {
                    return `<span class="event-chip ${eventClassName}" title="${escapeHtml(evt.title)}">${escapeHtml(evt.title)}</span>`;
                }).join('');

                const moreCount = dayEvents.length > 2 ? dayEvents.length - 2 : 0;
                const moreHtml = moreCount > 0
                    ? `<span class="event-chip ${eventClassName}">+${moreCount} lainnya</span>`
                    : '';

                html += `
                    <div class="calendar-cell ${isToday ? 'is-today' : ''}">
                        <div class="calendar-day-number">${day}</div>
                        ${eventHtml}
                        ${moreHtml}
                    </div>
                `;
            }

            container.innerHTML = html;
        }

        function renderBirthdayList(events) {
            const body = document.getElementById('birthdayListBody');

            if (!events.length) {
                body.innerHTML = '<tr><td colspan="5" class="birth-list-empty">Tidak ada ulang tahun di bulan ini.</td></tr>';
                return;
            }

            const rows = events.map((item) => {
                const dateLabel = item.date.split('-').reverse().join('-');

                return `
                    <tr>
                        <td>${escapeHtml(dateLabel)}</td>
                        <td>${escapeHtml(item.employee_name)}</td>
                        <td>${escapeHtml(item.employee_code || '-')}</td>
                        <td>${escapeHtml(item.sub_department || '-')}</td>
                        <td>${escapeHtml(item.age)} tahun</td>
                    </tr>
                `;
            }).join('');

            body.innerHTML = rows;
        }

        function updateTitles() {
            const title = monthLabel(state.year, state.month);
            document.getElementById('birthdayMonthTitle').textContent = title;

            const exportBtn = document.getElementById('btnExportBirthday');
            const params = new URLSearchParams({
                year: String(state.year),
                month: String(state.month)
            });
            exportBtn.setAttribute('href', `/admin/calendar/export-birthday?${params.toString()}`);
        }

        function loadData() {
            updateTitles();

            $.ajax({
                url: '/api/admin/calendar/events',
                method: 'GET',
                data: {
                    year: state.year,
                    month: state.month
                },
                success: function (response) {
                    const data = response?.data || {};
                    const birthdayEvents = data.birthday_events || [];

                    renderCalendar('birthdayCalendar', birthdayEvents, 'birthday');
                    renderBirthdayList(birthdayEvents);
                },
                error: function (xhr) {
                    const message = xhr?.responseJSON?.message || 'Gagal memuat data kalender';
                    if (typeof Swal !== 'undefined') {
                        Swal.fire('Error', message, 'error');
                    }
                }
            });
        }

        function moveMonth(offset) {
            state.month += offset;

            if (state.month < 1) {
                state.month = 12;
                state.year -= 1;
            }

            if (state.month > 12) {
                state.month = 1;
                state.year += 1;
            }

            loadData();
        }

        document.addEventListener('DOMContentLoaded', function () {
            fillWeekdayHeader('birthdayWeekdays');
            loadData();

            document.getElementById('btnPrevMonth').addEventListener('click', function () {
                moveMonth(-1);
            });

            document.getElementById('btnNextMonth').addEventListener('click', function () {
                moveMonth(1);
            });
        });
    })();
</script>
@endpush
