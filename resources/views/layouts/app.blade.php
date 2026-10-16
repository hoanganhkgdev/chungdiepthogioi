<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }} - {{ config('app.name') }}</title>
    <link rel="shortcut icon" href="/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Noto+Serif:wght@700;800&subset=vietnamese&display=swap">
    <style>
        :root { --maroon:#8f1d1d; --maroon-dark:#6e1313; --gold:#c9972b; --gold-soft:#fbf3e0; --ink:#2b2b2b; --line:#eadfce; }
        body { background:#faf7f2; color:var(--ink); font-family:'Inter',system-ui,-apple-system,'Segoe UI',sans-serif; font-size:15px; line-height:1.6; }
        a { color:var(--maroon); } a:hover { color:var(--gold); text-decoration:none; }
        h1 { font-size:26px; font-weight:700; color:var(--maroon); margin:0 0 6px; }
        h1 + hr { width:70px; border-top:3px solid var(--gold); margin:10px 0 22px; }
        .site-header { background:linear-gradient(135deg,var(--maroon-dark),var(--maroon)); color:#fff; padding:16px 0; border-bottom:4px solid var(--gold); }
        .site-header .brand { display:flex; align-items:center; gap:18px; }
        .site-header img { max-height:72px; background:#fff; border-radius:50%; padding:4px; }
        .site-header .site-name { font-family:'Noto Serif',Georgia,serif; font-size:32px; font-weight:800; margin:0; text-transform:uppercase; letter-spacing:2px; line-height:1.2; background:linear-gradient(180deg,#fff3c4 0%,#f0c35a 55%,#c9972b 100%); -webkit-background-clip:text; background-clip:text; color:transparent; -webkit-text-fill-color:transparent; filter:drop-shadow(0 2px 2px rgba(0,0,0,.35)); }
        .site-header .site-sub { display:flex; align-items:center; gap:12px; margin:8px 0 0; font-family:'Noto Serif',Georgia,serif; font-size:14px; font-weight:700; letter-spacing:4px; text-transform:uppercase; color:#f6dc9a; text-shadow:0 1px 2px rgba(0,0,0,.4); }
        .site-header .site-sub::before, .site-header .site-sub::after { content:''; height:1px; width:36px; background:linear-gradient(90deg,transparent,#e9c46a); }
        .site-header .site-sub::after { background:linear-gradient(90deg,#e9c46a,transparent); }
        .site-nav { background:#fff; box-shadow:0 2px 8px rgba(0,0,0,.08); }
        .site-nav a { color:var(--maroon); display:inline-block; padding:13px 20px; font-weight:600; border-bottom:3px solid transparent; }
        .site-nav a:hover, .site-nav a.active { color:var(--maroon); background:var(--gold-soft); border-bottom-color:var(--gold); }
        .panel { border:1px solid var(--line); border-radius:10px; box-shadow:0 2px 10px rgba(80,50,10,.06); overflow:hidden; }
        .panel-default > .panel-heading { background:var(--gold-soft); color:var(--maroon); border-bottom:1px solid var(--line); font-weight:700; text-transform:uppercase; font-size:13px; letter-spacing:.4px; padding:12px 16px; }
        .panel-body { padding:16px; }
        .form-control { border-radius:6px; height:38px; box-shadow:none; border-color:#d9cdb9; }
        .form-control:focus { border-color:var(--gold); box-shadow:0 0 0 3px rgba(201,151,43,.2); }
        .btn-primary { background:var(--maroon); border-color:var(--maroon-dark); border-radius:6px; padding:8px 26px; font-weight:600; }
        .btn-primary:hover, .btn-primary:focus { background:var(--gold); border-color:var(--gold); }
        .stat-row { display:flex; justify-content:space-between; padding:7px 0; border-bottom:1px dashed var(--line); }
        .stat-row b { color:var(--maroon); }
        .stat-total { display:flex; justify-content:space-between; align-items:center; margin-top:12px; padding:10px 14px; background:var(--maroon); color:#fff; border-radius:8px; font-weight:600; }
        .stat-total b { font-size:20px; color:#ffe39a; }
        .list-group-item { border-color:var(--line); padding:10px 16px; }
        .list-group-item:hover { background:var(--gold-soft); color:var(--maroon); padding-left:20px; transition:.15s; }
        .table-responsive { background:#fff; border:1px solid var(--line); border-radius:10px; box-shadow:0 2px 10px rgba(80,50,10,.06); }
        .table { margin:0; }
        .table-bordered, .table-bordered > thead > tr > th, .table-bordered > tbody > tr > td { border-color:var(--line); }
        .table > thead > tr > th { background:var(--maroon); color:#fff; border-bottom:0; padding:12px; white-space:nowrap; }
        .table-striped > tbody > tr:nth-of-type(odd) { background:#fffcf6; }
        .table-hover > tbody > tr:hover { background:var(--gold-soft); }
        .pagination > li > a, .pagination > li > span { color:var(--maroon); border-color:var(--line); margin:0 2px; border-radius:6px; }
        .pagination > .active > span, .pagination > .active > a { background:var(--maroon); border-color:var(--maroon); }
        .site-footer { background:var(--maroon-dark); color:#e8d9bd; padding:30px 0; margin-top:40px; border-top:4px solid var(--gold); font-size:14px; }
        .site-footer .ft-title { font-size:17px; font-weight:700; color:#ffe39a; display:block; margin-bottom:8px; }
        .site-footer strong { color:#fff; }
        .part-card { display:block; height:100%; background:#fff; border:1px solid var(--line); border-radius:12px; padding:20px 18px 16px; text-align:center; box-shadow:0 2px 10px rgba(80,50,10,.06); border-top:4px solid var(--gold); transition:.2s; color:var(--ink); }
        .part-card:hover { transform:translateY(-4px); box-shadow:0 10px 24px rgba(110,19,19,.15); color:var(--ink); border-top-color:var(--maroon); }
        .part-card .icon { width:56px; height:56px; line-height:56px; margin:0 auto 12px; border-radius:50%; background:var(--gold-soft); color:var(--maroon); font-size:24px; }
        .part-card h3 { font-size:16px; font-weight:700; color:var(--maroon); margin:0 0 6px; min-height:40px; }
        .part-card .meta { font-size:13px; color:#777; margin:3px 0; }
        .part-card .count { display:inline-block; margin-top:10px; padding:4px 14px; border-radius:20px; background:var(--maroon); color:#ffe39a; font-weight:600; font-size:13px; }
        .part-grid > [class*='col-'] { margin-bottom:24px; }
        @media (max-width:767px) { .site-header .site-name { font-size:20px; letter-spacing:1px; } .site-header .site-sub { font-size:11px; letter-spacing:2px; } .site-header .site-sub::before { display:none; } .site-header img { max-height:52px; } h1 { font-size:21px; } }
    </style>
    @stack('head')
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
</head>
<body>
@unless($bare ?? false)
    <header class="site-header">
        <div class="container">
            <a href="/" class="brand">
                <img src="/uploads/logo-ghpgvn.jpg" alt="{{ config('app.name') }}">
                <div><p class="site-name">{{ config('app.name') }}</p><p class="site-sub">Giáo hội Phật giáo Việt Nam</p></div>
            </a>
        </div>
    </header>
    <div class="container" style="margin-top:20px">
        <div class="row">
            <div class="col-md-12">@yield('content')</div>
        </div>
    </div>
    <footer class="site-footer">
        <div class="container"><div class="row">
            <div class="col-md-6">
                <span class="ft-title">CHỨNG ĐIỆP THỌ GIỚI</span>
                <span>●&nbsp;<strong>Đơn vị:</strong>&nbsp;Cổng thông tin văn phòng T.Ư Giáo hội<br>●&nbsp;<strong>Trụ sở:&nbsp;</strong>số 294 Nam Kỳ Khởi Nghĩa, Q.3, TP.HCM</span>
            </div>
            <div class="col-md-6">
                <span class="ft-title">VĂN PHÒNG LIÊN LẠC</span>
                <span>●&nbsp;<strong>Chùa Minh Đạo:&nbsp;</strong>12/3 BIS Kỳ Đồng, P9, Q.3, Tp.HCM<br>●&nbsp;<strong>Chùa Phật Quang:&nbsp;</strong>83 Quang Trung, P.Vĩnh Quang, RG, KG</span>
            </div>
        </div></div>
    </footer>
@else
    @yield('content')
@endunless
<script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.4.1/js/bootstrap.min.js"></script>
@stack('scripts')
</body>
</html>
