<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('code') — CreativeHub</title>
    <style>
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #0a0d1f; color: #e6eaf4; font-family: 'Plus Jakarta Sans', system-ui, sans-serif; padding: 24px; box-sizing: border-box; }
        .box { max-width: 460px; text-align: center; }
        .code { font-size: 72px; font-weight: 800; letter-spacing: -2px; background: linear-gradient(90deg, #a78bfa, #5b8cff); -webkit-background-clip: text; background-clip: text; color: transparent; margin: 0; }
        h1 { font-size: 22px; margin: 8px 0; color: #fff; }
        p { color: #9ba8cc; line-height: 1.6; }
        a { display: inline-block; margin-top: 16px; padding: 10px 18px; border-radius: 10px; background: #7c3aed; color: #fff; text-decoration: none; font-weight: 600; }
        a.secondary { background: transparent; border: 1px solid #36406b; margin-left: 8px; }
    </style>
</head>
<body>
    <main class="box">
        <p class="code">@yield('code')</p>
        <h1>@yield('title')</h1>
        <p>@yield('message')</p>
        <a href="{{ url('/') }}">Ke Beranda</a>
        <a href="{{ url()->previous() }}" class="secondary">Kembali</a>
    </main>
</body>
</html>
