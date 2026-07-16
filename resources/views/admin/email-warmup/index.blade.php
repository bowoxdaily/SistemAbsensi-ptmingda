@extends('layouts.app')

@section('title', 'Email Warmup Manager')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="row">
        <!-- Status Card -->
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Email Warmup Status</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label class="small">Status:</label>
                            <h6><span class="badge" id="statusBadge">Loading...</span></h6>
                        </div>
                        <div class="col-md-3">
                            <label class="small">Progress:</label>
                            <h6 id="progressText">-</h6>
                            <div class="progress" style="height: 1.5rem;">
                                <div class="progress-bar" id="progressBar" role="progressbar" style="width: 0%"></div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="small">Emails Today:</label>
                            <h6 id="emailsToday">-</h6>
                        </div>
                        <div class="col-md-3">
                            <label class="small">Reputation:</label>
                            <h6 id="reputationScore">-</h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Control Buttons -->
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex gap-2">
                        <button class="btn btn-primary" id="btnStart" data-bs-toggle="modal" data-bs-target="#startModal">
                            <i class="bx bx-play"></i> Start Warmup
                        </button>
                        <button class="btn btn-warning" id="btnPause" disabled>
                            <i class="bx bx-pause"></i> Pause
                        </button>
                        <button class="btn btn-info" id="btnResume" disabled>
                            <i class="bx bx-play"></i> Resume
                        </button>
                        <button class="btn btn-danger" id="btnStop" disabled>
                            <i class="bx bx-stop"></i> Stop
                        </button>
                        <button class="btn btn-secondary" id="btnRefresh">
                            <i class="bx bx-refresh"></i> Refresh
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-2">
                            <div class="text-center">
                                <h6 id="statTotalSent">-</h6>
                                <small>Total Sent</small>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="text-center">
                                <h6 id="statDelivered">-</h6>
                                <small>Delivered</small>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="text-center text-danger">
                                <h6 id="statBounced">-</h6>
                                <small>Bounced</small>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="text-center text-warning">
                                <h6 id="statSpam">-</h6>
                                <small>Spam</small>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="text-center">
                                <h6 id="deliveryRate">-</h6>
                                <small>Delivery Rate</small>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="text-center">
                                <h6 id="spamRate">-</h6>
                                <small>Spam Rate</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recommendation -->
        <div class="col-md-12 mb-4">
            <div class="alert alert-info" id="recommendationBox" style="display: none;"></div>
        </div>

        <!-- Logs -->
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Recent Warmup Logs</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Recipient</th>
                                <th>Subject</th>
                                <th>Status</th>
                                <th>Day</th>
                                <th>Sent At</th>
                            </tr>
                        </thead>
                        <tbody id="logsTable">
                            <tr><td colspan="5" class="text-center">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Start Modal -->
<div class="modal fade" id="startModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Start Email Warmup</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="startForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Total Days</label>
                        <input type="number" class="form-control" name="total_days" value="30" min="1" max="90">
                        <small class="text-muted">Duration of warmup (1-90 days)</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Starting Volume</label>
                        <input type="number" class="form-control" name="start_volume" value="10" min="1" max="100">
                        <small class="text-muted">Emails per day on day 1</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Maximum Volume</label>
                        <input type="number" class="form-control" name="max_volume" value="500" min="10" max="5000">
                        <small class="text-muted">Target volume at end of warmup</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Daily Increase (%)</label>
                        <input type="number" class="form-control" name="increase_percentage" value="15" min="0.1" max="50" step="0.1">
                        <small class="text-muted">Volume increase percentage each day</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Start Warmup</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
const API_BASE = '/api/email-warmup';

$(document).ready(function() {
    loadStatus();
    loadLogs();
    
    // Refresh every 30 seconds
    setInterval(loadStatus, 30000);
});

// Start warmup
$('#startForm').on('submit', function(e) {
    e.preventDefault();
    
    $.ajax({
        url: API_BASE + '/start',
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        data: $(this).serialize(),
        success: function(response) {
            Swal.fire('Berhasil', 'Email warmup dimulai', 'success');
            bootstrap.Modal.getInstance(document.getElementById('startModal')).hide();
            loadStatus();
        },
        error: function(xhr) {
            Swal.fire('Error', xhr.responseJSON?.message || 'Terjadi kesalahan', 'error');
        }
    });
});

