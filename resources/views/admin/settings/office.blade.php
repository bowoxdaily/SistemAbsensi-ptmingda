@extends('layouts.app')

@section('title', 'Pengaturan Lokasi Kantor')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">
            <span class="text-muted fw-light">Pengaturan /</span> Lokasi Kantor
        </h4>

        <div class="row">
            <!-- Form Settings -->
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Pengaturan Lokasi Kantor</h5>
                        <span class="badge {{ $setting->enforce_location ? 'bg-success' : 'bg-secondary' }}">
                            {{ $setting->enforce_location ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </div>
                    <div class="card-body">
                        <form id="officeSettingForm">
                            @csrf

                            <div class="mb-3">
                                <label class="form-label" for="office_name">Nama Kantor</label>
                                <input type="text" class="form-control" id="office_name" name="office_name"
                                    value="{{ $setting->office_name }}" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="address">Alamat</label>
                                <textarea class="form-control" id="address" name="address" rows="2">{{ $setting->address }}</textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="latitude">Latitude</label>
                                    <input type="number" step="0.00000001" class="form-control" id="latitude"
                                        name="latitude" value="{{ $setting->latitude }}" required>
                                    <small class="text-muted">Contoh: -6.200000</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="longitude">Longitude</label>
                                    <input type="number" step="0.00000001" class="form-control" id="longitude"
                                        name="longitude" value="{{ $setting->longitude }}" required>
                                    <small class="text-muted">Contoh: 106.816666</small>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="radius_meters">Radius Maksimal (meter)</label>
                                <input type="number" class="form-control" id="radius_meters" name="radius_meters"
                                    value="{{ $setting->radius_meters }}" min="10" max="5000" required>
                                <small class="text-muted">Karyawan harus berada dalam radius ini untuk dapat absen (10-5000
                                    meter)</small>
                            </div>

                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="enforce_location"
                                        name="enforce_location" value="1"
                                        {{ $setting->enforce_location ? 'checked' : '' }}>
                                    <label class="form-check-label" for="enforce_location">
                                        Paksa Validasi Lokasi
                                    </label>
                                </div>
                                <small class="text-muted">Jika diaktifkan, karyawan hanya bisa absen dari dalam radius
                                    kantor</small>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bx bx-save"></i> Simpan Pengaturan
                                </button>
                                <button type="button" class="btn btn-info" id="getMyLocationBtn">
                                    <i class="bx bx-current-location"></i> Gunakan Lokasi Saat Ini
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Map Preview -->
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Peta Lokasi Kantor</h5>
                    </div>
                    <div class="card-body">
                        <div id="map" style="width: 100%; height: 400px; border-radius: 8px;"></div>
                        <p class="mt-3 mb-0">
                            <strong>Koordinat Saat Ini:</strong><br>
                            Lat: <span id="currentLat">{{ $setting->latitude }}</span>,
                            Lng: <span id="currentLng">{{ $setting->longitude }}</span><br>
                            <strong>Radius:</strong> <span id="currentRadius">{{ $setting->radius_meters }}</span> meter
                        </p>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Informasi</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">
                                <i class="bx bx-info-circle text-info"></i>
                                Koordinat dapat ditemukan dengan Google Maps
                            </li>
                            <li class="mb-2">
                                <i class="bx bx-info-circle text-info"></i>
                                Klik kanan pada lokasi di Google Maps → pilih koordinat untuk menyalin
                            </li>
                            <li class="mb-2">
                                <i class="bx bx-info-circle text-info"></i>
                                Atau gunakan tombol "Gunakan Lokasi Saat Ini" untuk menggunakan lokasi browser
                            </li>
                            <li class="mb-0">
                                <i class="bx bx-info-circle text-info"></i>
                                Radius yang disarankan: 50-200 meter untuk gedung perkantoran
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        let map, marker, circle;

        // Initialize map
        function initMap() {
            const lat = parseFloat(document.getElementById('latitude').value);
            const lng = parseFloat(document.getElementById('longitude').value);
            const radius = parseInt(document.getElementById('radius_meters').value);

            // Create map if not exists
            if (!map) {
                map = L.map('map').setView([lat, lng], 16);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors'
                }).addTo(map);
            } else {
                map.setView([lat, lng], 16);
            }

            // Remove existing marker and circle
            if (marker) map.removeLayer(marker);
            if (circle) map.removeLayer(circle);

            // Add marker
            marker = L.marker([lat, lng]).addTo(map)
                .bindPopup('Lokasi Kantor').openPopup();

            // Add circle for radius
            circle = L.circle([lat, lng], {
                color: 'blue',
                fillColor: '#30f',
                fillOpacity: 0.2,
                radius: radius
            }).addTo(map);

            // Update display
            document.getElementById('currentLat').textContent = lat.toFixed(8);
            document.getElementById('currentLng').textContent = lng.toFixed(8);
            document.getElementById('currentRadius').textContent = radius;
        }

        // Update map on input change
        document.getElementById('latitude').addEventListener('input', initMap);
        document.getElementById('longitude').addEventListener('input', initMap);
        document.getElementById('radius_meters').addEventListener('input', initMap);

        // Get current location
        document.getElementById('getMyLocationBtn').addEventListener('click', function() {
            if (navigator.geolocation) {
                this.disabled = true;
                this.innerHTML =
                    '<span class="spinner-border spinner-border-sm me-2"></span> Mendapatkan lokasi...';

                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        document.getElementById('latitude').value = position.coords.latitude;
                        document.getElementById('longitude').value = position.coords.longitude;
                        initMap();
                        this.disabled = false;
                        this.innerHTML = '<i class="bx bx-current-location"></i> Gunakan Lokasi Saat Ini';

                        Swal.fire({
                            icon: 'success',
                            title: 'Lokasi Berhasil Didapatkan',
                            text: 'Koordinat telah diisi dengan lokasi Anda saat ini',
                            timer: 2000
                        });
                    },
                    (error) => {
                        this.disabled = false;
                        this.innerHTML = '<i class="bx bx-current-location"></i> Gunakan Lokasi Saat Ini';

                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: 'Tidak dapat mengakses lokasi: ' + error.message
                        });
                    }
                );
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Tidak Didukung',
                    text: 'Browser Anda tidak mendukung geolocation'
                });
            }
        });

        // Submit form
        document.getElementById('officeSettingForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());
            data.enforce_location = document.getElementById('enforce_location').checked ? 1 : 0;

            try {
                const response = await fetch('/api/settings/office', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('[name="_token"]').value,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: result.message
                    });

                    // Update badge
                    const badge = document.querySelector('.card-header .badge');
                    if (data.enforce_location) {
                        badge.className = 'badge bg-success';
                        badge.textContent = 'Aktif';
                    } else {
                        badge.className = 'badge bg-secondary';
                        badge.textContent = 'Nonaktif';
                    }
                } else {
                    throw new Error(result.message);
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: error.message || 'Terjadi kesalahan saat menyimpan pengaturan'
                });
            }
        });

        // Initialize map on page load
        document.addEventListener('DOMContentLoaded', function() {
            initMap();
        });
    </script>
@endsection
