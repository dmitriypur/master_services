<!DOCTYPE html>
<html lang="ru">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Календарь</title>
    <style>
      body { font-family: -apple-system, system-ui, Segoe UI, Roboto; margin: 0; background: #ffffff; color: #111; }
      .wrap { padding: 16px; }
      .title { font-weight: 600; margin-bottom: 8px; }
      .hint { color: #666; font-size: 14px; }
      .btn { display: inline-block; margin-top: 12px; padding: 8px 12px; background: #000; color: #fff; border-radius: 6px; text-decoration: none; }
    </style>
  </head>
  <body>
    <div class="wrap">
      <div class="title">Календарь мастера</div>
      <div class="hint">{{ $user?->name ?? 'Мастер' }}</div>
      <button id="openFull" class="btn">Открыть полную версию</button>
    </div>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <script>
      (function(){
        try {
          if (window.Telegram && window.Telegram.WebApp) {
            window.Telegram.WebApp.ready();
            try { window.Telegram.WebApp.expand(); } catch(e) {}
          }
        } catch(e) {}
        try { navigator.sendBeacon('/debug/webapp-event', new Blob([JSON.stringify({stage:'calendar-loaded'})], {type:'application/json'})); } catch(e) {}
        var btn = document.getElementById('openFull');
        var url = "{{ url('/master/calendar') }}";
        btn.addEventListener('click', function(){
          try {
            if (window.Telegram && window.Telegram.WebApp && typeof window.Telegram.WebApp.openLink === 'function') {
              window.Telegram.WebApp.openLink(url);
            } else {
              window.location.href = url;
            }
          } catch (e) {
            window.location.href = url;
          }
        });
      })();
    </script>
  </body>
</html>