// Pause
$('#btnPause').on('click', function() {
    $.ajax({
        url: API_BASE + '/pause',
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            Swal.fire('Berhasil', 'Email warmup dijeda', 'success');
            loadStatus();
        }
    });
});

// Resume
$('#btnResume').on('click', function() {
    $.ajax({
        url: API_BASE + '/resume',
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            Swal.fire('Berhasil', 'Email warmup dilanjutkan', 'success');
            loadStatus();
        }
    });
});

// Stop
$('#btnStop').on('click', function() {
    Swal.fire({
        title: 'Confirm',
        text: 'Apakah Anda yakin ingin menghentikan warmup?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Hentikan'
    }).then(result => {
        if (result.isConfirmed) {
            $.ajax({
                url: API_BASE + '/stop',
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    Swal.fire('Berhasil', 'Email warmup dihentikan', 'success');
                    loadStatus();
                }
            });
        }
    });
});

// Refresh
$('#btnRefresh').on('click', loadStatus);

function loadStatus() {
    $.ajax({
        url: API_BASE + '/status',
        method: 'GET',
        success: function(response) {
            const data = response.data;
            const stats = data.statistics;

            // Update status badge
            const statusColors = {
                'active': 'bg-success',
                'paused': 'bg-warning',
                'inactive': 'bg-secondary',
                'completed': 'bg-info'
            };
            $('#statusBadge')
                .removeClass('bg-success bg-warning bg-secondary bg-info')
                .addClass(statusColors[data.status] || 'bg-secondary')
                .text(data.status.toUpperCase());

            // Update progress
            $('#progressText').text(`Day ${data.current_day}/${data.total_days}`);
            $('#progressBar').css('width', data.progress_percentage + '%').text(data.progress_percentage + '%');

            // Update emails
            $('#emailsToday').text(`${data.emails_sent_today}/${data.emails_allowed_today}`);

            // Update reputation
            const repColor = stats.sender_reputation >= 90 ? 'success' : stats.sender_reputation >= 75 ? 'info' : 'warning';
            $('#reputationScore').html(`<span class="text-${repColor}">${stats.sender_reputation.toFixed(0)}/100 (${stats.reputation_status})</span>`);

            // Update statistics
            $('#statTotalSent').text(stats.total_sent);
            $('#statDelivered').text(stats.total_delivered);
            $('#statBounced').text(stats.total_bounced);
            $('#statSpam').text(stats.total_spam);
            $('#deliveryRate').text(stats.delivery_rate.toFixed(2) + '%');
            $('#spamRate').text(stats.spam_rate.toFixed(2) + '%');

            // Update buttons
            $('#btnStart').prop('disabled', data.status !== 'inactive');
            $('#btnPause').prop('disabled', data.status !== 'active');
            $('#btnResume').prop('disabled', data.status !== 'paused');
            $('#btnStop').prop('disabled', data.status === 'inactive' || data.status === 'completed');

            // Load recommendations
            loadRecommendations();
        }
    });
}

function loadRecommendations() {
    $.ajax({
        url: API_BASE + '/recommendations',
        method: 'GET',
        success: function(response) {
            $('#recommendationBox')
                .text(response.recommendation)
                .show();
        }
    });
}

function loadLogs() {
    $.ajax({
        url: API_BASE + '/logs',
        method: 'GET',
        data: { per_page: 20 },
        success: function(response) {
            const logs = response.data.data || [];
            const html = logs.map(log => `
                <tr>
                    <td>${log.recipient_email}</td>
                    <td>${log.subject}</td>
                    <td><span class="badge bg-${getStatusColor(log.status)}">${log.status}</span></td>
                    <td>${log.warmup_day}</td>
                    <td>${new Date(log.sent_at).toLocaleString()}</td>
                </tr>
            `).join('');
            $('#logsTable').html(html || '<tr><td colspan="5" class="text-center">No logs yet</td></tr>');
        }
    });
}

function getStatusColor(status) {
    const colors = {
        'sent': 'primary',
        'delivered': 'success',
        'bounced': 'danger',
        'spam': 'warning'
    };
    return colors[status] || 'secondary';
}
</script>
@endpush
@endsection
