@extends('layouts.app')

@section('title', 'Daftar Karyawan')

@section('title', 'Daftar Karyawan')

@section('styles')
    <style>
        /* Modal Scroll Optimization */
        #karyawanModal .modal-body {
            -webkit-overflow-scrolling: touch;
            scroll-behavior: smooth;
        }

        /* Mobile Modal Adjustments */
        @media (max-width: 767px) {
            #karyawanModal .modal-dialog {
                margin: 0.5rem;
                max-width: calc(100% - 1rem);
            }

            #karyawanModal .modal-body {
                max-height: 65vh !important;
                padding: 1rem 0.75rem;
            }

            #karyawanModal .tab-content {
                margin-top: 0.75rem !important;
            }
        }

        /* Desktop Modal */
        @media (min-width: 768px) {
            #karyawanModal .modal-body {
                max-height: 75vh !important;
            }
        }
    </style>
@endsection

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1">Daftar Karyawan</h4>
                <p class="text-muted mb-0 d-none d-md-block">Kelola data karyawan perusahaan</p>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#importModal">
                    <i class='bx bx-download me-1'></i> <span class="d-none d-sm-inline">Import</span>
                </button>
                <a href="#" class="btn btn-success btn-sm" id="exportBtn" onclick="exportKaryawan(); return false;">
                    <i class='bx bx-upload me-1'></i> <span class="d-none d-sm-inline">Export Excel</span>
                </a>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#karyawanModal"
                    onclick="openCreateModal()">
                    <i class='bx bx-plus me-1'></i> <span class="d-none d-sm-inline">Tambah</span>
                </button>
            </div>
        </div>

        <!-- Alert Messages -->
        <div id="alertContainer"></div>

        <!-- Filter Card -->
        <div class="card mb-3">
            <div class="card-body">
                <!-- Search Bar -->
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="input-group">
                            <span class="input-group-text"><i class='bx bx-search'></i></span>
                            <input type="text" class="form-control" id="searchInput"
                                placeholder="Cari berdasarkan nama, kode karyawan, atau NIK...">
                            <button class="btn btn-outline-secondary" type="button" onclick="clearSearch()">
                                <i class='bx bx-x'></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Advanced Filters -->
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label for="filterDepartment" class="form-label small mb-1">Departemen</label>
                        <select class="form-select form-select-sm" id="filterDepartment">
                            <option value="">Semua Departemen</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="filterPosition" class="form-label small mb-1">Posisi</label>
                        <select class="form-select form-select-sm" id="filterPosition">
                            <option value="">Semua Posisi</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="filterStatus" class="form-label small mb-1">Status</label>
                        <select class="form-select form-select-sm" id="filterStatus">
                            <option value="">Semua Status</option>
                            <option value="active">Aktif</option>
                            <option value="inactive">Tidak Aktif</option>
                            <option value="resign">Resign</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-primary flex-grow-1" onclick="applyFilter()">
                                <i class='bx bx-filter-alt me-1'></i> Filter
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="resetFilter()"
                                title="Reset Filter">
                                <i class='bx bx-reset'></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div id="activeFilters" class="mt-3 d-none">
                    <small class="text-muted">Filter aktif:</small>
                    <div id="filterBadges" class="d-inline-flex gap-2 ms-2"></div>
                </div>
            </div>
        </div>

        <!-- Karyawan List Card -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Data Karyawan</h5>
                <div class="d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center gap-2">
                        <small class="text-muted d-none d-md-inline">Tampilkan:</small>
                        <select class="form-select form-select-sm" id="perPageSelect" style="width: auto;"
                            onchange="changePerPage()">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                    <small class="text-muted" id="totalKaryawan">Total: 0</small>
                </div>
            </div>
            <div class="card-body p-0">
                <!-- Desktop View: Table -->
                <div class="d-none d-md-block">
                    <div class="table-responsive text-nowrap" style="overflow: visible;">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 50px;">#</th>
                                    <th style="width: 12%;">Kode</th>
                                    <th style="width: 18%;">Nama</th>
                                    <th style="width: 13%;">Departemen</th>
                                    <th style="width: 13%;">Posisi</th>
                                    <th style="width: 13%;">Shift</th>
                                    <th style="width: 10%;" class="text-center">Status</th>
                                    <th style="width: 60px;" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="table-border-bottom-0" id="karyawanTableBody">
                                <tr id="loadingRow">
                                    <td colspan="7" class="text-center py-4">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Mobile View: Card List -->
                <div class="d-md-none" id="karyawanCardList">
                    <div class="text-center py-4" id="loadingRowMobile">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div class="card-footer" id="paginationContainer" style="display: none;">
                <div
                    class="d-flex justify-content-center justify-content-md-between align-items-center flex-column flex-md-row gap-2">
                    <div class="text-muted small d-none d-md-block" id="paginationInfo"></div>
                    <div id="paginationLinks"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Form (simplified) -->
    <div class="modal fade" id="karyawanModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <form id="karyawanForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitle">Tambah Karyawan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                        <input type="hidden" id="karyawanId">

                        <!-- Tab Navigation - Mobile Optimized -->
                        <ul class="nav nav-tabs nav-fill flex-wrap" role="tablist">
                            <li class="nav-item">
                                <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab"
                                    data-bs-target="#tabPersonal">
                                    <i class='bx bx-user d-md-inline'></i> <span
                                        class="d-none d-sm-inline">Pribadi</span><span class="d-sm-none">1</span>
                                </button>
                            </li>
                            <li class="nav-item">
                                <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                                    data-bs-target="#tabPekerjaan">
                                    <i class='bx bx-briefcase d-md-inline'></i> <span
                                        class="d-none d-sm-inline">Pekerjaan</span><span class="d-sm-none">2</span>
                                </button>
                            </li>
                            <li class="nav-item">
                                <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                                    data-bs-target="#tabKontak">
                                    <i class='bx bx-phone d-md-inline'></i> <span
                                        class="d-none d-sm-inline">Kontak</span><span class="d-sm-none">3</span>
                                </button>
                            </li>
                            <li class="nav-item">
                                <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                                    data-bs-target="#tabAdministrasi">
                                    <i class='bx bx-file d-md-inline'></i> <span
                                        class="d-none d-sm-inline">Administrasi</span><span class="d-sm-none">4</span>
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content mt-3">
                            <!-- Tab Personal -->
                            <div class="tab-pane fade show active" id="tabPersonal" role="tabpanel">
                                <div class="row g-2 g-md-3">
                                    <div class="col-sm-6 mb-2">
                                        <label for="employee_code" class="form-label small">Kode Karyawan <span
                                                class="text-danger">*</span></label>
                                        <input type="text" class="form-control form-control-sm" id="employee_code"
                                            name="employee_code">
                                        <div class="invalid-feedback" id="employee_codeError"></div>
                                    </div>
                                    <div class="col-sm-6 mb-2">
                                        <label for="nik" class="form-label small">NIK / KTP</label>
                                        <input type="text" class="form-control form-control-sm" id="nik"
                                            name="nik" maxlength="20">
                                        <div class="invalid-feedback" id="nikError"></div>
                                    </div>
                                </div>

                                <div class="mb-2">
                                    <label for="name" class="form-label small">Nama Lengkap <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control form-control-sm" id="name"
                                        name="name">
                                    <div class="invalid-feedback" id="nameError"></div>
                                </div>

                                <div class="row g-2 g-md-3">
                                    <div class="col-sm-4 mb-2">
                                        <label for="gender" class="form-label small">Jenis Kelamin <span
                                                class="text-danger">*</span></label>
                                        <select class="form-select form-select-sm" id="gender" name="gender">
                                            <option value="">Pilih...</option>
                                            <option value="L">Laki-laki</option>
                                            <option value="P">Perempuan</option>
                                        </select>
                                        <div class="invalid-feedback" id="genderError"></div>
                                    </div>
                                    <div class="col-sm-4 mb-2">
                                        <label for="birth_place" class="form-label small">Tempat Lahir <span
                                                class="text-danger">*</span></label>
                                        <input type="text" class="form-control form-control-sm" id="birth_place"
                                            name="birth_place">
                                        <div class="invalid-feedback" id="birth_placeError"></div>
                                    </div>
                                    <div class="col-sm-4 mb-2">
                                        <label for="birth_date" class="form-label small">Tanggal Lahir <span
                                                class="text-danger">*</span></label>
                                        <input type="date" class="form-control form-control-sm" id="birth_date"
                                            name="birth_date">
                                        <div class="invalid-feedback" id="birth_dateError"></div>
                                    </div>
                                </div>

                                <div class="row g-2 g-md-3">
                                    <div class="col-sm-6 mb-2">
                                        <label for="marital_status" class="form-label small">Status Perkawinan <span
                                                class="text-danger">*</span></label>
                                        <select class="form-select form-select-sm" id="marital_status"
                                            name="marital_status">
                                            <option value="">Pilih...</option>
                                            <option value="Belum Menikah">Belum Menikah</option>
                                            <option value="Menikah">Menikah</option>
                                            <option value="Duda">Duda</option>
                                            <option value="Janda">Janda</option>
                                        </select>
                                        <div class="invalid-feedback" id="marital_statusError"></div>
                                    </div>
                                    <div class="col-sm-6 mb-2">
                                        <label for="tanggungan_anak" class="form-label small">Tanggungan Anak</label>
                                        <input type="number" class="form-control form-control-sm" id="tanggungan_anak"
                                            name="tanggungan_anak" min="0" value="0">
                                        <div class="invalid-feedback" id="tanggungan_anakError"></div>
                                    </div>
                                </div>

                                <div class="row g-2 g-md-3">
                                    <div class="col-sm-6 mb-2">
                                        <label for="agama" class="form-label small">Agama</label>
                                        <select class="form-select form-select-sm" id="agama" name="agama">
                                            <option value="">Pilih...</option>
                                            <option value="Islam">Islam</option>
                                            <option value="Kristen">Kristen</option>
                                            <option value="Katolik">Katolik</option>
                                            <option value="Hindu">Hindu</option>
                                            <option value="Buddha">Buddha</option>
                                            <option value="Konghucu">Konghucu</option>
                                        </select>
                                        <div class="invalid-feedback" id="agamaError"></div>
                                    </div>
                                    <div class="col-sm-6 mb-2">
                                        <label for="bangsa" class="form-label small">Kebangsaan</label>
                                        <input type="text" class="form-control form-control-sm" id="bangsa"
                                            name="bangsa" placeholder="Contoh: Indonesia">
                                        <div class="invalid-feedback" id="bangsaError"></div>
                                    </div>
                                </div>

                                <div class="row g-2 g-md-3">
                                    <div class="col-sm-6 mb-2">
                                        <label for="status_kependudukan" class="form-label small">Status
                                            Kependudukan</label>
                                        <select class="form-select form-select-sm" id="status_kependudukan"
                                            name="status_kependudukan">
                                            <option value="">Pilih...</option>
                                            <option value="WNI">WNI</option>
                                            <option value="WNA">WNA</option>
                                        </select>
                                        <div class="invalid-feedback" id="status_kependudukanError"></div>
                                    </div>
                                    <div class="col-sm-6 mb-2">
                                        <label for="nama_ibu_kandung" class="form-label small">Nama Ibu Kandung</label>
                                        <input type="text" class="form-control form-control-sm" id="nama_ibu_kandung"
                                            name="nama_ibu_kandung">
                                        <div class="invalid-feedback" id="nama_ibu_kandungError"></div>
                                    </div>
                                </div>

                                <div class="mb-2">
                                    <label for="kartu_keluarga" class="form-label small">Nomor Kartu Keluarga</label>
                                    <input type="text" class="form-control form-control-sm" id="kartu_keluarga"
                                        name="kartu_keluarga" maxlength="20">
                                    <div class="invalid-feedback" id="kartu_keluargaError"></div>
                                </div>
                            </div>

                            <!-- Tab Pekerjaan -->
                            <div class="tab-pane fade" id="tabPekerjaan" role="tabpanel">
                                <div class="row g-2 g-md-3">
                                    <div class="col-sm-6 mb-2">
                                        <label for="department_id" class="form-label small">Departemen <span
                                                class="text-danger">*</span></label>
                                        <select class="form-select form-select-sm" id="department_id"
                                            name="department_id">
                                            <option value="">Pilih Departemen...</option>
                                        </select>
                                        <div class="invalid-feedback" id="department_idError"></div>
                                    </div>
                                    <div class="col-sm-6 mb-2">
                                        <label for="sub_department_id" class="form-label small">Sub Departemen /
                                            Bagian</label>
                                        <select class="form-select form-select-sm" id="sub_department_id"
                                            name="sub_department_id">
                                            <option value="">Pilih Sub Departemen...</option>
                                        </select>
                                        <div class="invalid-feedback" id="sub_department_idError"></div>
                                    </div>
                                </div>

                                <div class="row g-2 g-md-3">
                                    <div class="col-sm-6 mb-2">
                                        <label for="position_id" class="form-label small">Posisi <span
                                                class="text-danger">*</span></label>
                                        <select class="form-select form-select-sm" id="position_id" name="position_id">
                                            <option value="">Pilih Posisi...</option>
                                        </select>
                                        <div class="invalid-feedback" id="position_idError"></div>
                                    </div>
                                    <div class="col-sm-6 mb-2">
                                        <label for="lulusan_sekolah" class="form-label small">Pendidikan Terakhir</label>
                                        <select class="form-select form-select-sm" id="lulusan_sekolah"
                                            name="lulusan_sekolah">
                                            <option value="">Pilih Pendidikan...</option>
                                            <option value="SD">SD</option>
                                            <option value="SMP">SMP</option>
                                            <option value="SMA">SMA</option>
                                            <option value="D1">D1</option>
                                            <option value="D3">D3</option>
                                            <option value="D4/S1">D4/S1</option>
                                            <option value="S2">S2</option>
                                        </select>
                                        <div class="invalid-feedback" id="lulusan_sekolahError"></div>
                                    </div>
                                </div>

                                <div class="row g-2 g-md-3">
                                    <div class="col-sm-4 mb-2">
                                        <label for="join_date" class="form-label small">Tanggal Bergabung <span
                                                class="text-danger">*</span></label>
                                        <input type="date" class="form-control form-control-sm" id="join_date"
                                            name="join_date">
                                        <div class="invalid-feedback" id="join_dateError"></div>
                                    </div>
                                    <div class="col-sm-4 mb-2">
                                        <label for="employment_status" class="form-label small">Status Kerja <span
                                                class="text-danger">*</span></label>
                                        <select class="form-select form-select-sm" id="employment_status"
                                            name="employment_status">
                                            <option value="">Pilih...</option>
                                            <option value="Tetap">Tetap</option>
                                            <option value="Kontrak">Kontrak</option>
                                            <option value="Probation">Probation</option>
                                        </select>
                                        <div class="invalid-feedback" id="employment_statusError"></div>
                                    </div>
                                    <div class="col-sm-4 mb-2">
                                        <label for="work_schedule_id" class="form-label small">Jadwal Kerja <span
                                                class="text-danger">*</span></label>
                                        <select class="form-select form-select-sm" id="work_schedule_id"
                                            name="work_schedule_id">
                                            <option value="">Pilih Jadwal...</option>
                                        </select>
                                        <div class="invalid-feedback" id="work_schedule_idError"></div>
                                    </div>
                                </div>

                                <div class="row g-2 g-md-3">
                                    <div class="col-sm-6 mb-2">
                                        <label for="status" class="form-label small">Status Karyawan <span
                                                class="text-danger">*</span></label>
                                        <select class="form-select form-select-sm" id="status" name="status"
                                            onchange="toggleResignDate()">
                                            <option value="">Pilih...</option>
                                            <option value="active">Aktif</option>
                                            <option value="inactive">Tidak Aktif</option>
                                            <option value="resign">Resign</option>
                                        </select>
                                        <div class="invalid-feedback" id="statusError"></div>
                                    </div>
                                    <div class="col-sm-6 mb-2" id="resignDateContainer" style="display: none;">
                                        <label for="tanggal_resign" class="form-label small">Tanggal Resign</label>
                                        <input type="date" class="form-control form-control-sm" id="tanggal_resign"
                                            name="tanggal_resign">
                                        <div class="invalid-feedback" id="tanggal_resignError"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tab Kontak -->
                            <div class="tab-pane fade" id="tabKontak" role="tabpanel">
                                <div class="mb-2">
                                    <label for="address" class="form-label small">Alamat Lengkap <span
                                            class="text-danger">*</span></label>
                                    <textarea class="form-control form-control-sm" id="address" name="address" rows="2"></textarea>
                                    <div class="invalid-feedback" id="addressError"></div>
                                </div>

                                <div class="row g-2 g-md-3">
                                    <div class="col-sm-4 mb-2">
                                        <label for="city" class="form-label small">Kota <span
                                                class="text-danger">*</span></label>
                                        <input type="text" class="form-control form-control-sm" id="city"
                                            name="city">
                                        <div class="invalid-feedback" id="cityError"></div>
                                    </div>
                                    <div class="col-sm-4 mb-2">
                                        <label for="province" class="form-label small">Provinsi <span
                                                class="text-danger">*</span></label>
                                        <input type="text" class="form-control form-control-sm" id="province"
                                            name="province">
                                        <div class="invalid-feedback" id="provinceError"></div>
                                    </div>
                                    <div class="col-sm-4 mb-2">
                                        <label for="postal_code" class="form-label small">Kode Pos <span
                                                class="text-danger">*</span></label>
                                        <input type="text" class="form-control form-control-sm" id="postal_code"
                                            name="postal_code">
                                        <div class="invalid-feedback" id="postal_codeError"></div>
                                    </div>
                                </div>

                                <div class="row g-2 g-md-3">
                                    <div class="col-sm-6 mb-2">
                                        <label for="phone" class="form-label small">Nomor HP <span
                                                class="text-danger">*</span></label>
                                        <input type="text" class="form-control form-control-sm" id="phone"
                                            name="phone">
                                        <div class="invalid-feedback" id="phoneError"></div>
                                    </div>
                                    <div class="col-sm-6 mb-2">
                                        <label for="email" class="form-label small">Email <span
                                                class="text-danger">*</span></label>
                                        <input type="email" class="form-control form-control-sm" id="email"
                                            name="email">
                                        <div class="invalid-feedback" id="emailError"></div>
                                    </div>
                                </div>

                                <hr class="my-2">
                                <h6 class="mb-2 small fw-bold">Kontak Darurat</h6>
                                <div class="row g-2 g-md-3">
                                    <div class="col-sm-6 mb-2">
                                        <label for="emergency_contact_name" class="form-label small">Nama <span
                                                class="text-danger">*</span></label>
                                        <input type="text" class="form-control form-control-sm"
                                            id="emergency_contact_name" name="emergency_contact_name">
                                        <div class="invalid-feedback" id="emergency_contact_nameError"></div>
                                    </div>
                                    <div class="col-sm-6 mb-2">
                                        <label for="emergency_contact_phone" class="form-label small">Nomor <span
                                                class="text-danger">*</span></label>
                                        <input type="text" class="form-control form-control-sm"
                                            id="emergency_contact_phone" name="emergency_contact_phone">
                                        <div class="invalid-feedback" id="emergency_contact_phoneError"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tab Administrasi -->
                            <div class="tab-pane fade" id="tabAdministrasi" role="tabpanel">
                                <h6 class="text-primary mb-2 small fw-bold">Data Keuangan</h6>
                                <div class="row g-2 g-md-3">
                                    <div class="col-sm-6 mb-2">
                                        <label for="bank" class="form-label small">Nama Bank</label>
                                        <input type="text" class="form-control form-control-sm" id="bank"
                                            name="bank" placeholder="BCA">
                                        <div class="invalid-feedback" id="bankError"></div>
                                    </div>
                                    <div class="col-sm-6 mb-2">
                                        <label for="nomor_rekening" class="form-label small">Nomor Rekening</label>
                                        <input type="text" class="form-control form-control-sm" id="nomor_rekening"
                                            name="nomor_rekening">
                                        <div class="invalid-feedback" id="nomor_rekeningError"></div>
                                    </div>
                                </div>

                                <hr class="my-2">
                                <h6 class="text-primary mb-2 small fw-bold">Data Administrasi & Pajak</h6>
                                <div class="mb-2">
                                    <label for="tax_npwp" class="form-label small">Nomor NPWP</label>
                                    <input type="text" class="form-control form-control-sm" id="tax_npwp"
                                        name="tax_npwp" placeholder="12.345.678.9-012.345">
                                    <div class="invalid-feedback" id="tax_npwpError"></div>
                                </div>

                                <div class="row g-2 g-md-3">
                                    <div class="col-sm-6 mb-2">
                                        <label for="bpjs_kesehatan" class="form-label small">No. BPJS Kesehatan</label>
                                        <input type="text" class="form-control form-control-sm" id="bpjs_kesehatan"
                                            name="bpjs_kesehatan">
                                        <div class="invalid-feedback" id="bpjs_kesehatanError"></div>
                                    </div>
                                    <div class="col-sm-6 mb-2">
                                        <label for="bpjs_ketenagakerjaan" class="form-label small">No. BPJS
                                            Ketenagakerjaan</label>
                                        <input type="text" class="form-control form-control-sm"
                                            id="bpjs_ketenagakerjaan" name="bpjs_ketenagakerjaan">
                                        <div class="invalid-feedback" id="bpjs_ketenagakerjaanError"></div>
                                    </div>
                                </div>

                                <div class="alert alert-info p-2 mt-2">
                                    <small><i class='bx bx-info-circle'></i> Data ini opsional untuk administrasi
                                        perusahaan.</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <span class="spinner-border spinner-border-sm me-1 d-none" id="submitSpinner"></span>
                            Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Detail Modal -->
    <div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Karyawan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <!-- Column 1 -->
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">Identitas Pribadi</h6>
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <th width="45%">Kode Karyawan</th>
                                    <td id="detailEmployeeCode">-</td>
                                </tr>
                                <tr>
                                    <th>NIK / KTP</th>
                                    <td id="detailNik">-</td>
                                </tr>
                                <tr>
                                    <th>Nama Lengkap</th>
                                    <td id="detailName">-</td>
                                </tr>
                                <tr>
                                    <th>Jenis Kelamin</th>
                                    <td id="detailGender">-</td>
                                </tr>
                                <tr>
                                    <th>Tempat, Tanggal Lahir</th>
                                    <td id="detailBirth">-</td>
                                </tr>
                                <tr>
                                    <th>Agama</th>
                                    <td id="detailAgama">-</td>
                                </tr>
                                <tr>
                                    <th>Kebangsaan</th>
                                    <td id="detailBangsa">-</td>
                                </tr>
                                <tr>
                                    <th>Status Kependudukan</th>
                                    <td id="detailStatusKependudukan">-</td>
                                </tr>
                                <tr>
                                    <th>Status Perkawinan</th>
                                    <td id="detailMaritalStatus">-</td>
                                </tr>
                                <tr>
                                    <th>Tanggungan Anak</th>
                                    <td id="detailTanggunganAnak">-</td>
                                </tr>
                                <tr>
                                    <th>Nama Ibu Kandung</th>
                                    <td id="detailNamaIbuKandung">-</td>
                                </tr>
                                <tr>
                                    <th>Nomor Kartu Keluarga</th>
                                    <td id="detailKartuKeluarga">-</td>
                                </tr>
                            </table>

                            <h6 class="text-primary mb-3 mt-4">Data Pekerjaan</h6>
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <th width="45%">Departemen</th>
                                    <td id="detailDepartment">-</td>
                                </tr>
                                <tr>
                                    <th>Sub Departemen</th>
                                    <td id="detailSubDepartment">-</td>
                                </tr>
                                <tr>
                                    <th>Posisi</th>
                                    <td id="detailPosition">-</td>
                                </tr>
                                <tr>
                                    <th>Pendidikan Terakhir</th>
                                    <td id="detailLulusanSekolah">-</td>
                                </tr>
                                <tr>
                                    <th>Tanggal Bergabung</th>
                                    <td id="detailJoinDate">-</td>
                                </tr>
                                <tr>
                                    <th>Status Kerja</th>
                                    <td id="detailEmploymentStatus">-</td>
                                </tr>
                                <tr>
                                    <th>Jadwal Kerja</th>
                                    <td id="detailShiftType">-</td>
                                </tr>
                                <tr>
                                    <th>Status Karyawan</th>
                                    <td id="detailStatus">-</td>
                                </tr>
                                <tr id="detailResignDateRow" style="display: none;">
                                    <th>Tanggal Resign</th>
                                    <td id="detailTanggalResign">-</td>
                                </tr>
                            </table>
                        </div>

                        <!-- Column 2 -->
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">Kontak & Alamat</h6>
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <th width="45%">Alamat Lengkap</th>
                                    <td id="detailAddress">-</td>
                                </tr>
                                <tr>
                                    <th>Kota</th>
                                    <td id="detailCity">-</td>
                                </tr>
                                <tr>
                                    <th>Provinsi</th>
                                    <td id="detailProvince">-</td>
                                </tr>
                                <tr>
                                    <th>Kode Pos</th>
                                    <td id="detailPostalCode">-</td>
                                </tr>
                                <tr>
                                    <th>Nomor HP</th>
                                    <td id="detailPhone">-</td>
                                </tr>
                                <tr>
                                    <th>Email</th>
                                    <td id="detailEmail">-</td>
                                </tr>
                            </table>

                            <h6 class="text-primary mb-3 mt-4">Kontak Darurat</h6>
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <th width="45%">Nama</th>
                                    <td id="detailEmergencyName">-</td>
                                </tr>
                                <tr>
                                    <th>Nomor Telepon</th>
                                    <td id="detailEmergencyPhone">-</td>
                                </tr>
                            </table>

                            <h6 class="text-primary mb-3 mt-4">Data Keuangan</h6>
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <th width="45%">Nama Bank</th>
                                    <td id="detailBank">-</td>
                                </tr>
                                <tr>
                                    <th>Nomor Rekening</th>
                                    <td id="detailNomorRekening">-</td>
                                </tr>
                            </table>

                            <h6 class="text-primary mb-3 mt-4">Data Administrasi & Pajak</h6>
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <th width="45%">Nomor NPWP</th>
                                    <td id="detailTaxNpwp">-</td>
                                </tr>
                                <tr>
                                    <th>BPJS Kesehatan</th>
                                    <td id="detailBpjsKesehatan">-</td>
                                </tr>
                                <tr>
                                    <th>BPJS Ketenagakerjaan</th>
                                    <td id="detailBpjsKetenagakerjaan">-</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Import Modal -->
    <div class="modal fade" id="importModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="importForm" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title">Import Data Karyawan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class='bx bx-info-circle me-2'></i>
                            <strong>Petunjuk:</strong>
                            <ol class="mb-0 mt-2 ps-3">
                                <li>Download template Excel terlebih dahulu</li>
                                <li>Isi data karyawan sesuai format template</li>
                                <li>Upload file Excel yang sudah diisi</li>
                                <li>Pastikan Departemen dan Posisi sudah ada di database</li>
                            </ol>
                        </div>

                        <div class="mb-3">
                            <a href="{{ route('admin.karyawan.template') }}" class="btn btn-sm btn-outline-primary w-100">
                                <i class='bx bx-download me-1'></i> Download Template Excel
                            </a>
                        </div>

                        <div class="mb-3">
                            <label for="import_file" class="form-label">Pilih File Excel <span
                                    class="text-danger">*</span></label>
                            <input type="file" class="form-control" id="import_file" name="file"
                                accept=".xlsx,.xls" required>
                            <small class="text-muted">Format: .xlsx atau .xls (Max 2MB)</small>
                            <div class="invalid-feedback" id="fileError"></div>
                        </div>

                        <div id="importProgress" class="d-none">
                            <div class="progress">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar"
                                    style="width: 100%"></div>
                            </div>
                            <p class="text-center mt-2 mb-0">Sedang mengimport data...</p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" id="importBtn">
                            <i class='bx bx-upload me-1'></i> Import Data
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        let currentPage = 1;
        let karyawanModal, detailModal, importModal;
        let masterData = {};
        let currentFilters = {
            department_id: '',
            position_id: '',
            status: '',
            search: ''
        };
        let perPage = 10;
        let searchTimeout = null;

        $(document).ready(function() {
            karyawanModal = new bootstrap.Modal(document.getElementById('karyawanModal'));
            detailModal = new bootstrap.Modal(document.getElementById('detailModal'));
            importModal = new bootstrap.Modal(document.getElementById('importModal'));
            loadMasterData();
            loadKaryawans();

            // Auto-scroll to top when switching tabs (mobile friendly)
            $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
                const modalBody = $('#karyawanModal .modal-body');
                modalBody.animate({
                    scrollTop: 0
                }, 300);
            });

            // Search input with debounce
            $('#searchInput').on('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function() {
                    currentFilters.search = $('#searchInput').val();
                    updateFilterBadges();
                    loadKaryawans(1);
                }, 500); // 500ms debounce
            });

            $('#karyawanForm').on('submit', function(e) {
                e.preventDefault();
                saveKaryawan();
            });
            $('#importForm').on('submit', function(e) {
                e.preventDefault();
                importKaryawan();
            });
        });

        function loadMasterData() {
            $.ajax({
                url: '/api/karyawan/master-data',
                method: 'GET',
                success: function(response) {
                    masterData = response.data;
                    populateDepartments();
                    populatePositions();
                    populateWorkSchedules();
                    populateFilterDepartments();
                    populateFilterPositions();
                }
            });
        }

        function populateDepartments() {
            const select = $('#department_id');
            select.find('option:not(:first)').remove();
            masterData.departments.forEach(dept => {
                select.append(`<option value="${dept.id}">${dept.name}</option>`);
            });
        }

        function populatePositions() {
            const select = $('#position_id');
            select.find('option:not(:first)').remove();
            masterData.positions.forEach(pos => {
                select.append(`<option value="${pos.id}">${pos.name}</option>`);
            });
        }

        function populateWorkSchedules() {
            const select = $('#work_schedule_id');
            select.find('option:not(:first)').remove();
            masterData.work_schedules.forEach(schedule => {
                select.append(`<option value="${schedule.id}">${schedule.name}</option>`);
            });
        }

        function populateFilterDepartments() {
            const select = $('#filterDepartment');
            select.find('option:not(:first)').remove();
            masterData.departments.forEach(dept => {
                select.append(`<option value="${dept.id}">${dept.name}</option>`);
            });
        }

        function populateFilterPositions() {
            const select = $('#filterPosition');
            select.find('option:not(:first)').remove();
            masterData.positions.forEach(pos => {
                select.append(`<option value="${pos.id}">${pos.name}</option>`);
            });
        }

        function populateSubDepartments(departmentId = null, selectedSubDeptId = null) {
            const select = $('#sub_department_id');
            select.find('option:not(:first)').remove();

            if (departmentId && masterData.sub_departments) {
                const filtered = masterData.sub_departments.filter(sd =>
                    sd.department_id == departmentId && sd.is_active
                );

                filtered.forEach(subDept => {
                    const option = `<option value="${subDept.id}">${subDept.name}</option>`;
                    select.append(option);
                });

                if (selectedSubDeptId) {
                    select.val(selectedSubDeptId);
                }
            }
        }

        // Cascade: When department changes, load filtered sub departments
        $(document).on('change', '#department_id', function() {
            const departmentId = $(this).val();
            $('#sub_department_id').val('').find('option:not(:first)').remove();

            if (departmentId) {
                populateSubDepartments(departmentId);
            }
        });

        function applyFilter() {
            currentFilters.department_id = $('#filterDepartment').val();
            currentFilters.position_id = $('#filterPosition').val();
            currentFilters.status = $('#filterStatus').val();
            currentFilters.search = $('#searchInput').val();
            updateFilterBadges();
            loadKaryawans(1);
        }

        function resetFilter() {
            currentFilters = {
                department_id: '',
                position_id: '',
                status: '',
                search: ''
            };
            $('#filterDepartment').val('');
            $('#filterPosition').val('');
            $('#filterStatus').val('');
            $('#searchInput').val('');
            updateFilterBadges();
            loadKaryawans(1);
        }

        function clearSearch() {
            $('#searchInput').val('');
            currentFilters.search = '';
            updateFilterBadges();
            loadKaryawans(1);
        }

        function changePerPage() {
            perPage = parseInt($('#perPageSelect').val());
            loadKaryawans(1);
        }

        function updateFilterBadges() {
            const container = $('#filterBadges');
            container.empty();

            let hasFilter = false;

            if (currentFilters.search) {
                container.append(`<span class="badge bg-label-secondary">Cari: "${currentFilters.search}"</span>`);
                hasFilter = true;
            }

            if (currentFilters.department_id) {
                const deptName = $('#filterDepartment option:selected').text();
                container.append(`<span class="badge bg-label-primary">Dept: ${deptName}</span>`);
                hasFilter = true;
            }

            if (currentFilters.position_id) {
                const posName = $('#filterPosition option:selected').text();
                container.append(`<span class="badge bg-label-info">Posisi: ${posName}</span>`);
                hasFilter = true;
            }

            if (currentFilters.status) {
                const statusLabel = {
                    'active': 'Aktif',
                    'inactive': 'Tidak Aktif',
                    'resign': 'Resign'
                };
                container.append(
                    `<span class="badge bg-label-warning">Status: ${statusLabel[currentFilters.status]}</span>`);
                hasFilter = true;
            }

            if (hasFilter) {
                $('#activeFilters').removeClass('d-none');
            } else {
                $('#activeFilters').addClass('d-none');
            }
        }

        function loadKaryawans(page = 1) {
            let url = '/api/karyawan?page=' + page + '&per_page=' + perPage;

            if (currentFilters.search) {
                url += '&search=' + encodeURIComponent(currentFilters.search);
            }
            if (currentFilters.department_id) {
                url += '&department_id=' + currentFilters.department_id;
            }
            if (currentFilters.position_id) {
                url += '&position_id=' + currentFilters.position_id;
            }
            if (currentFilters.status) {
                url += '&status=' + currentFilters.status;
            }

            $.ajax({
                url: url,
                method: 'GET',
                beforeSend: function() {
                    $('#loadingRow').show();
                    $('#loadingRowMobile').show();
                },
                success: function(response) {
                    renderKaryawans(response.data);
                    currentPage = page;
                },
                error: function() {
                    showAlert('Gagal memuat data karyawan', 'danger');
                }
            });
        }

        function exportKaryawan() {
            let url = '{{ route('admin.karyawan.export') }}?1=1';

            if (currentFilters.search) {
                url += '&search=' + encodeURIComponent(currentFilters.search);
            }
            if (currentFilters.department_id) {
                url += '&department_id=' + currentFilters.department_id;
            }
            if (currentFilters.position_id) {
                url += '&position_id=' + currentFilters.position_id;
            }
            if (currentFilters.status) {
                url += '&status=' + currentFilters.status;
            }

            window.location.href = url;
        }

        function renderKaryawans(data) {
            const tbody = $('#karyawanTableBody');
            const cardList = $('#karyawanCardList');
            tbody.empty();
            cardList.empty();

            if (data.data.length === 0) {
                tbody.append(`
                    <tr><td colspan="8" class="text-center py-4">
                        <div class="mb-3"><i class='bx bx-user' style="font-size: 48px; color: #ddd;"></i></div>
                        <p class="text-muted mb-2">Belum ada data karyawan</p>
                        <button class="btn btn-sm btn-primary" onclick="openCreateModal()" data-bs-toggle="modal" data-bs-target="#karyawanModal">
                            <i class='bx bx-plus me-1'></i> Tambah Karyawan
                        </button>
                    </td></tr>
                `);
                cardList.append(`
                    <div class="text-center py-4">
                        <div class="mb-3"><i class='bx bx-user' style="font-size: 48px; color: #ddd;"></i></div>
                        <p class="text-muted mb-2">Belum ada data karyawan</p>
                        <button class="btn btn-sm btn-primary" onclick="openCreateModal()" data-bs-toggle="modal" data-bs-target="#karyawanModal">
                            <i class='bx bx-plus me-1'></i> Tambah Karyawan
                        </button>
                    </div>
                `);
                $('#paginationContainer').hide();
            } else {
                data.data.forEach((k, index) => {
                    const rowNumber = data.from + index;
                    const statusBadge = getStatusBadge(k.status);

                    tbody.append(`
                        <tr>
                            <td style="white-space: nowrap;">${rowNumber}</td>
                            <td style="white-space: nowrap;"><strong>${k.employee_code}</strong></td>
                            <td><strong>${k.name}</strong></td>
                            <td>${k.department ? k.department.name : '-'}</td>
                            <td>${k.position ? k.position.name : '-'}</td>
                            <td>${k.work_schedule ? '<span class="badge bg-label-info">' + k.work_schedule.name + '</span>' : '-'}</td>
                            <td class="text-center" style="white-space: nowrap;">${statusBadge}</td>
                            <td class="text-center" style="white-space: nowrap; position: relative;">
                                <div class="dropdown">
                                    <button type="button" class="btn btn-sm btn-icon btn-text-secondary rounded-pill dropdown-toggle hide-arrow"
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class='bx bx-dots-vertical-rounded'></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item" href="javascript:void(0);" onclick="showDetail(${k.id})">
                                            <i class='bx bx-show me-2'></i> Detail</a></li>
                                        <li><a class="dropdown-item" href="javascript:void(0);" onclick="editKaryawan(${k.id})">
                                            <i class='bx bx-edit me-2'></i> Edit</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item text-danger" href="javascript:void(0);" onclick="deleteKaryawan(${k.id})">
                                            <i class='bx bx-trash me-2'></i> Hapus</a></li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    `);

                    cardList.append(`
                        <div class="card mb-2 mx-3 border">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1 fw-bold">${k.name}</h6>
                                        <p class="text-muted small mb-1">${k.employee_code}</p>
                                        <p class="text-muted small mb-1">${k.department ? k.department.name : '-'} - ${k.position ? k.position.name : '-'}</p>
                                        <p class="text-muted small mb-2">${k.work_schedule ? '<span class="badge bg-label-info">' + k.work_schedule.name + '</span>' : '-'}</p>
                                        ${statusBadge}
                                    </div>
                                    <div class="dropdown">
                                        <button type="button" class="btn btn-sm btn-icon btn-text-secondary rounded-pill dropdown-toggle hide-arrow"
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class='bx bx-dots-vertical-rounded'></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><a class="dropdown-item" href="javascript:void(0);" onclick="showDetail(${k.id})">
                                                <i class='bx bx-show me-2'></i> Detail</a></li>
                                            <li><a class="dropdown-item" href="javascript:void(0);" onclick="editKaryawan(${k.id})">
                                                <i class='bx bx-edit me-2'></i> Edit</a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item text-danger" href="javascript:void(0);" onclick="deleteKaryawan(${k.id})">
                                                <i class='bx bx-trash me-2'></i> Hapus</a></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `);
                });

                $('#totalKaryawan').text(`Total: ${data.total}`);
                $('#paginationInfo').text(`${data.from} - ${data.to} dari ${data.total}`);

                if (data.last_page > 1) {
                    renderPagination(data);
                    $('#paginationContainer').show();
                } else {
                    $('#paginationContainer').hide();
                }
            }
        }

        function getStatusBadge(status) {
            const badges = {
                'active': '<span class="badge bg-label-success">Aktif</span>',
                'inactive': '<span class="badge bg-label-warning">Tidak Aktif</span>',
                'resign': '<span class="badge bg-label-danger">Resign</span>'
            };
            return badges[status] || status;
        }

        function renderPagination(data) {
            const container = $('#paginationLinks');
            container.empty();
            let html = '<nav><ul class="pagination pagination-sm mb-0">';
            html += `<li class="page-item ${data.current_page === 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" onclick="loadKaryawans(${data.current_page - 1}); return false;">
                    <i class='bx bx-chevron-left'></i></a></li>`;

            const maxVisiblePages = window.innerWidth < 768 ? 3 : 5;
            const halfVisible = Math.floor(maxVisiblePages / 2);
            let startPage = Math.max(1, data.current_page - halfVisible);
            let endPage = Math.min(data.last_page, startPage + maxVisiblePages - 1);

            if (endPage - startPage < maxVisiblePages - 1) {
                startPage = Math.max(1, endPage - maxVisiblePages + 1);
            }

            if (startPage > 1) {
                html +=
                    `<li class="page-item"><a class="page-link" href="#" onclick="loadKaryawans(1); return false;">1</a></li>`;
                if (startPage > 2) html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }

            for (let i = startPage; i <= endPage; i++) {
                html += `<li class="page-item ${i === data.current_page ? 'active' : ''}">
                    <a class="page-link" href="#" onclick="loadKaryawans(${i}); return false;">${i}</a></li>`;
            }

            if (endPage < data.last_page) {
                if (endPage < data.last_page - 1) html +=
                    '<li class="page-item disabled"><span class="page-link">...</span></li>';
                html +=
                    `<li class="page-item"><a class="page-link" href="#" onclick="loadKaryawans(${data.last_page}); return false;">${data.last_page}</a></li>`;
            }

            html += `<li class="page-item ${data.current_page === data.last_page ? 'disabled' : ''}">
                <a class="page-link" href="#" onclick="loadKaryawans(${data.current_page + 1}); return false;">
                    <i class='bx bx-chevron-right'></i></a></li>`;
            html += '</ul></nav>';
            container.html(html);
        }

        function openCreateModal() {
            $('#modalTitle').text('Tambah Karyawan');
            $('#karyawanId').val('');
            $('#karyawanForm')[0].reset();
            $('.form-control, .form-select').removeClass('is-invalid');
            $('.nav-tabs button:first').tab('show');
            $('#resignDateContainer').hide();
        }

        function toggleResignDate() {
            const status = $('#status').val();
            if (status === 'resign') {
                $('#resignDateContainer').show();
                $('#tanggal_resign').prop('required', true);
            } else {
                $('#resignDateContainer').hide();
                $('#tanggal_resign').prop('required', false).val('');
            }
        }

        function editKaryawan(id) {
            $.ajax({
                url: `/api/karyawan/${id}`,
                method: 'GET',
                success: function(response) {
                    const k = response.data;
                    $('#modalTitle').text('Edit Karyawan');
                    $('#karyawanId').val(k.id);
                    $('#employee_code').val(k.employee_code);
                    $('#nik').val(k.nik);
                    $('#name').val(k.name);
                    $('#gender').val(k.gender);
                    $('#birth_place').val(k.birth_place);

                    // Set tanggal lahir langsung (sudah dalam format Y-m-d dari API)
                    $('#birth_date').val(k.birth_date || '');

                    $('#marital_status').val(k.marital_status);
                    $('#tanggungan_anak').val(k.tanggungan_anak || 0);
                    $('#agama').val(k.agama);
                    $('#bangsa').val(k.bangsa);
                    $('#status_kependudukan').val(k.status_kependudukan);
                    $('#nama_ibu_kandung').val(k.nama_ibu_kandung);
                    $('#kartu_keluarga').val(k.kartu_keluarga);

                    $('#department_id').val(k.department_id);

                    // Populate sub departments based on selected department
                    if (k.department_id) {
                        populateSubDepartments(k.department_id, k.sub_department_id);
                    }

                    $('#position_id').val(k.position_id);
                    // Set dropdown value for Pendidikan Terakhir
                    $('#lulusan_sekolah').val(k.lulusan_sekolah);

                    // Set tanggal bergabung langsung (sudah dalam format Y-m-d dari API)
                    $('#join_date').val(k.join_date || '');

                    $('#employment_status').val(k.employment_status);
                    $('#work_schedule_id').val(k.work_schedule_id);
                    $('#status').val(k.status);

                    // Toggle tanggal resign - set langsung tanpa konversi timezone
                    if (k.status === 'resign' && k.tanggal_resign) {
                        $('#tanggal_resign').val(k.tanggal_resign);
                        $('#resignDateContainer').show();
                    } else {
                        $('#resignDateContainer').hide();
                    }

                    $('#address').val(k.address);
                    $('#city').val(k.city);
                    $('#province').val(k.province);
                    $('#postal_code').val(k.postal_code);
                    $('#phone').val(k.phone);
                    $('#email').val(k.email);
                    $('#emergency_contact_name').val(k.emergency_contact_name);
                    $('#emergency_contact_phone').val(k.emergency_contact_phone);

                    // Data Administrasi
                    $('#bank').val(k.bank);
                    $('#nomor_rekening').val(k.nomor_rekening);
                    $('#tax_npwp').val(k.tax_npwp);
                    $('#bpjs_kesehatan').val(k.bpjs_kesehatan);
                    $('#bpjs_ketenagakerjaan').val(k.bpjs_ketenagakerjaan);

                    $('.form-control, .form-select').removeClass('is-invalid');
                    $('.nav-tabs button:first').tab('show');
                    karyawanModal.show();
                }
            });
        }

        function saveKaryawan() {
            const id = $('#karyawanId').val();
            const url = id ? `/api/karyawan/${id}` : '/api/karyawan';
            const method = id ? 'PUT' : 'POST';

            const data = {
                employee_code: $('#employee_code').val(),
                nik: $('#nik').val(),
                name: $('#name').val(),
                gender: $('#gender').val(),
                birth_place: $('#birth_place').val(),
                birth_date: formatDateForSubmission($('#birth_date').val()),
                marital_status: $('#marital_status').val(),
                tanggungan_anak: $('#tanggungan_anak').val() || 0,
                agama: $('#agama').val(),
                bangsa: $('#bangsa').val(),
                status_kependudukan: $('#status_kependudukan').val(),
                nama_ibu_kandung: $('#nama_ibu_kandung').val(),
                ktp: $('#nik').val(), // KTP sama dengan NIK
                kartu_keluarga: $('#kartu_keluarga').val(),
                department_id: $('#department_id').val(),
                sub_department_id: $('#sub_department_id').val(),
                position_id: $('#position_id').val(),
                lulusan_sekolah: $('#lulusan_sekolah').val(), // Pendidikan Terakhir dari dropdown
                join_date: formatDateForSubmission($('#join_date').val()),
                employment_status: $('#employment_status').val(),
                work_schedule_id: $('#work_schedule_id').val(),
                status: $('#status').val(),
                tanggal_resign: formatDateForSubmission($('#tanggal_resign').val()),
                bank: $('#bank').val(),
                nomor_rekening: $('#nomor_rekening').val(),
                tax_npwp: $('#tax_npwp').val(),
                bpjs_kesehatan: $('#bpjs_kesehatan').val(),
                bpjs_ketenagakerjaan: $('#bpjs_ketenagakerjaan').val(),
                address: $('#address').val(),
                city: $('#city').val(),
                province: $('#province').val(),
                postal_code: $('#postal_code').val(),
                phone: $('#phone').val(),
                email: $('#email').val(),
                emergency_contact_name: $('#emergency_contact_name').val(),
                emergency_contact_phone: $('#emergency_contact_phone').val()
            };

            $('.form-control, .form-select').removeClass('is-invalid');
            $('.invalid-feedback').text('');
            $('#submitBtn').prop('disabled', true);
            $('#submitSpinner').removeClass('d-none');

            $.ajax({
                url: url,
                method: method,
                data: data,
                success: function(response) {
                    karyawanModal.hide();
                    showAlert(response.message, 'success');
                    loadKaryawans(currentPage);
                    $('#karyawanForm')[0].reset();
                },
                error: function(xhr) {
                    if (xhr.status === 422) {
                        const errors = xhr.responseJSON.errors;
                        for (let field in errors) {
                            $(`#${field}`).addClass('is-invalid');
                            $(`#${field}Error`).text(errors[field][0]);
                        }
                    } else {
                        showAlert(xhr.responseJSON.message || 'Terjadi kesalahan', 'danger');
                    }
                },
                complete: function() {
                    $('#submitBtn').prop('disabled', false);
                    $('#submitSpinner').addClass('d-none');
                }
            });
        }

        // Format date from Y-m-d or ISO to dd-mm-yyyy (timezone-safe)
        function formatDateToDisplay(dateString) {
            if (!dateString || dateString === '-') return '-';

            // Extract date part only (ignore time and timezone)
            let datePart = dateString;
            if (dateString.includes('T')) {
                // ISO format: 2025-10-27T17:00:00.000000Z -> 2025-10-27
                datePart = dateString.split('T')[0];
            }

            // Parse Y-m-d format manually (no timezone conversion)
            const [year, month, day] = datePart.split('-');

            // Return in dd-mm-yyyy format
            return `${day}-${month}-${year}`;
        }

        // Helper function to ensure date is in Y-m-d format for form submission (timezone-safe)
        function formatDateForSubmission(dateString) {
            if (!dateString) return '';

            // If already in Y-m-d format, return as is
            if (typeof dateString === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(dateString)) {
                return dateString;
            }

            // If it's a date object, format to Y-m-d
            if (dateString instanceof Date) {
                const year = dateString.getFullYear();
                const month = String(dateString.getMonth() + 1).padStart(2, '0');
                const day = String(dateString.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            }

            return dateString;
        }

        function showDetail(id) {
            $.ajax({
                url: `/api/karyawan/${id}`,
                method: 'GET',
                success: function(response) {
                    const k = response.data;

                    // Format tanggal ke dd-mm-yyyy
                    const birthDateFormatted = formatDateToDisplay(k.birth_date);
                    const joinDateFormatted = formatDateToDisplay(k.join_date);
                    const resignDateFormatted = formatDateToDisplay(k.tanggal_resign);

                    // Data Pribadi
                    $('#detailEmployeeCode').text(k.employee_code);
                    $('#detailNik').text(k.nik || '-');
                    $('#detailName').text(k.name);
                    $('#detailGender').text(k.gender === 'L' ? 'Laki-laki' : 'Perempuan');
                    $('#detailBirth').text(`${k.birth_place || '-'}, ${birthDateFormatted}`);
                    $('#detailAgama').text(k.agama || '-');
                    $('#detailBangsa').text(k.bangsa || '-');
                    $('#detailStatusKependudukan').text(k.status_kependudukan || '-');
                    $('#detailMaritalStatus').text(k.marital_status);
                    $('#detailTanggunganAnak').text(k.tanggungan_anak || '0');
                    $('#detailNamaIbuKandung').text(k.nama_ibu_kandung || '-');
                    $('#detailKartuKeluarga').text(k.kartu_keluarga || '-');

                    // Data Pekerjaan
                    $('#detailDepartment').text(k.department ? k.department.name : '-');
                    $('#detailSubDepartment').text(k.sub_department ? k.sub_department.name : '-');
                    $('#detailPosition').text(k.position ? k.position.name : '-');
                    $('#detailLulusanSekolah').text(k.lulusan_sekolah || '-');
                    $('#detailJoinDate').text(joinDateFormatted);
                    $('#detailEmploymentStatus').text(k.employment_status);
                    $('#detailShiftType').text(k.work_schedule ? k.work_schedule.name : '-');
                    $('#detailStatus').html(getStatusBadge(k.status));

                    // Tampilkan tanggal resign jika status resign
                    if (k.status === 'resign' && k.tanggal_resign) {
                        $('#detailTanggalResign').text(resignDateFormatted);
                        $('#detailResignDateRow').show();
                    } else {
                        $('#detailResignDateRow').hide();
                    }

                    // Kontak & Alamat
                    $('#detailAddress').text(k.address);
                    $('#detailCity').text(k.city);
                    $('#detailProvince').text(k.province);
                    $('#detailPostalCode').text(k.postal_code);
                    $('#detailPhone').text(k.phone);
                    $('#detailEmail').text(k.email);

                    // Kontak Darurat
                    $('#detailEmergencyName').text(k.emergency_contact_name);
                    $('#detailEmergencyPhone').text(k.emergency_contact_phone);

                    // Data Keuangan
                    $('#detailBank').text(k.bank || '-');
                    $('#detailNomorRekening').text(k.nomor_rekening || '-');

                    // Data Administrasi & Pajak
                    $('#detailTaxNpwp').text(k.tax_npwp || '-');
                    $('#detailBpjsKesehatan').text(k.bpjs_kesehatan || '-');
                    $('#detailBpjsKetenagakerjaan').text(k.bpjs_ketenagakerjaan || '-');

                    detailModal.show();
                }
            });
        }

        function deleteKaryawan(id) {
            Swal.fire({
                title: 'Hapus Karyawan?',
                html: 'Apakah Anda yakin ingin menghapus karyawan ini?<br><br><small class="text-danger">Peringatan: Data akun user juga akan terhapus.</small>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: '<i class="bx bx-trash"></i> Ya, Hapus!',
                cancelButtonText: '<i class="bx bx-x"></i> Batal',
                buttonsStyling: true,
                reverseButtons: true,
                allowOutsideClick: false,
                allowEscapeKey: false,
                backdrop: true,
                focusConfirm: false
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `/api/karyawan/${id}`,
                        method: 'DELETE',
                        success: function(response) {
                            Swal.fire({
                                title: 'Berhasil!',
                                text: response.message,
                                icon: 'success',
                                timer: 2000,
                                showConfirmButton: false,
                                allowOutsideClick: false
                            });
                            loadKaryawans(currentPage);
                        },
                        error: function(xhr) {
                            Swal.fire({
                                title: 'Gagal!',
                                text: xhr.responseJSON.message || 'Gagal menghapus karyawan',
                                icon: 'error',
                                confirmButtonText: 'OK',
                                allowOutsideClick: false
                            });
                        }
                    });
                }
            });
        }

        function showAlert(message, type) {
            const icon = type === 'success' ? 'bx-check-circle' : 'bx-error-circle';
            const alert = `
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    <i class='bx ${icon} me-2'></i>
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
            $('#alertContainer').html(alert);
            setTimeout(function() {
                $('.alert').fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        }

        function importKaryawan() {
            const fileInput = $('#import_file')[0];

            if (!fileInput.files.length) {
                $('#fileError').text('File Excel harus dipilih');
                $('#import_file').addClass('is-invalid');
                return;
            }

            const formData = new FormData();
            formData.append('file', fileInput.files[0]);

            $('#import_file').removeClass('is-invalid');
            $('#fileError').text('');
            $('#importBtn').prop('disabled', true);
            $('#importProgress').removeClass('d-none');

            $.ajax({
                url: '/api/karyawan/import',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    importModal.hide();
                    Swal.fire({
                        title: 'Berhasil!',
                        text: response.message,
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    $('#importForm')[0].reset();
                    loadKaryawans(1);
                },
                error: function(xhr) {
                    let errorMessage = 'Terjadi kesalahan saat import';

                    if (xhr.status === 422) {
                        if (xhr.responseJSON.errors) {
                            errorMessage = '<ul class="mb-0 ps-3">';
                            xhr.responseJSON.errors.forEach(error => {
                                errorMessage += `<li>${error}</li>`;
                            });
                            errorMessage += '</ul>';
                        } else {
                            errorMessage = xhr.responseJSON.message;
                        }
                    }

                    Swal.fire({
                        title: 'Gagal Import!',
                        html: errorMessage,
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                },
                complete: function() {
                    $('#importBtn').prop('disabled', false);
                    $('#importProgress').addClass('d-none');
                }
            });
        }
    </script>
@endpush
