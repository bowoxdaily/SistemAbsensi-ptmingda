@if(Auth::user()->role === 'karyawan' || Auth::user()->role === 'employee')
    <div id="ann-overlay" style="display:none"></div>
    <div id="ann-popup" style="display:none">
        <div class="ann-header">
            <img src="{{ asset('sneat-1.0.0/assets/img/logo.png') }}" alt="OPL">
        </div>
        <div class="ann-body">
            <div id="ann-popup-list"></div>
            <div class="d-flex justify-content-end mt-3">
                <button class="btn btn-sm btn-primary" onclick="closeOplPopup()">Tutup</button>
            </div>
        </div>
    </div>

    @push('styles')
    <style>
        /* Blue carousel control icons for popup carousel */
        #oplPopupCarousel .carousel-control-next-icon,
        #oplPopupCarousel .carousel-control-prev-icon {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='%230d6efd' viewBox='0 0 16 16'%3E%3Cpath d='M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z'/%3E%3C/svg%3E");
            background-size: 1.2rem 1.2rem;
        }
        #oplPopupCarousel .carousel-control-next,
        #oplPopupCarousel .carousel-control-prev { width: 3rem; }
    </style>
    @endpush

    @push('scripts')
    <script>
        async function fetchOplPopups() {
            try {
                const res = await fetch('/employee/opls/popups', { headers: {'Accept':'application/json'} });
                const j = await res.json();
                if (!j.success) return;
                const items = j.data;
                if (!items || items.length === 0) return;
                const list = document.getElementById('ann-popup-list');
                // Render carousel inside popup if images exist
                const hasImages = items.some(i => i.attachment_url);
                if (hasImages) {
                    const carouselId = 'oplPopupCarousel';
                    let indicators = '';
                    let inner = '';
                    items.forEach((i, idx) => {
                        const active = idx === 0 ? 'active' : '';
                        indicators += `<button type="button" data-bs-target="#${carouselId}" data-bs-slide-to="${idx}" class="${active}" aria-current="${active ? 'true' : 'false'}" aria-label="Slide ${idx+1}"></button>`;
                        inner += `
                            <div class="carousel-item ${active}">
                                <div class="p-3 text-center">
                                    ${i.attachment_url ? `<img src="${i.attachment_url}" data-title="${(i.title||'').replace(/"/g,'&quot;')}" style="max-width:520px;max-height:420px;border-radius:8px;cursor:pointer;" onclick="openOplViewer(this.getAttribute('src'), this.getAttribute('data-title'))">` : `<div class="ann-content"><strong>${i.title}</strong></div>`}
                                                <div class="mt-2"><strong>${i.title}</strong></div>
                                </div>
                            </div>
                        `;
                    });

                    list.innerHTML = `
                        <div id="${carouselId}" class="carousel slide" data-bs-ride="carousel">
                            <div class="carousel-indicators">${indicators}</div>
                            <div class="carousel-inner">${inner}</div>
                            <button class="carousel-control-prev" type="button" data-bs-target="#${carouselId}" data-bs-slide="prev">
                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Previous</span>
                            </button>
                            <button class="carousel-control-next" type="button" data-bs-target="#${carouselId}" data-bs-slide="next">
                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Next</span>
                            </button>
                        </div>
                    `;
                } else {
                    list.innerHTML = items.map(i => `
                        <div>
                            <h5 class="ann-title">${i.title}</h5>
                            <div class="ann-content">${i.content}</div>
                        </div>
                    `).join('');
                }
                // Use SweetAlert2 to show OPL popups (reuse announcement style and z-index)
                oplQueue = items;
                waitForSwalThenShowOpl(0);
            } catch (e) { console.error(e); }
        }

        function closeOplPopup(){
            document.getElementById('ann-overlay').style.display = 'none';
            document.getElementById('ann-popup').style.display = 'none';
        }

        // Image viewer for popup (with logging and error handling)
        function openOplViewer(src, title) {
            console.log('openOplViewer called', src, title);
            if (!src) {
                console.warn('No src provided to openOplViewer');
                return;
            }
            let overlay = document.getElementById('opl-viewer-overlay');
            if (!overlay) {
                overlay = document.createElement('div');
                overlay.id = 'opl-viewer-overlay';
                overlay.style = 'position:fixed;inset:0;background:rgba(0,0,0,0.6);display:flex;align-items:center;justify-content:center;z-index:200000;padding:12px;';
                const viewer = document.createElement('div'); viewer.id = 'opl-viewer'; viewer.style = 'background:#fff;padding:12px;border-radius:12px;max-width:95vw;max-height:calc(90vh - 24px);overflow:auto;';
                const ttl = document.createElement('div'); ttl.id='opl-viewer-title'; ttl.style='font-weight:700;margin-bottom:8px';
                const img = document.createElement('img'); img.id='opl-viewer-img'; img.style='max-width:100%;max-height:calc(90vh - 160px);border-radius:8px;display:block;margin-bottom:8px;object-fit:contain;';
                img.onload = function(){ console.log('OPL image loaded', src); };
                img.onerror = function(){ console.warn('Failed to load OPL image', src); this.style.display='none'; };
                const btn = document.createElement('button'); btn.className='btn btn-sm btn-secondary'; btn.textContent='Tutup'; btn.onclick = closeOplViewer;
                viewer.appendChild(ttl); viewer.appendChild(img); viewer.appendChild(btn);
                overlay.appendChild(viewer); document.body.appendChild(overlay);
            }
            document.getElementById('opl-viewer-title').textContent = title || '';
            const imgEl = document.getElementById('opl-viewer-img');
            imgEl.style.display = 'block';
            imgEl.src = src;
            overlay.style.display = 'flex';
        }

        function closeOplViewer() {
            const overlay = document.getElementById('opl-viewer-overlay');
            if (overlay) overlay.style.display = 'none';
        }
        // Queue for OPL popups
        let oplQueue = [];

        // Show OPL at index using Swal
        async function showOplAtIndex(idx) {
            if (idx >= oplQueue.length) return;
            const a = oplQueue[idx];
            const isLast = idx === oplQueue.length - 1;

            const html = `
                <div style="text-align:center;">
                    ${a.attachment_url ? `<img src="${a.attachment_url}" style="max-width:100%;max-height:60vh;border-radius:8px;margin-bottom:12px;">` : ''}
                    <h4 style="margin:0 0 8px">${a.title}</h4>
                    <!-- content removed; OPL displays title and image only -->
                    ${a.attachment_url ? `<div style="margin-top:12px"><a class="btn btn-sm btn-primary" href="${a.attachment_url}" download="${(a.title||'opl').replace(/\"/g,'').replace(/\'/g,'')}"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 16 16" fill="currentColor" class="bi bi-download me-1"><path d="M.5 9.9a.5.5 0 0 1 .5.5V13a2 2 0 0 0 2 2h9a2 2 0 0 0 2-2v-2.6a.5.5 0 0 1 1 0V13a3 3 0 0 1-3 3h-9A3 3 0 0 1 0 13v-2.6a.5.5 0 0 1 .5-.5z"/><path d="M5.354 6.146a.5.5 0 0 1 .708 0L8 8.086l1.938-1.94a.5.5 0 1 1 .707.708l-2.25 2.25a.5.5 0 0 1-.708 0l-2.25-2.25a.5.5 0 0 1 0-.708z"/><path d="M8 1.5a.5.5 0 0 1 .5.5v6.086a.5.5 0 0 1-1 0V2a.5.5 0 0 1 .5-.5z"/></svg>Download</a></div>` : ''}
                </div>
            `;

            try {
                const result = await Swal.fire({
                    html: html,
                    confirmButtonText: isLast ? 'Tutup' : 'Lanjut',
                    showDenyButton: true,
                    denyButtonText: 'Lihat Selengkapnya',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showCloseButton: true,
                    width: Math.min(640, window.innerWidth * 0.94),
                    customClass: { popup: 'ann-modern-popup' }
                });

                // If user chose to view more, navigate to OPL detail page
                if (result.isDenied) {
                    // Mark as read before navigating (best-effort)
                    try {
                        const csrf = document.querySelector('meta[name="csrf-token"]').content;
                        await fetch(`/employee/opls/${a.id}/mark-read`, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' } });
                    } catch (e) { console.warn('Failed to mark OPL read', e); }

                    // Navigate to detail page (fallback to list if route differs)
                    window.location.href = `/employee/opls?focus=${a.id}`;
                    return; // stop processing queue since user left
                }

                // Otherwise, mark as read for today and continue to next popup
                try {
                    const csrf = document.querySelector('meta[name="csrf-token"]').content;
                    await fetch(`/employee/opls/${a.id}/mark-read`, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' } });
                } catch (e) { console.warn('Failed to mark OPL read', e); }

                // proceed to next popup
                showOplAtIndex(idx + 1);
            } catch (e) {
                console.warn('Swal OPL error:', e);
            }
        }

        function waitForSwalThenShowOpl(idx = 0, attempt = 0) {
            if (typeof Swal !== 'undefined') {
                showOplAtIndex(idx);
            } else if (attempt < 20) {
                setTimeout(() => waitForSwalThenShowOpl(idx, attempt + 1), 300);
            }
        }

        // Fetch popups when the page loads (layout includes this partial on every page for employees)
        document.addEventListener('DOMContentLoaded', function(){
            fetchOplPopups();
        });
    </script>
    @endpush
@endif
