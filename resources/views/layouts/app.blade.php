<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#123d32">
    <meta name="user-id" content="{{ auth()->id() }}">
    <meta name="reverb-key" content="{{ config('reverb.apps.apps.0.key') }}">
    <meta name="reverb-host" content="{{ config('reverb.apps.apps.0.options.host') }}">
    <meta name="reverb-port" content="{{ config('reverb.apps.apps.0.options.port') }}">
    <meta name="reverb-scheme" content="{{ config('reverb.apps.apps.0.options.scheme') }}">
    <title>@yield('title', 'Minimarket') · Minimarket</title>
    @vite('resources/js/app.js')
    <style>
        :root {
            --ink: #1d2d27;
            --muted: #77847d;
            --line: #e6ebe6;
            --paper: #f5f7f3;
            --white: #fff;
            --green: #195b48;
            --green-dark: #123d32;
            --lime: #d5e77d;
            --red: #a93f35;
            --amber: #a66a16;
            --shadow: 0 12px 38px rgb(26 49 39 / 6%);
        }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--paper); color: var(--ink); font-family: "Segoe UI", Arial, sans-serif; font-size: 14px; }
        a { color: inherit; }
        button, input, select, textarea { font: inherit; }
        .app-shell { min-height: 100vh; display: grid; grid-template-columns: 248px minmax(0, 1fr); }
        .sidebar { position: sticky; top: 0; height: 100vh; display: flex; flex-direction: column; padding: 26px 18px; background: var(--green-dark); color: #eef4ef; }
        .brand { display: flex; align-items: center; gap: 12px; padding: 0 10px 34px; font-size: 13px; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; }
        .brand-icon { width: 34px; height: 34px; display: grid; place-items: center; border: 1px solid rgb(213 231 125 / 65%); border-radius: 9px; color: var(--lime); font-family: Georgia, serif; font-size: 19px; }
        .nav-label { margin: 0 10px 10px; color: #9cb5a9; font-size: 10px; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; }
        .nav-list { display: grid; gap: 5px; }
        .nav-link { display: flex; align-items: center; gap: 12px; padding: 11px 12px; border-radius: 9px; color: #c6d5cc; text-decoration: none; transition: background .15s, color .15s; }
        .nav-link:hover, .nav-link.active { background: rgb(255 255 255 / 10%); color: #fff; }
        .nav-icon { width: 20px; text-align: center; color: var(--lime); font-size: 16px; }
        .sidebar-bottom { margin-top: auto; padding: 16px 10px 2px; border-top: 1px solid rgb(255 255 255 / 12%); }
        .identity { display: flex; align-items: center; gap: 10px; margin-bottom: 15px; }
        .avatar { width: 36px; height: 36px; display: grid; flex: 0 0 auto; place-items: center; border-radius: 50%; background: #dce9d9; color: var(--green-dark); font-weight: 800; }
        .identity-name { overflow: hidden; color: #fff; font-size: 12px; font-weight: 700; text-overflow: ellipsis; white-space: nowrap; }
        .identity-role { margin-top: 3px; color: #a9beb2; font-size: 11px; text-transform: capitalize; }
        .logout { width: 100%; padding: 9px 10px; border: 1px solid rgb(255 255 255 / 18%); border-radius: 8px; background: transparent; color: #e2ebe5; cursor: pointer; text-align: left; }
        .logout:hover { background: rgb(255 255 255 / 8%); }
        .workspace { min-width: 0; }
        .topbar { min-height: 76px; display: flex; justify-content: space-between; align-items: center; gap: 18px; padding: 16px clamp(20px, 4vw, 48px); border-bottom: 1px solid var(--line); background: rgb(255 255 255 / 84%); }
        .topbar-label { color: var(--muted); font-size: 12px; }
        .topbar-date { color: var(--ink); font-size: 13px; font-weight: 600; }
        .main-content { width: min(100%, 1440px); margin: 0 auto; padding: 34px clamp(20px, 4vw, 48px) 56px; }
        .page-heading { display: flex; justify-content: space-between; align-items: flex-end; gap: 20px; margin-bottom: 26px; }
        .eyebrow { margin: 0 0 8px; color: var(--green); font-size: 10px; font-weight: 800; letter-spacing: .14em; text-transform: uppercase; }
        h1, h2, h3, p { margin-top: 0; }
        h1, h2 { font-family: Georgia, "Times New Roman", serif; font-weight: 400; letter-spacing: -.025em; }
        h1 { margin-bottom: 8px; font-size: clamp(28px, 3vw, 38px); }
        h2 { margin-bottom: 0; font-size: 24px; }
        h3 { margin-bottom: 8px; font-size: 15px; }
        .subheading { margin: 0; color: var(--muted); line-height: 1.6; }
        .card { min-width: 0; padding: 22px; border: 1px solid var(--line); border-radius: 14px; background: var(--white); box-shadow: var(--shadow); }
        .card-heading { display: flex; justify-content: space-between; align-items: center; gap: 14px; margin-bottom: 18px; }
        .card-heading p { margin: 5px 0 0; color: var(--muted); font-size: 12px; }
        .stats-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; margin-bottom: 20px; }
        .stat-card { padding: 19px; border: 1px solid var(--line); border-radius: 13px; background: #fff; box-shadow: var(--shadow); }
        .stat-top { display: flex; justify-content: space-between; align-items: center; color: var(--muted); font-size: 12px; }
        .stat-icon { width: 34px; height: 34px; display: grid; place-items: center; border-radius: 10px; background: #edf5ed; color: var(--green); font-size: 17px; }
        .stat-value { margin: 17px 0 3px; font-family: Georgia, "Times New Roman", serif; font-size: clamp(22px, 2.5vw, 30px); }
        .stat-note { color: var(--muted); font-size: 11px; }
        .content-grid { display: grid; grid-template-columns: minmax(0, 1.4fr) minmax(280px, .8fr); gap: 18px; }
        .stack { display: grid; gap: 18px; }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { padding: 11px 12px; border-bottom: 1px solid var(--line); color: #89958e; font-size: 10px; font-weight: 700; letter-spacing: .09em; text-transform: uppercase; white-space: nowrap; }
        td { padding: 14px 12px; border-bottom: 1px solid #eff2ee; vertical-align: middle; }
        tbody tr:last-child td { border-bottom: 0; }
        .cell-title { color: var(--ink); font-weight: 650; }
        .cell-muted { margin-top: 4px; color: var(--muted); font-size: 11px; }
        .money { color: var(--ink); font-weight: 700; white-space: nowrap; }
        .badge { display: inline-flex; align-items: center; padding: 5px 9px; border-radius: 99px; background: #edf6ef; color: #2a674c; font-size: 11px; font-weight: 700; white-space: nowrap; }
        .badge.warning { background: #fff4df; color: var(--amber); }
        .badge.danger { background: #fff0ed; color: var(--red); }
        .button { display: inline-flex; align-items: center; justify-content: center; gap: 8px; min-height: 40px; padding: 0 15px; border: 1px solid transparent; border-radius: 8px; background: var(--green); color: #fff; cursor: pointer; font-size: 12px; font-weight: 700; text-decoration: none; transition: background .15s, transform .15s; }
        .button:hover { transform: translateY(-1px); background: #144936; }
        .button.secondary { border-color: var(--line); background: #fff; color: var(--ink); }
        .button.secondary:hover { background: #f7f9f6; }
        .button.danger { background: #fff2f0; color: var(--red); }
        .button.small { min-height: 32px; padding: 0 10px; font-size: 11px; }
        .form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
        .form-field { display: grid; gap: 7px; }
        .form-field.full { grid-column: 1 / -1; }
        .form-field label { color: #45544c; font-size: 12px; font-weight: 700; }
        .input, .form-field input, .form-field textarea, .form-field select { width: 100%; min-height: 43px; padding: 10px 12px; border: 1px solid #dce4dd; border-radius: 8px; background: #fff; color: var(--ink); outline: 0; }
        .input:focus, .form-field input:focus, .form-field textarea:focus, .form-field select:focus { border-color: #6c9b83; box-shadow: 0 0 0 3px rgb(25 91 72 / 9%); }
        .input-error { color: var(--red); font-size: 11px; }
        .alert { margin-bottom: 18px; padding: 13px 15px; border: 1px solid #cbe1cf; border-radius: 9px; background: #f1f9f1; color: #2c6948; font-size: 13px; }
        .alert.error { border-color: #efc8c1; background: #fff5f3; color: var(--red); }
        .realtime-notification { position: fixed; z-index: 1000; top: 20px; right: 20px; display: flex; align-items: flex-start; gap: 14px; width: min(390px, calc(100vw - 40px)); padding: 15px 16px; border: 1px solid #cbe1cf; border-radius: 10px; background: #fff; box-shadow: 0 12px 38px rgb(26 49 39 / 16%); color: var(--ink); }
        .realtime-notification[hidden] { display: none; }
        .realtime-notification-message { margin: 0; font-size: 13px; line-height: 1.5; }
        .realtime-notification-dismiss { flex: 0 0 auto; border: 0; background: transparent; color: var(--muted); cursor: pointer; font-size: 18px; line-height: 1; }
        .empty-state { padding: 34px 16px; color: var(--muted); text-align: center; }
        .pagination { margin-top: 18px; }
        .pagination nav { display: flex; justify-content: space-between; align-items: center; gap: 15px; }
        .pagination p { margin: 0; color: var(--muted); font-size: 12px; }
        .pagination a, .pagination span[aria-current] { display: inline-block; margin-left: 5px; padding: 7px 10px; border: 1px solid var(--line); border-radius: 7px; text-decoration: none; }
        @media (max-width: 980px) { .stats-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } .content-grid { grid-template-columns: 1fr; } }
        @media (max-width: 680px) {
            .app-shell { grid-template-columns: 1fr; }
            .sidebar { position: static; height: auto; padding: 14px 16px; }
            .brand { padding: 0 2px 13px; }
            .nav-label, .sidebar-bottom { display: none; }
            .nav-list { display: flex; overflow-x: auto; }
            .nav-link { flex: 0 0 auto; padding: 9px 11px; font-size: 12px; }
            .topbar { min-height: 54px; padding: 10px 18px; }
            .main-content { padding: 25px 16px 40px; }
            .page-heading { align-items: flex-start; flex-direction: column; }
            .card { padding: 16px; }
            .stats-grid { gap: 9px; }
            .stat-card { padding: 14px; }
            .stat-value { margin-top: 13px; }
            .form-grid { grid-template-columns: 1fr; }
            .form-field.full { grid-column: auto; }
        }
        @media print {
            .sidebar, .topbar, .no-print { display: none !important; }
            .app-shell { display: block; }
            .main-content { width: 100%; padding: 0; }
            body { background: #fff; }
            .card { box-shadow: none; }
        }
    </style>
    @stack('styles')
</head>
<body>
<div class="realtime-notification" data-realtime-notification role="status" aria-live="polite" hidden>
    <p class="realtime-notification-message" data-notification-message></p>
    <button class="realtime-notification-dismiss" type="button" data-notification-dismiss aria-label="Tutup notifikasi">&times;</button>
</div>
<div class="app-shell">
    <aside class="sidebar">
        <a class="brand" href="{{ auth()->user()->role === 'admin' ? route('admin') : route('kasir') }}" style="text-decoration:none">
            <span class="brand-icon">M</span><span>Minimarket</span>
        </a>
        <p class="nav-label">Ruang kerja</p>
        <nav class="nav-list" aria-label="Navigasi utama">
            @if(auth()->user()->role === 'admin')
                <a class="nav-link {{ request()->routeIs('admin') ? 'active' : '' }}" href="{{ route('admin') }}"><span class="nav-icon">⌂</span> Ringkasan</a>
                <a class="nav-link {{ request()->routeIs('products.*') ? 'active' : '' }}" href="{{ route('products.index') }}"><span class="nav-icon">▤</span> Inventaris</a>
                <a class="nav-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}" href="{{ route('admin.categories.index') }}"><span class="nav-icon">▦</span> Kategori</a>
                <a class="nav-link {{ request()->routeIs('admin.suppliers.*') ? 'active' : '' }}" href="{{ route('admin.suppliers.index') }}"><span class="nav-icon">♧</span> Supplier</a>
                <a class="nav-link {{ request()->routeIs('admin.stock*') ? 'active' : '' }}" href="{{ route('admin.stock') }}"><span class="nav-icon">⇧</span> Stok masuk</a>
                <a class="nav-link {{ request()->routeIs('admin.transactions') ? 'active' : '' }}" href="{{ route('admin.transactions') }}"><span class="nav-icon">◷</span> Transaksi</a>
                <a class="nav-link {{ request()->routeIs('admin.reports*') ? 'active' : '' }}" href="{{ route('admin.reports') }}"><span class="nav-icon">▥</span> Laporan</a>
                <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}"><span class="nav-icon">♙</span> Pengguna</a>
            @else
                <a class="nav-link {{ request()->routeIs('kasir') ? 'active' : '' }}" href="{{ route('kasir') }}"><span class="nav-icon">▣</span> Kasir</a>
                <a class="nav-link {{ request()->routeIs('kasir.transactions') ? 'active' : '' }}" href="{{ route('kasir.transactions') }}"><span class="nav-icon">◷</span> Riwayat saya</a>
            @endif
        </nav>
        <div class="sidebar-bottom">
            <div class="identity">
                <span class="avatar">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                <div style="min-width:0">
                    <div class="identity-name">{{ auth()->user()->name }}</div>
                    <div class="identity-role">{{ auth()->user()->role }}</div>
                </div>
            </div>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button class="logout" type="submit">Keluar dari akun</button>
            </form>
        </div>
    </aside>
    <div class="workspace">
        <header class="topbar">
            <span class="topbar-label">@yield('section', 'Operasional toko')</span>
            <span class="topbar-date">{{ now()->locale('id')->translatedFormat('l, d F Y') }}</span>
        </header>
        <main class="main-content">
            @if(session('success')) <div class="alert" role="status">{{ session('success') }}</div> @endif
            @if($errors->any())
                <div class="alert error" role="alert">
                    @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
                </div>
            @endif
            @yield('content')
        </main>
    </div>
</div>

@auth
<script>
    window.Echo.private('user.{{ auth()->id() }}')
        .listen('.OrderPlaced', (e) => {

            alert(
                'Order #' + e.order_id +
                ' telah ditempatkan! Total: Rp' + e.total
            );

            console.log('Order Placed:', e);
        });
</script>
@endauth
@stack('scripts')
</body>
</html>
