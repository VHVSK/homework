/* Network-only navigation: no homework, account page or credential is stored in Cache Storage. */
'use strict';
const offlinePage = '<!doctype html><html lang="uk"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Немає з’єднання · Після дзвоника</title><style>body{margin:0;background:#f5f8f6;color:#163d31;font:16px/1.7 system-ui,sans-serif;display:grid;place-items:center;min-height:100vh}main{max-width:440px;padding:32px}h1{font-size:28px;line-height:1.25}a{display:inline-block;margin-top:12px;padding:10px 18px;border-radius:8px;background:#163d31;color:#fff;text-decoration:none}</style><main><p>Після дзвоника</p><h1>Потрібне з’єднання з інтернетом</h1><p>Щоб показати актуальні завдання й захистити дані кабінету, сайт завантажує сторінки з мережі. Перевірте з’єднання та спробуйте ще раз.</p><a href="./index.php">Повторити</a></main></html>';
self.addEventListener('fetch', (event) => {
  if (event.request.method !== 'GET' || event.request.mode !== 'navigate' || new URL(event.request.url).origin !== self.location.origin) return;
  event.respondWith(fetch(event.request).catch(() => new Response(offlinePage, {
    status: 503,
    headers: {
      'Content-Type': 'text/html; charset=utf-8',
      'Cache-Control': 'no-store',
      'Content-Security-Policy': "default-src 'none'; style-src 'unsafe-inline'; base-uri 'none'; frame-ancestors 'none'",
    },
  })));
});
