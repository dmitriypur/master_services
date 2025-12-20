<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="manifest" href="/manifest.json">
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <style>
      #app-loading {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: #fff;
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 9999;
        transition: opacity 0.3s ease;
      }
      .spinner {
        width: 40px;
        height: 40px;
        border: 4px solid #f3f3f3;
        border-top: 4px solid #3498db;
        border-radius: 50%;
        animation: spin 1s linear infinite;
      }
      @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
      }
    </style>
    @vite(['resources/js/app.js', 'resources/css/app.css'])
    @inertiaHead
  </head>
  <body class="font-sans antialiased">
    <div id="app-loading">
      <div class="spinner"></div>
    </div>
    @inertia
    <script>
       // Скрываем лоадер когда инерция (или просто страница) загрузилась
       // Inertia удаляет содержимое body при маунте, так что этот div пропадет сам,
       // но если он вне @inertia (как сейчас, он рядом), нам нужно его убрать.
       // На самом деле @inertia рендерит <div id="app" data-page="..."></div>
       // Поэтому наш app-loading будет соседом.
       // Мы можем слушать событие инерции или просто в app.js убрать его.
       
       // Простой фоллбэк:
       window.addEventListener('load', () => {
           // Ждем чуть-чуть инициализации Vue
           setTimeout(() => {
               const loader = document.getElementById('app-loading');
               if (loader) {
                   loader.style.opacity = '0';
                   setTimeout(() => loader.remove(), 300);
               }
           }, 500);
       });
    </script>
  </body>
</html>