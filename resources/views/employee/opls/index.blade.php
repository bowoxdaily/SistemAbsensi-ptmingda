@extends('layouts.app')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">One Point Lesson</h4>

    <div class="card">
        <div class="card-body">
            <div id="opls-list">Memuat...</div>
        </div>
    </div>
</div>

@push('styles')
<style>
    /* Responsive image sizing for OPL page */
    .opl-image {
        max-height: 60vh;
        width: auto;
        display: block;
        margin: 0 auto;
    }

    /* Viewer tweaks for small screens */
    @media (max-width: 576px) {
        .opl-image { max-height: 45vh; }
        #opl-viewer { max-height: calc(80vh - 24px) !important; }
        #opl-viewer-img { max-height: calc(70vh - 120px) !important; }
    }
</style>
@endpush

@push('styles')
<style>
    /* Blue carousel control icons */
    .carousel-control-next-icon,
    .carousel-control-prev-icon {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='%230d6efd' viewBox='0 0 16 16'%3E%3Cpath d='M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z'/%3E%3C/svg%3E");
        background-size: 1.25rem 1.25rem;
    }

    /* Slightly larger touch targets */
    .carousel-control-next,
    .carousel-control-prev {
        width: 3.2rem;
    }
</style>
@endpush

@push('scripts')
<script>
    async function loadOpls() {
        const res = await fetch('/api/employee/opls?per_page=50', { headers: {'Accept':'application/json'} });
        const j = await res.json();
        const wrap = document.getElementById('opls-list');
        if (!j.success) { wrap.innerHTML = 'Gagal memuat'; return; }
        const items = j.data.data || j.data;
        if (items.length === 0) { wrap.innerHTML = '<div class="p-3">Belum ada OPL.</div>'; return; }

        // If any item has an image, render a Bootstrap carousel
        const hasImages = items.some(i => i.attachment_url);
        if (hasImages) {
            const carouselId = 'oplCarousel';
            let indicators = '';
            let inner = '';
            items.forEach((i, idx) => {
                const active = idx === 0 ? 'active' : '';
                indicators += `<button type="button" data-bs-target="#${carouselId}" data-bs-slide-to="${idx}" class="${active}" aria-current="${active ? 'true' : 'false'}" aria-label="Slide ${idx+1}"></button>`;
                inner += `
                    <div class="carousel-item ${active}">
                        <div class="d-flex flex-column align-items-center p-3">
                            ${i.attachment_url ? `<img src="${i.attachment_url}" class="img-fluid opl-image" data-title="${(i.title||'').replace(/"/g,'&quot;')}" style="border-radius:8px;cursor:pointer;" onclick="openOplViewer(this.getAttribute('src'), this.getAttribute('data-title'))">` : `<div class="p-3"><strong>${i.title}</strong></div>`}
                            <div class="mt-3 w-100 d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>${i.title}</strong>
                                    <div class="text-muted small">${new Date(i.created_at).toLocaleString()}</div>
                                </div>
                                ${i.attachment_url ? `<a class="btn btn-sm btn-outline-primary" href="${i.attachment_url}" download="${(i.title||'opl').replace(/\"/g,'').replace(/\'/g,'')}"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 16 16" fill="currentColor" class="bi bi-download me-1"><path d="M.5 9.9a.5.5 0 0 1 .5.5V13a2 2 0 0 0 2 2h9a2 2 0 0 0 2-2v-2.6a.5.5 0 0 1 1 0V13a3 3 0 0 1-3 3h-9A3 3 0 0 1 0 13v-2.6a.5.5 0 0 1 .5-.5z"/><path d="M5.354 6.146a.5.5 0 0 1 .708 0L8 8.086l1.938-1.94a.5.5 0 1 1 .707.708l-2.25 2.25a.5.5 0 0 1-.708 0l-2.25-2.25a.5.5 0 0 1 0-.708z"/><path d="M8 1.5a.5.5 0 0 1 .5.5v6.086a.5.5 0 0 1-1 0V2a.5.5 0 0 1 .5-.5z"/></svg>Download</a>` : ''}
                            </div>
                        </div>
                    </div>
                `;
            });

            wrap.innerHTML = `
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
            wrap.innerHTML = items.map(i => `
                <div class="mb-3 card p-3">
                    <h5>${i.title}</h5>
                    <div class="text-muted small mb-2">${new Date(i.created_at).toLocaleString()}</div>
                </div>
            `).join('');
        }
    }
        loadOpls();
    
    // If URL contains ?focus={id}, open that OPL in the viewer after load
    document.addEventListener('DOMContentLoaded', function(){
        const params = new URLSearchParams(window.location.search);
        const focus = params.get('focus');
        if (focus) {
            // wait until items are loaded
            (async function waitForItems(attempt = 0) {
                const wrap = document.getElementById('opls-list');
                if (wrap && wrap.innerHTML && wrap.innerHTML.indexOf('Memuat') === -1) {
                    try {
                        const res = await fetch('/api/employee/opls?per_page=50', { headers: {'Accept':'application/json'} });
                        const j = await res.json();
                        const items = j.data.data || j.data;
                        const target = items.find(i => String(i.id) === String(focus));
                        if (target && target.attachment_url) {
                            openOplViewer(target.attachment_url, target.title);
                        }
                    } catch (e) { console.warn(e); }
                    return;
                }
                if (attempt < 20) setTimeout(() => waitForItems(attempt + 1), 200);
            })();
        }
    });
    
    // Image viewer modal functions (global for this page)
    function openOplViewer(src, title) {
        console.log('openOplViewer called', src, title);
        if (!src) { console.warn('No src provided'); return; }
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
</script>
@endpush

@endsection
