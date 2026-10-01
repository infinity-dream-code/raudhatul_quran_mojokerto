<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sedang memulihkan…</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #f5f7f6; color: #1f2937; display: flex; min-height: 100vh; align-items: center; justify-content: center; margin: 0; }
        .box { text-align: center; padding: 24px; }
        .muted { color: #6b7280; font-size: 14px; }
    </style>
</head>
<body>
<div class="box">
    <p id="msg">Sedang memulihkan halaman…</p>
    <p class="muted" id="sub">Mohon tunggu sebentar.</p>
</div>
<script>
(function () {
    var key = 'transient_500_reloaded:' + location.pathname + location.search;
    try {
        if (!sessionStorage.getItem(key)) {
            sessionStorage.setItem(key, '1');
            location.reload();
            return;
        }
        sessionStorage.removeItem(key);
    } catch (e) {}
    document.getElementById('msg').textContent = 'Terjadi gangguan sementara.';
    document.getElementById('sub').textContent = 'Silakan muat ulang halaman, atau coba lagi beberapa saat.';
})();
</script>
</body>
</html>
