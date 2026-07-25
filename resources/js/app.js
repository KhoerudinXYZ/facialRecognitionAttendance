import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {
            // Diam saja - gagal di sini cuma berarti "Add to Home Screen" tidak
            // muncul otomatis (mis. diakses lewat HTTP biasa, bukan HTTPS/localhost).
        });
    });
}
