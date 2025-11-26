<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>WebApp Auth</title>
  </head>
  <body>
    <div id="status" style="font-family: system-ui, -apple-system, Segoe UI, Roboto; padding:16px;">
      Вход через Telegram WebApp
    </div>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <script>
    (function(){
      const status = document.getElementById('status');
      function setStatus(t){ status.textContent = t; }
      function sendStage(stage){
        try { navigator.sendBeacon('/debug/webapp-event', new Blob([JSON.stringify({stage})], {type:'application/json'})); } catch(e) {}
      }
      try {
        setStatus('Страница загружена');
        sendStage('loaded');
        let initData = (window && window.Telegram && window.Telegram.WebApp && window.Telegram.WebApp.initData) || '';
        if (!initData) {
          const q = new URLSearchParams(window.location.search);
          const fromQuery = q.get('initData');
          if (fromQuery) {
            initData = fromQuery;
            setStatus('initData (mock) ok');
            sendStage('init-mock');
          }
        }
        if (!initData) { setStatus('Нет initData'); sendStage('no-init'); return; }
        setStatus('initData ok (' + initData.length + ')');
        sendStage('init-ok');
        const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content') || '';
        fetch('/auth/telegram/webapp', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf,
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: JSON.stringify({ initData }),
          credentials: 'same-origin'
        }).then(async (res) => {
          if (!res.ok) {
            const data = await res.json().catch(() => ({}));
            setStatus((data && data.message) ? data.message : ('Ошибка авторизации ('+res.status+')'));
            sendStage('auth-error-'+res.status);
            return;
          }
          const data = await res.json().catch(() => ({}));
          setStatus('Успешно');
          sendStage('auth-ok');
          var dest = (data && data.redirect) ? data.redirect : '/master/calendar';
          window.location.href = dest;
        }).catch(() => setStatus('Ошибка запроса'));
      } catch (e) {
        setStatus('Ошибка инициализации');
        sendStage('init-exception');
      }
    })();
    </script>
  </body>
</html>