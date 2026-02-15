@extends('layouts.app')

@section('title', 'Security Scanner')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold mb-0">
                <i class='bx bx-qr-scan me-2'></i> Security Check-in Scanner
            </h4>
            <div>
                <span class="badge bg-success" id="todayCount">{{ $todayInterviews->count() }} Interview Hari Ini</span>
            </div>
        </div>

        <div class="row">
            <!-- Scanner Card -->
            <div class="col-lg-7 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class='bx bx-camera me-2'></i> Scan QR Code Kandidat
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- Camera View -->
                        <div id="cameraContainer" class="text-center">
                            <div class="position-relative" style="max-width: 500px; margin: 0 auto;">
                                <video id="qrVideo" style="width: 100%; border-radius: 10px; border: 3px solid #696cff;" autoplay playsinline></video>
                                <canvas id="qrCanvas" style="display: none;"></canvas>
                                
                                <!-- Scan Line Animation -->
                                <div id="scanLine" style="position: absolute; top: 0; left: 0; right: 0; height: 2px; background: linear-gradient(90deg, transparent, #696cff, transparent); animation: scan 2s linear infinite; display: none;"></div>
                            </div>
                            
                            <div class="mt-3">
                                <button type="button" class="btn btn-primary" id="startScanBtn">
                                    <i class='bx bx-camera me-1'></i> Mulai Scan
                                </button>
                                <button type="button" class="btn btn-secondary" id="stopScanBtn" style="display: none;">
                                    <i class='bx bx-stop-circle me-1'></i> Stop Scan
                                </button>
                            </div>
                            
                            <div class="alert alert-info mt-3" role="alert">
                                <i class='bx bx-info-circle me-1'></i>
                                <strong>Petunjuk:</strong> Arahkan kamera ke QR Code kandidat untuk scan otomatis
                            </div>
                        </div>

                        <!-- Manual Input Alternative -->
                        <div class="mt-4">
                            <hr>
                            <h6 class="mb-3">Atau Input Manual Token:</h6>
                            <div class="input-group">
                                <input type="text" class="form-control" id="manualToken" placeholder="Masukkan token QR Code">
                                <button class="btn btn-outline-primary" type="button" id="manualCheckBtn">
                                    <i class='bx bx-search me-1'></i> Cek
                                </button>
                            </div>
                        </div>

                        <!-- Candidate Info (shown after scan) -->
                        <div id="candidateInfo" style="display: none;" class="mt-4">
                            <div class="alert alert-success">
                                <h5 class="alert-heading">
                                    <i class='bx bx-check-circle me-2'></i> QR Code Valid!
                                </h5>
                                <hr>
                                <div class="row">
                                    <div class="col-6"><strong>Nama:</strong></div>
                                    <div class="col-6" id="infoName"></div>
                                    
                                    <div class="col-6"><strong>Posisi:</strong></div>
                                    <div class="col-6" id="infoPosition"></div>
                                    
                                    <div class="col-6"><strong>No. HP:</strong></div>
                                    <div class="col-6" id="infoPhone"></div>
                                    
                                    <div class="col-6"><strong>Waktu Interview:</strong></div>
                                    <div class="col-6" id="infoTime"></div>
                                </div>
                                
                                <div class="mt-3 text-center">
                                    <button type="button" class="btn btn-success btn-lg" id="confirmCheckInBtn">
                                        <i class='bx bx-check me-1'></i> Konfirmasi Check-in
                                    </button>
                                    <button type="button" class="btn btn-secondary" id="cancelBtn">
                                        <i class='bx bx-x me-1'></i> Batal
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Today's Schedule -->
            <div class="col-lg-5 mb-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Jadwal Hari Ini</h5>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="refreshScheduleBtn">
                            <i class='bx bx-refresh'></i>
                        </button>
                    </div>
                    <div class="card-body" style="max-height: 600px; overflow-y: auto;">
                        @forelse($todayInterviews as $interview)
                            <div class="d-flex align-items-center mb-3 p-3 border rounded {{ $interview->isCheckedIn() ? 'bg-light' : '' }}">
                                <div class="flex-shrink-0">
                                    @if($interview->isCheckedIn())
                                        <span class="badge bg-success">
                                            <i class='bx bx-check'></i> Check-in
                                        </span>
                                    @else
                                        <span class="badge bg-warning">
                                            <i class='bx bx-time'></i> Pending
                                        </span>
                                    @endif
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="mb-0">{{ $interview->candidate_name }}</h6>
                                    <small class="text-muted">
                                        <i class='bx bx-briefcase'></i> {{ $interview->position->name }}<br>
                                        <i class='bx bx-time'></i> {{ $interview->interview_time }}
                                        @if($interview->isCheckedIn())
                                            <br><i class='bx bx-check-circle text-success'></i> Check-in: {{ $interview->checked_in_at->format('H:i') }}
                                        @endif
                                    </small>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-4 text-muted">
                                <i class='bx bx-calendar-x bx-lg'></i>
                                <p class="mt-2">Tidak ada jadwal interview hari ini</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Check-in History Today -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h6 class="card-title mb-0">Riwayat Check-in Hari Ini</h6>
                    </div>
                    <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                        <div id="historyList">
                            <div class="text-center text-muted py-3">
                                <small>Belum ada check-in hari ini</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        @keyframes scan {
            0% { top: 0; }
            100% { top: 100%; }
        }
        
        #qrVideo {
            background: #000;
        }
    </style>
