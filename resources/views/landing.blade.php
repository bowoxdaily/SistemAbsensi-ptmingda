<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>{{ config('app.name', 'PT. Mingda International Footwear') }}</title>
    <meta name="description" content="PT. Mingda International Footwear — Perusahaan manufaktur alas kaki berkualitas internasional."/>
    <link rel="icon" type="image/x-icon" href="{{ asset('sneat-1.0.0/assets/img/favicon/favicon.ico') }}"/>
    <link rel="preconnect" href="https://fonts.googleapis.com"/>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="{{ asset('sneat-1.0.0/assets/vendor/fonts/boxicons.css') }}"/>
    <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    :root{--pri:#696cff;--pri-d:#5f61e6;--pri-bg:#e7e7ff;--dk:#232333;--dk2:#2b2c40;--tx:#697a8d;--txl:#8e9bae;--bg:#f5f5f9;--w:#fff;--gold:#c8a84e;--gold-l:#e8d48b;--sh:0 2px 8px rgba(0,0,0,.06);--shl:0 6px 30px rgba(0,0,0,.1)}
    html{scroll-behavior:smooth}
    body{font-family:'Public Sans',sans-serif;color:var(--tx);background:var(--bg);line-height:1.7;overflow-x:hidden}
    a{text-decoration:none;color:inherit}
    img{max-width:100%;display:block}
    .ct{max-width:1140px;margin:0 auto;padding:0 1.5rem}
    .btn{display:inline-flex;align-items:center;gap:.45rem;padding:.6rem 1.5rem;border-radius:8px;font-family:inherit;font-size:.85rem;font-weight:600;border:none;cursor:pointer;transition:all .2s}
    .btn-pri{background:var(--pri);color:var(--w)}.btn-pri:hover{background:var(--pri-d);transform:translateY(-1px);box-shadow:0 4px 14px rgba(105,108,255,.3)}
    .btn-w{background:var(--w);color:var(--dk)}.btn-w:hover{background:#f0f0ff;transform:translateY(-1px)}
    .btn-o{background:transparent;color:var(--w);border:1.5px solid rgba(255,255,255,.4)}.btn-o:hover{border-color:#fff;background:rgba(255,255,255,.08)}
    .btn-ol{background:transparent;color:var(--pri);border:1.5px solid var(--pri)}.btn-ol:hover{background:var(--pri-bg)}
    .btn-lg{padding:.75rem 2.2rem;font-size:.95rem}
    .fu{opacity:0;transform:translateY(28px);transition:opacity .7s ease,transform .7s ease}.fu.vi{opacity:1;transform:translateY(0)}
    </style>
</head>
<body>
<style>
.nav{position:fixed;top:0;left:0;right:0;z-index:1000;transition:all .35s}
.nav.solid{background:var(--w);box-shadow:var(--sh)}
.nav .ct{display:flex;align-items:center;justify-content:space-between;height:72px}
.nav-brand{display:flex;align-items:center;gap:.7rem}
.nav-brand img{height:44px;width:auto}
.nav-brand-t{font-weight:700;font-size:.9rem;line-height:1.2;transition:color .3s}
.nav-brand-t small{display:block;font-weight:400;font-size:.68rem;opacity:.7}
.nav:not(.solid) .nav-brand-t{color:var(--w)}
.nav.solid .nav-brand-t{color:var(--dk)}
.nav-links{display:flex;align-items:center;gap:.3rem}
.nav-links a{padding:.4rem .8rem;border-radius:6px;font-size:.82rem;font-weight:500;transition:all .2s}
.nav:not(.solid) .nav-links a{color:rgba(255,255,255,.8)}.nav:not(.solid) .nav-links a:hover{color:#fff;background:rgba(255,255,255,.1)}
.nav.solid .nav-links a{color:var(--tx)}.nav.solid .nav-links a:hover{color:var(--pri);background:var(--pri-bg)}
.nav-login{margin-left:.5rem}
.nav:not(.solid) .nav-login .btn{background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.25)}.nav:not(.solid) .nav-login .btn:hover{background:rgba(255,255,255,.25)}
.nav.solid .nav-login .btn{background:var(--pri);color:#fff}.nav.solid .nav-login .btn:hover{background:var(--pri-d)}
.nav-toggle{display:none;background:none;border:none;font-size:1.5rem;cursor:pointer;padding:.3rem}
.nav:not(.solid) .nav-toggle{color:#fff}.nav.solid .nav-toggle{color:var(--dk)}
@media(max-width:768px){
.nav-toggle{display:block}
.nav-mid{display:none;position:absolute;top:72px;left:0;right:0;background:var(--w);box-shadow:var(--shl);padding:1rem;flex-direction:column;gap:.3rem}
.nav-mid.open{display:flex}
.nav-mid a{color:var(--dk)!important;padding:.6rem 1rem;border-radius:6px}.nav-mid a:hover{background:var(--pri-bg)!important;color:var(--pri)!important}
.nav-login{margin-left:0;margin-top:.3rem}.nav-login .btn{width:100%;justify-content:center;background:var(--pri)!important;color:#fff!important;border:none!important}
}
</style>

<nav class="nav" id="mainNav">
<div class="ct">
    <a href="{{ url('/') }}" class="nav-brand">
        <img src="{{ asset('sneat-1.0.0/assets/img/logo.png') }}" alt="Logo PT Mingda"/>
        <div class="nav-brand-t">PT. Mingda International<small>Footwear Industry</small></div>
    </a>
    <button class="nav-toggle" id="navToggle" aria-label="Menu"><i class='bx bx-menu'></i></button>
    <div class="nav-links nav-mid" id="navMid">
        <a href="#tentang">Tentang</a>
        <a href="#keunggulan">Keunggulan</a>
        <a href="#galeri">Galeri</a>
        <a href="#portal">Portal</a>
        <a href="#kontak">Kontak</a>
        <div class="nav-login">
            <a href="{{ route('login') }}" class="btn"><i class='bx bx-log-in'></i> Login Karyawan</a>
        </div>
    </div>
</div>
</nav>

<style>
.hero{position:relative;min-height:100vh;display:flex;align-items:center;background:linear-gradient(135deg,var(--dk) 0%,#1a1a2e 40%,#16213e 100%);overflow:hidden}
.hero::before{content:'';position:absolute;inset:0;background:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.02'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E")}
.hero::after{content:'';position:absolute;bottom:0;left:0;right:0;height:120px;background:linear-gradient(to top,var(--bg),transparent)}
.hero-accent{position:absolute;border-radius:50%;background:rgba(105,108,255,.08)}
.hero-accent.a1{width:500px;height:500px;top:-150px;right:-100px}
.hero-accent.a2{width:300px;height:300px;bottom:50px;left:-80px;background:rgba(200,168,78,.06)}
.hero .ct{position:relative;z-index:1;display:grid;grid-template-columns:1.1fr .9fr;align-items:center;gap:4rem;padding-top:4rem;padding-bottom:4rem}
.hero-content h1{font-size:3rem;font-weight:800;color:var(--w);line-height:1.12;margin-bottom:1.2rem}
.hero-content h1 span{color:var(--gold)}
.hero-sub{font-size:1.05rem;color:rgba(255,255,255,.65);margin-bottom:.8rem;max-width:500px;line-height:1.8}
.hero-tagline{display:inline-flex;align-items:center;gap:.5rem;padding:.35rem 1rem;border-radius:20px;border:1px solid rgba(200,168,78,.3);background:rgba(200,168,78,.08);color:var(--gold-l);font-size:.75rem;font-weight:600;letter-spacing:.06em;margin-bottom:1.6rem}
.hero-btns{display:flex;gap:.8rem;flex-wrap:wrap;margin-top:2rem}
.hero-stats{display:flex;gap:2.5rem;margin-top:2.5rem;padding-top:2rem;border-top:1px solid rgba(255,255,255,.08)}
.hero-stats .hs-item .hs-num{font-size:1.6rem;font-weight:800;color:var(--w)}
.hero-stats .hs-item .hs-lab{font-size:.72rem;color:rgba(255,255,255,.45);margin-top:.1rem}
.hero-visual{display:flex;justify-content:center;align-items:center}
.hero-img-wrap{position:relative;width:100%;max-width:380px}
.hero-img-wrap .hero-shoe{width:100%;height:320px;border-radius:20px;background:linear-gradient(180deg,rgba(22,33,62,0.25) 0%,rgba(15,17,23,0.7) 100%),url('https://images.unsplash.com/photo-1608231387042-66d1773070a5?auto=format&fit=crop&w=900&q=80') center/cover no-repeat;display:flex;align-items:center;justify-content:center;border:1px solid rgba(255,255,255,.15);box-shadow:0 15px 35px rgba(0,0,0,.4)}
.hero-shoe i{display:none}
.hero-shoe-label{position:absolute;bottom:-12px;left:50%;transform:translateX(-50%);background:var(--gold);color:var(--dk);padding:.4rem 1.2rem;border-radius:8px;font-size:.72rem;font-weight:700;letter-spacing:.05em;white-space:nowrap}
@media(max-width:992px){.hero .ct{grid-template-columns:1fr;text-align:center}.hero-sub{margin-left:auto;margin-right:auto}.hero-btns{justify-content:center}.hero-stats{justify-content:center}.hero-visual{margin-top:2rem}}
@media(max-width:576px){.hero-content h1{font-size:2rem}.hero-stats{gap:1.5rem}.hero-stats .hs-num{font-size:1.3rem}.nav-brand-t{display:none}}
</style>

<section class="hero">
    <div class="hero-accent a1"></div>
    <div class="hero-accent a2"></div>
    <div class="ct">
        <div class="hero-content">
            <div class="hero-tagline"><i class='bx bx-badge-check'></i> Trusted Footwear Manufacturer</div>
            <h1>Menghasilkan Alas Kaki<br>Berkualitas <span>Internasional</span></h1>
            <p class="hero-sub">PT. Mingda International Footwear adalah perusahaan manufaktur alas kaki yang berkomitmen menghasilkan produk berkualitas tinggi dengan standar internasional dan didukung oleh sumber daya manusia yang profesional.</p>
            <div class="hero-btns">
                <a href="#tentang" class="btn btn-w btn-lg"><i class='bx bx-building-house'></i> Tentang Kami</a>
                <a href="{{ route('login') }}" class="btn btn-o btn-lg"><i class='bx bx-log-in'></i> Portal Karyawan</a>
            </div>
            <div class="hero-stats">
                <div class="hs-item"><div class="hs-num">1.000+</div><div class="hs-lab">Tenaga Kerja</div></div>
                <div class="hs-item"><div class="hs-num">10+</div><div class="hs-lab">Departemen</div></div>
                <div class="hs-item"><div class="hs-num">Global</div><div class="hs-lab">Standar Kualitas</div></div>
            </div>
        </div>
        <div class="hero-visual">
            <div class="hero-img-wrap">
                <div class="hero-shoe"></div>
                <div class="hero-shoe-label">MANUFACTURING EXCELLENCE</div>
            </div>
        </div>
    </div>
</section>

<style>
.sec{padding:5rem 0}
.sec-w{background:var(--w)}
.sec-head{text-align:center;margin-bottom:3.5rem}
.sec-eye{display:inline-flex;align-items:center;gap:.35rem;font-size:.72rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--pri);margin-bottom:.5rem}
.sec-title{font-size:2rem;font-weight:800;color:var(--dk);margin-bottom:.5rem}
.sec-sub{font-size:.92rem;color:var(--tx);max-width:540px;margin:0 auto}
.about-grid{display:grid;grid-template-columns:1fr 1fr;gap:3.5rem;align-items:center}
.about-img{height:340px;border-radius:16px;background:linear-gradient(180deg,rgba(0,0,0,0.1) 0%,rgba(0,0,0,0.55) 100%),url('https://images.unsplash.com/photo-1581091226825-a6a2a5aee158?auto=format&fit=crop&w=900&q=80') center/cover no-repeat;display:flex;align-items:center;justify-content:center;position:relative;overflow:hidden;box-shadow:var(--sh)}
.about-img i{display:none}
.about-img .about-badge{position:absolute;bottom:1.2rem;right:1.2rem;background:var(--pri);color:#fff;padding:.5rem 1rem;border-radius:8px;font-size:.72rem;font-weight:700;display:flex;align-items:center;gap:.4rem}
.about-text h2{font-size:1.8rem;font-weight:800;color:var(--dk);margin-bottom:1rem;line-height:1.2}
.about-text h2 span{color:var(--pri)}
.about-text p{margin-bottom:1rem;font-size:.88rem;line-height:1.8}
.vm-grid{display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-top:1.5rem}
.vm-card{padding:1.3rem;border-radius:10px;border:1px solid #eee}
.vm-card h4{font-size:.82rem;font-weight:700;color:var(--dk);margin-bottom:.4rem;display:flex;align-items:center;gap:.4rem}
.vm-card h4 i{color:var(--pri);font-size:1.1rem}
.vm-card p{font-size:.78rem;line-height:1.7}
@media(max-width:992px){.about-grid{grid-template-columns:1fr}.about-img{height:240px}}
</style>

<section class="sec sec-w" id="tentang">
<div class="ct">
    <div class="about-grid">
        <div class="about-img fu">
            <div class="about-badge"><i class='bx bx-check-shield'></i> ISO Certified</div>
        </div>
        <div class="about-text fu">
            <div class="sec-eye"><i class='bx bx-info-circle'></i> TENTANG KAMI</div>
            <h2>Produsen Alas Kaki<br><span>Terpercaya</span> & Berkualitas</h2>
            <p>PT. Mingda International Footwear merupakan perusahaan yang bergerak di bidang manufaktur alas kaki (footwear) yang berlokasi di Indonesia. Sebagai mitra manufaktur terpercaya, kami telah melayani berbagai brand internasional dan domestik dengan dedikasi tinggi terhadap mutu dan keunggulan produk.</p>
            <p>Didukung oleh lebih dari 1.000 tenaga kerja profesional yang tersebar di berbagai departemen, kami terus berkomitmen untuk menghadirkan produk yang inovatif, berkualitas, dan tepat waktu.</p>
            <div class="vm-grid">
                <div class="vm-card">
                    <h4><i class='bx bx-bullseye'></i> Visi</h4>
                    <p>Menjadi perusahaan manufaktur alas kaki terdepan di Asia Tenggara yang dikenal akan kualitas, inovasi, dan keberlanjutan.</p>
                </div>
                <div class="vm-card">
                    <h4><i class='bx bx-target-lock'></i> Misi</h4>
                    <p>Menghasilkan produk alas kaki berkualitas internasional dengan mengedepankan efisiensi, kesejahteraan karyawan, dan kepuasan pelanggan.</p>
                </div>
            </div>
        </div>
    </div>
</div>
</section>

<style>
.adv-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:1.5rem}
.adv-card{background:var(--w);border-radius:12px;padding:2rem 1.4rem;box-shadow:var(--sh);transition:all .25s;border:1px solid transparent;text-align:center}
.adv-card:hover{transform:translateY(-4px);box-shadow:var(--shl);border-color:var(--pri-bg)}
.adv-icon{width:56px;height:56px;border-radius:14px;display:inline-flex;align-items:center;justify-content:center;font-size:1.6rem;margin-bottom:1rem}
.adv-card h3{font-size:.9rem;font-weight:700;color:var(--dk);margin-bottom:.5rem}
.adv-card p{font-size:.78rem;line-height:1.7}
@media(max-width:992px){.adv-grid{grid-template-columns:repeat(2,1fr)}}
@media(max-width:576px){.adv-grid{grid-template-columns:1fr}}
</style>

<section class="sec" id="keunggulan">
<div class="ct">
    <div class="sec-head fu">
        <div class="sec-eye"><i class='bx bx-award'></i> KEUNGGULAN KAMI</div>
        <h2 class="sec-title">Mengapa Memilih Kami</h2>
        <p class="sec-sub">Komitmen kami dalam menghasilkan produk terbaik didukung oleh berbagai keunggulan operasional</p>
    </div>
    <div class="adv-grid">
        <div class="adv-card fu">
            <div class="adv-icon" style="background:#e7e7ff;color:var(--pri)"><i class='bx bx-diamond'></i></div>
            <h3>Kualitas Produksi</h3>
            <p>Standar quality control ketat di setiap tahap produksi memastikan output produk berkualitas tinggi.</p>
        </div>
        <div class="adv-card fu">
            <div class="adv-icon" style="background:#e8fadf;color:#56ca00"><i class='bx bx-group'></i></div>
            <h3>SDM Profesional</h3>
            <p>Didukung oleh lebih dari 1.000 karyawan terlatih dan berpengalaman di berbagai lini produksi.</p>
        </div>
        <div class="adv-card fu">
            <div class="adv-icon" style="background:#fff3e0;color:#ff9800"><i class='bx bx-cog'></i></div>
            <h3>Teknologi Modern</h3>
            <p>Pemanfaatan teknologi terkini dalam proses manufaktur dan sistem manajemen karyawan digital.</p>
        </div>
        <div class="adv-card fu">
            <div class="adv-icon" style="background:#e0f4ff;color:#16b1ff"><i class='bx bx-globe'></i></div>
            <h3>Standar Internasional</h3>
            <p>Memenuhi standar kualitas internasional dan telah bermitra dengan berbagai brand global.</p>
        </div>
    </div>
</div>
</section>

<style>
.gallery-filters{display:flex;justify-content:center;flex-wrap:wrap;gap:.5rem;margin-bottom:2.5rem}
.g-btn{background:var(--w);color:var(--tx);border:1px solid #e0e0e0;padding:.45rem 1.1rem;border-radius:30px;font-size:.82rem;font-weight:600;cursor:pointer;transition:all .25s;font-family:inherit}
.g-btn:hover,.g-btn.active{background:var(--pri);color:#fff;border-color:var(--pri);box-shadow:0 4px 12px rgba(105,108,255,.25)}
.gallery-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:1.8rem}
.g-card{background:var(--w);border-radius:14px;overflow:hidden;box-shadow:var(--sh);transition:all .3s ease;cursor:pointer;display:flex;flex-direction:column}
.g-card:hover{transform:translateY(-5px);box-shadow:var(--shl)}
.g-img-wrap{position:relative;width:100%;height:220px;overflow:hidden;background:#ebebeb}
.g-img{width:100%;height:100%;object-fit:cover;transition:transform .4s ease}
.g-card:hover .g-img{transform:scale(1.06)}
.g-cat-tag{position:absolute;top:.8rem;left:.8rem;background:rgba(35,35,51,.75);backdrop-filter:blur(4px);color:#fff;padding:.25rem .75rem;border-radius:6px;font-size:.7rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em}
.g-body{padding:1.25rem 1.4rem;display:flex;flex-direction:column;flex-grow:1}
.g-body h3{font-size:.98rem;font-weight:700;color:var(--dk);margin-bottom:.4rem;line-height:1.4}
.g-body p{font-size:.82rem;color:var(--tx);line-height:1.6;margin-bottom:0}
.g-zoom-btn{position:absolute;bottom:.8rem;right:.8rem;width:34px;height:34px;background:rgba(255,255,255,.9);color:var(--dk);border-radius:50%;display:flex;align-items:center;justify-content:center;opacity:0;transition:opacity .2s ease;font-size:1.1rem}
.g-card:hover .g-zoom-btn{opacity:1}

/* Modal Lightbox */
.g-modal{display:none;position:fixed;inset:0;z-index:9999;background:rgba(15,17,23,.88);backdrop-filter:blur(6px);align-items:center;justify-content:center;padding:1.5rem}
.g-modal.open{display:flex}
.g-modal-content{max-width:850px;width:100%;background:var(--w);border-radius:16px;overflow:hidden;box-shadow:0 20px 50px rgba(0,0,0,.5);position:relative;animation:mIn .25s ease-out}
@keyframes mIn{from{opacity:0;transform:scale(.95)}to{opacity:1;transform:scale(1)}}
.g-modal-img{width:100%;max-height:480px;object-fit:cover;display:block}
.g-modal-meta{padding:1.5rem}
.g-modal-close{position:absolute;top:1rem;right:1rem;width:36px;height:36px;background:rgba(0,0,0,.6);color:#fff;border:none;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:1.2rem;transition:background .2s;z-index:2}
.g-modal-close:hover{background:rgba(0,0,0,.9)}
@media(max-width:576px){.gallery-grid{grid-template-columns:1fr}.g-img-wrap{height:190px}.g-modal-img{max-height:300px}}
</style>

<section class="sec" id="galeri">
<div class="ct">
    <div class="sec-head fu">
        <div class="sec-eye"><i class='bx bx-images'></i> FASILITAS KAMI</div>
        <h2 class="sec-title">Galeri Industri & Fasilitas</h2>
        <p class="sec-sub">Melihat lebih dekat fasilitas canggih, alur manufaktur presisi, dan lingkungan kerja berstandar tinggi di PT Mingda International Footwear.</p>
    </div>

    <!-- Filter Buttons -->
    <div class="gallery-filters fu">
        <button class="g-btn active" data-filter="all">Semua</button>
        <button class="g-btn" data-filter="production">Lini Produksi</button>
        <button class="g-btn" data-filter="facility">Fasilitas Pabrik</button>
        <button class="g-btn" data-filter="quality_control">Quality Control</button>
        <button class="g-btn" data-filter="warehouse">Gudang & Logistik</button>
        <button class="g-btn" data-filter="office">Area Manajemen</button>
    </div>

    <!-- Gallery Items Grid -->
    <div class="gallery-grid" id="galleryContainer">
        @forelse($galleries ?? [] as $item)
            @php
                $catNames = [
                    'production'      => 'Lini Produksi',
                    'facility'        => 'Fasilitas Pabrik',
                    'quality_control' => 'Quality Control',
                    'warehouse'       => 'Gudang & Logistik',
                    'office'          => 'Area Manajemen'
                ];
                $categoryLabel = $catNames[$item->category] ?? ucfirst($item->category);
            @endphp
            <div class="g-card fu" data-category="{{ $item->category }}" data-img="{{ $item->image_url }}" data-title="{{ $item->title }}" data-category-label="{{ $categoryLabel }}" data-desc="{{ $item->description ?? '' }}">
                <div class="g-img-wrap">
                    <img src="{{ $item->image_url }}" alt="{{ $item->title }}" class="g-img" loading="lazy">
                    <span class="g-cat-tag">{{ $categoryLabel }}</span>
                    <span class="g-zoom-btn"><i class='bx bx-zoom-in'></i></span>
                </div>
                <div class="g-body">
                    <h3>{{ $item->title }}</h3>
                    @if($item->description)
                        <p>{{ $item->description }}</p>
                    @endif
                </div>
            </div>
        @empty
            <div style="grid-column:1/-1;text-align:center;padding:3rem 1rem;color:var(--txl);">
                <i class='bx bx-image' style="font-size:3rem;display:block;margin-bottom:.5rem;"></i>
                Belum ada foto galeri yang dipublikasikan.
            </div>
        @endforelse
    </div>
</div>
</section>

<!-- Lightbox Modal -->
<div class="g-modal" id="galleryModal" onclick="closeGalleryModalOnOutside(event)">
    <div class="g-modal-content">
        <button class="g-modal-close" onclick="closeGalleryModal()" aria-label="Tutup"><i class='bx bx-x'></i></button>
        <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg'/%3E" alt="" id="modalImg" class="g-modal-img">
        <div class="g-modal-meta">
            <span class="g-cat-tag" style="position:static;display:inline-block;margin-bottom:.5rem;" id="modalCategory"></span>
            <h3 style="font-size:1.15rem;font-weight:800;color:var(--dk);margin-bottom:.4rem;" id="modalTitle"></h3>
            <p style="font-size:.85rem;color:var(--tx);line-height:1.7;margin-bottom:0;" id="modalDesc"></p>
        </div>
    </div>
</div>

<style>
.portal-box{background:linear-gradient(135deg,var(--dk) 0%,#1a1a2e 100%);border-radius:20px;overflow:hidden;display:grid;grid-template-columns:1fr 1fr;min-height:380px}
.portal-info{padding:3rem;display:flex;flex-direction:column;justify-content:center}
.portal-info .sec-eye{color:var(--gold-l)}
.portal-info h2{font-size:1.7rem;font-weight:800;color:var(--w);margin-bottom:1rem;line-height:1.2}
.portal-info p{font-size:.88rem;color:rgba(255,255,255,.6);margin-bottom:1.5rem;line-height:1.8}
.portal-features{display:grid;grid-template-columns:1fr 1fr;gap:.8rem;margin-bottom:2rem}
.pf-item{display:flex;align-items:center;gap:.5rem;font-size:.78rem;color:rgba(255,255,255,.7)}
.pf-item i{color:var(--gold);font-size:1rem}
.portal-visual{background:linear-gradient(145deg,rgba(105,108,255,.12),rgba(200,168,78,.08));display:flex;align-items:center;justify-content:center;position:relative}
.portal-card{background:var(--w);border-radius:14px;padding:1.4rem;box-shadow:0 8px 30px rgba(0,0,0,.2);width:85%;max-width:320px}
.pc-head{display:flex;align-items:center;gap:.6rem;margin-bottom:1rem;padding-bottom:.8rem;border-bottom:1px solid #f0f0f0}
.pc-av{width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,var(--pri),#8385ff);display:flex;align-items:center;justify-content:center;color:#fff;font-size:1rem}
.pc-name{font-weight:600;color:var(--dk);font-size:.82rem}.pc-role{font-size:.68rem;color:var(--tx)}
.pc-row{display:flex;justify-content:space-between;align-items:center;padding:.45rem 0;font-size:.75rem}
.pc-row .pc-l{color:var(--tx);display:flex;align-items:center;gap:.3rem}
.pc-row .pc-v{font-weight:600;color:var(--dk)}
.pc-status{padding:.15rem .5rem;border-radius:5px;font-size:.68rem;font-weight:600;background:#e8fadf;color:#56ca00}
@media(max-width:768px){.portal-box{grid-template-columns:1fr}.portal-visual{min-height:280px;padding:2rem}}
</style>

<section class="sec sec-w" id="portal">
<div class="ct">
    <div class="portal-box fu">
        <div class="portal-info">
            <div class="sec-eye"><i class='bx bx-devices'></i> PORTAL KARYAWAN</div>
            <h2>Sistem Absensi Digital Terintegrasi</h2>
            <p>Kami menggunakan sistem absensi digital modern untuk memastikan pengelolaan kehadiran karyawan yang akurat, efisien, dan transparan.</p>
            <div class="portal-features">
                <div class="pf-item"><i class='bx bx-check'></i> Absensi Real-time</div>
                <div class="pf-item"><i class='bx bx-check'></i> GPS Tracking</div>
                <div class="pf-item"><i class='bx bx-check'></i> Manajemen Lembur</div>
                <div class="pf-item"><i class='bx bx-check'></i> Laporan Otomatis</div>
                <div class="pf-item"><i class='bx bx-check'></i> Jadwal & Shift</div>
                <div class="pf-item"><i class='bx bx-check'></i> Notifikasi Email</div>
            </div>
            <div>
                <a href="{{ route('login') }}" class="btn btn-pri btn-lg"><i class='bx bx-log-in'></i> Login ke Portal</a>
            </div>
        </div>
        <div class="portal-visual">
            <div class="portal-card">
                <div class="pc-head">
                    <div class="pc-av"><i class='bx bx-user'></i></div>
                    <div><div class="pc-name">Ahmad Suryadi</div><div class="pc-role">Operator — Dept. Sewing</div></div>
                </div>
                <div class="pc-row"><span class="pc-l"><i class='bx bx-calendar'></i> Tanggal</span><span class="pc-v">{{ now()->translatedFormat('d M Y') }}</span></div>
                <div class="pc-row"><span class="pc-l"><i class='bx bx-log-in-circle'></i> Masuk</span><span class="pc-v">07:58</span></div>
                <div class="pc-row"><span class="pc-l"><i class='bx bx-log-out-circle'></i> Pulang</span><span class="pc-v">17:02</span></div>
                <div class="pc-row"><span class="pc-l"><i class='bx bx-check-circle'></i> Status</span><span class="pc-status">✓ Hadir</span></div>
            </div>
        </div>
    </div>
</div>
</section>

<style>
.contact-grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:1.5rem}
.contact-card{background:var(--w);border-radius:12px;padding:2rem 1.5rem;box-shadow:var(--sh);text-align:center;transition:all .25s}
.contact-card:hover{transform:translateY(-3px);box-shadow:var(--shl)}
.contact-card .cc-icon{width:52px;height:52px;border-radius:50%;background:var(--pri-bg);color:var(--pri);display:inline-flex;align-items:center;justify-content:center;font-size:1.4rem;margin-bottom:1rem}
.contact-card h4{font-size:.88rem;font-weight:700;color:var(--dk);margin-bottom:.4rem}
.contact-card p{font-size:.8rem;line-height:1.7}
.contact-card a{color:var(--pri);font-weight:500}
@media(max-width:768px){.contact-grid{grid-template-columns:1fr}}

.footer{background:var(--dk);color:rgba(255,255,255,.55);padding:0}
.footer-main{padding:3rem 0;display:grid;grid-template-columns:1.5fr 1fr 1fr;gap:2rem}
.footer-brand{display:flex;align-items:center;gap:.7rem;margin-bottom:1rem}
.footer-brand img{height:36px;filter:brightness(0) invert(1);opacity:.7}
.footer-brand span{font-weight:700;font-size:.9rem;color:rgba(255,255,255,.85)}
.footer-desc{font-size:.78rem;line-height:1.8;max-width:300px}
.footer h5{font-size:.78rem;font-weight:700;color:rgba(255,255,255,.8);text-transform:uppercase;letter-spacing:.08em;margin-bottom:1rem}
.footer ul{list-style:none;padding:0}
.footer ul li{margin-bottom:.5rem}
.footer ul li a{font-size:.8rem;color:rgba(255,255,255,.55);transition:color .2s}
.footer ul li a:hover{color:var(--pri)}
.footer-bottom{border-top:1px solid rgba(255,255,255,.08);padding:1.2rem 0;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.5rem;font-size:.72rem}
@media(max-width:768px){.footer-main{grid-template-columns:1fr}.footer-bottom{justify-content:center;text-align:center}}
</style>

<section class="sec" id="kontak">
<div class="ct">
    <div class="sec-head fu">
        <div class="sec-eye"><i class='bx bx-envelope'></i> HUBUNGI KAMI</div>
        <h2 class="sec-title">Kontak Perusahaan</h2>
        </div>
    <div class="contact-grid">
        <div class="contact-card fu">
            <div class="cc-icon"><i class='bx bx-map'></i></div>
            <h4>Alamat</h4>
            <p>Kawasan Industri Losarang Blok C30 Indramayu<br>Jawa Barat, Indonesia</p>
        </div>
        <div class="contact-card fu">
            <div class="cc-icon"><i class='bx bx-phone'></i></div>
            <h4>Telepon</h4>
            <p><a href="tel:+62211234567">(021) 123-4567</a><br>Senin – Sabtu, 07:30 – 17:00 WIB</p>
        </div>
        <div class="contact-card fu">
            <div class="cc-icon"><i class='bx bx-envelope'></i></div>
            <h4>Email</h4>
            <p><a href="mailto:it@mingda.co.id">it@mingda.co.id</a></p>
        </div>
    </div>
</div>
</section>

<footer class="footer">
<div class="ct">
    <div class="footer-main">
        <div>
            <div class="footer-brand">
                <img src="{{ asset('sneat-1.0.0/assets/img/logo.png') }}" alt="Logo"/>
                <span>PT. Mingda International</span>
            </div>
            <p class="footer-desc">Perusahaan manufaktur alas kaki berkualitas internasional yang berkomitmen pada kualitas, inovasi, dan kesejahteraan karyawan.</p>
        </div>
        <div>
            <h5>Navigasi</h5>
            <ul>
                <li><a href="#tentang">Tentang Kami</a></li>
                <li><a href="#keunggulan">Keunggulan</a></li>
                <li><a href="#galeri">Galeri Fasilitas</a></li>
                <li><a href="#portal">Portal Karyawan</a></li>
                <li><a href="#kontak">Kontak</a></li>
            </ul>
        </div>
        <div>
            <h5>Portal</h5>
            <ul>
                <li><a href="{{ route('login') }}">Login Karyawan</a></li>
                <li><a href="{{ route('password.request') }}">Lupa Password</a></li>
            </ul>
        </div>
    </div>
    <div class="footer-bottom">
        <div>&copy; {{ date('Y') }} PT. Mingda International Footwear. All rights reserved.</div>
        <div>Sistem Absensi Karyawan v2.0</div>
    </div>
</div>
</footer>

<script>
(function(){
    const nav=document.getElementById('mainNav');
    const tog=document.getElementById('navToggle');
    const mid=document.getElementById('navMid');
    window.addEventListener('scroll',()=>nav.classList.toggle('solid',scrollY>50));
    tog.addEventListener('click',()=>mid.classList.toggle('open'));
    document.querySelectorAll('#navMid a[href^="#"]').forEach(a=>a.addEventListener('click',()=>mid.classList.remove('open')));
    const obs=new IntersectionObserver(e=>{e.forEach(x=>{if(x.isIntersecting)x.target.classList.add('vi')})},{threshold:.12});
    document.querySelectorAll('.fu').forEach(el=>obs.observe(el));
    document.querySelectorAll('a[href^="#"]').forEach(a=>{a.addEventListener('click',e=>{e.preventDefault();const t=document.querySelector(a.getAttribute('href'));if(t)t.scrollIntoView({behavior:'smooth',block:'start'})})});

    // Gallery Filter & Card Click
    const filterBtns = document.querySelectorAll('.g-btn');
    const cards = document.querySelectorAll('.g-card');
    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            filterBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            const cat = btn.getAttribute('data-filter');
            cards.forEach(card => {
                if (cat === 'all' || card.getAttribute('data-category') === cat) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });

    cards.forEach(card => {
        card.addEventListener('click', () => {
            openGalleryModal(
                card.getAttribute('data-img'),
                card.getAttribute('data-title'),
                card.getAttribute('data-category-label'),
                card.getAttribute('data-desc')
            );
        });
    });
})();

// Lightbox modal controls
function openGalleryModal(imgUrl, title, category, desc) {
    const modal = document.getElementById('galleryModal');
    document.getElementById('modalImg').src = imgUrl;
    document.getElementById('modalCategory').innerText = category;
    document.getElementById('modalTitle').innerText = title;
    const descEl = document.getElementById('modalDesc');
    if (desc && desc.trim()) {
        descEl.innerText = desc;
        descEl.style.display = 'block';
    } else {
        descEl.innerText = '';
        descEl.style.display = 'none';
    }
    modal.classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeGalleryModal() {
    const modal = document.getElementById('galleryModal');
    modal.classList.remove('open');
    document.body.style.overflow = '';
    document.getElementById('modalImg').src = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg'/%3E";
}

function closeGalleryModalOnOutside(e) {
    if (e.target.id === 'galleryModal') {
        closeGalleryModal();
    }
}

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeGalleryModal();
    }
});
</script>

</body>
</html>