@endpush

@push('scripts')
    <!-- jsQR Library -->
    <script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
    
    <script>
        let videoStream = null;
        let scanningActive = false;
        let currentInterviewId = null;

        // Start camera scan
        $('#startScanBtn').on('click', function() {
            startCamera();
        });

        // Stop camera scan
        $('#stopScanBtn').on('click', function() {
            stopCamera();
        });

        // Manual token check
        $('#manualCheckBtn').on('click', function() {
            const token = $('#manualToken').val().trim();
            if (token) {
                validateToken(token);
            } else {
                Swal.fire('Error', 'Masukkan token terlebih dahulu', 'error');
            }
        });

        // Confirm check-in
        $('#confirmCheckInBtn').on('click', function() {
            if (currentInterviewId) {
                confirmCheckIn(currentInterviewId);
            }
        });

        // Cancel
        $('#cancelBtn').on('click', function() {
            resetScanUI();
            currentInterviewId = null;
        });

        // Refresh schedule
        $('#refreshScheduleBtn').on('click', function() {
            location.reload();
        });

        // Start camera
        function startCamera() {
            navigator.mediaDevices.getUserMedia({ 
                video: { facingMode: 'environment' } 
            })
            .then(function(stream) {
                videoStream = stream;
                const video = document.getElementById('qrVideo');
                video.srcObject = stream;
                video.play();
                
                scanningActive = true;
                $('#startScanBtn').hide();
                $('#stopScanBtn').show();
                $('#scanLine').show();
                
                requestAnimationFrame(scanQRCode);
            })
            .catch(function(err) {
                console.error('Camera error:', err);
                Swal.fire('Error', 'Tidak dapat mengakses kamera. Pastikan izin kamera telah diberikan.', 'error');
            });
        }

        // Stop camera
        function stopCamera() {
            scanningActive = false;
            if (videoStream) {
                videoStream.getTracks().forEach(track => track.stop());
                videoStream = null;
            }
            $('#startScanBtn').show();
            $('#stopScanBtn').hide();
            $('#scanLine').hide();
        }

        // Scan QR Code
        function scanQRCode() {
            if (!scanningActive) return;

            const video = document.getElementById('qrVideo');
            const canvas = document.getElementById('qrCanvas');
            const context = canvas.getContext('2d');

            if (video.readyState === video.HAVE_ENOUGH_DATA) {
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                context.drawImage(video, 0, 0, canvas.width, canvas.height);
                
                const imageData = context.getImageData(0, 0, canvas.width, canvas.height);
                const code = jsQR(imageData.data, imageData.width, imageData.height);

                if (code) {
                    // Extract token from URL or direct token
                    let token = code.data;
                    
                    // If it's a URL, extract token from it
                    if (token.includes('/interview/scan/')) {
                        const parts = token.split('/');
                        token = parts[parts.length - 1];
                    }

                    console.log('QR Code detected:', token);
                    stopCamera();
                    validateToken(token);
                    return;
                }
            }

            requestAnimationFrame(scanQRCode);
        }

        // Validate token via API
        function validateToken(token) {
            $.ajax({
                url: '/api/security/validate-token',
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                },
                data: JSON.stringify({ token: token }),
                success: function(response) {
                    if (response.success) {
                        showCandidateInfo(response.data);
                    }
                },
                error: function(xhr) {
                    const message = xhr.responseJSON?.message || 'QR Code tidak valid';
                    Swal.fire('Error', message, 'error');
                    
                    if (xhr.responseJSON?.data?.already_checked_in) {
                        const data = xhr.responseJSON.data;
                        Swal.fire({
                            icon: 'info',
                            title: 'Sudah Check-in',
                            html: `
                                <p><strong>${data.candidate_name}</strong> sudah melakukan check-in pada ${data.checked_in_at}</p>
                            `
                        });
                    }
                }
            });
        }

        // Show candidate info
        function showCandidateInfo(data) {
            currentInterviewId = data.id;
            $('#manualToken').val('');
            
            // Parse interview_time to avoid timezone issues (following copilot-instructions.md pattern)
            let interviewTime = String(data.interview_time || '');
            // Extract time portion (HH:MM) from various formats without Date object
            const timeMatch = interviewTime.match(/(\d{1,2}):(\d{2})/);
            if (timeMatch) {
                interviewTime = `${timeMatch[1].padStart(2, '0')}:${timeMatch[2]}`;
            }
            
            Swal.fire({
                icon: 'success',
                title: 'QR Code Valid!',
                html: `
                    <div class="text-start">
                        <table class="table table-borderless">
                            <tr>
                                <td width="40%"><strong>Nama:</strong></td>
                                <td>${data.candidate_name}</td>
                            </tr>
                            <tr>
                                <td><strong>Posisi:</strong></td>
                                <td>${data.position}</td>
                            </tr>
                            <tr>
                                <td><strong>No. HP:</strong></td>
                                <td>${data.phone}</td>
                            </tr>
                            <tr>
                                <td><strong>Waktu Interview:</strong></td>
                                <td>${interviewTime}</td>
                            </tr>
                        </table>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: '<i class="bx bx-check me-1"></i> Konfirmasi Check-in',
                cancelButtonText: '<i class="bx bx-x me-1"></i> Batal',
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                allowOutsideClick: false
            }).then((result) => {
                if (result.isConfirmed) {
                    confirmCheckIn(currentInterviewId);
                } else {
                    resetScanUI();
                    currentInterviewId = null;
                }
            });
        }

        // Confirm check-in
        function confirmCheckIn(interviewId) {
            $('#confirmCheckInBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Processing...');
            
            $.ajax({
                url: '/api/security/checkin',
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                },
                data: JSON.stringify({ interview_id: interviewId }),
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Check-in Berhasil!',
                            html: `<p><strong>${response.data.candidate_name}</strong> telah check-in pada ${response.data.checked_in_at}</p>`,
                            confirmButtonText: 'OK'
                        }).then(() => {
                            resetScanUI();
                            loadHistory();
                            location.reload();
                        });
                    }
                },
                error: function(xhr) {
                    const message = xhr.responseJSON?.message || 'Gagal melakukan check-in';
                    Swal.fire('Error', message, 'error');
                },
                complete: function() {
                    $('#confirmCheckInBtn').prop('disabled', false).html('<i class="bx bx-check me-1"></i> Konfirmasi Check-in');
                }
            });
        }

        // Reset scan UI
        function resetScanUI() {
            $('#candidateInfo').slideUp();
            currentInterviewId = null;
        }

        // Load history
        function loadHistory() {
            $.ajax({
                url: '/api/security/history',
                method: 'GET',
                success: function(response) {
                    if (response.success && response.data.length > 0) {
                        let html = '';
                        response.data.forEach(item => {
                            html += `
                                <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                    <div>
                                        <strong>${item.candidate_name}</strong>
                                        <br><small class="text-muted">${item.position}</small>
                                    </div>
                                    <div class="text-end">
                                        <small class="text-success">
                                            <i class='bx bx-check-circle'></i> ${item.checked_in_at}
                                        </small>
                                    </div>
                                </div>
                            `;
                        });
                        $('#historyList').html(html);
                    }
                }
            });
        }

        // Load history on page load
        $(document).ready(function() {
            loadHistory();
            
            // Auto refresh history every 30 seconds
            setInterval(loadHistory, 30000);
        });
    </script>
@endpush
