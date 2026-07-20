import 'leaflet/dist/leaflet.css';
import L from 'leaflet';
import markerIconUrl from 'leaflet/dist/images/marker-icon.png';
import markerIcon2xUrl from 'leaflet/dist/images/marker-icon-2x.png';
import markerShadowUrl from 'leaflet/dist/images/marker-shadow.png';

L.Icon.Default.mergeOptions({
    iconUrl: markerIconUrl,
    iconRetinaUrl: markerIcon2xUrl,
    shadowUrl: markerShadowUrl,
});

const container = document.getElementById('map-lokasi');
if (container) {
    const latInput = document.getElementById('lokasi_lat');
    const lngInput = document.getElementById('lokasi_lng');
    const radiusInput = document.getElementById('lokasi_radius_meter');

    const datasetLat = parseFloat(container.dataset.lat);
    const datasetLng = parseFloat(container.dataset.lng);
    const hasTitik = !Number.isNaN(datasetLat) && !Number.isNaN(datasetLng);

    const startLat = hasTitik ? datasetLat : -2.5;
    const startLng = hasTitik ? datasetLng : 118;
    const startRadius = parseInt(container.dataset.radius, 10) || 100;

    const map = L.map(container).setView([startLat, startLng], hasTitik ? 17 : 5);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        maxZoom: 19,
    }).addTo(map);

    const marker = L.marker([startLat, startLng], { draggable: true, opacity: hasTitik ? 1 : 0 }).addTo(map);
    const circle = L.circle([startLat, startLng], {
        radius: startRadius,
        color: '#4f46e5',
        fillColor: '#4f46e5',
        fillOpacity: 0.15,
    }).addTo(map);

    function taruhTitik(lat, lng) {
        marker.setLatLng([lat, lng]).setOpacity(1);
        circle.setLatLng([lat, lng]);
        if (latInput) latInput.value = lat.toFixed(7);
        if (lngInput) lngInput.value = lng.toFixed(7);
    }

    map.on('click', (e) => taruhTitik(e.latlng.lat, e.latlng.lng));
    marker.on('drag', (e) => {
        const { lat, lng } = e.target.getLatLng();
        circle.setLatLng([lat, lng]);
        if (latInput) latInput.value = lat.toFixed(7);
        if (lngInput) lngInput.value = lng.toFixed(7);
    });

    radiusInput?.addEventListener('input', () => {
        const meter = parseInt(radiusInput.value, 10);
        if (!Number.isNaN(meter) && meter > 0) circle.setRadius(meter);
    });

    // "Gunakan Lokasi Saat Ini" mengisi input lat/lng lewat script inline di
    // blade (geolocation browser) - dengarkan perubahan input itu juga, supaya
    // marker & circle ikut pindah tanpa perlu klik map lagi.
    [latInput, lngInput].forEach((input) => {
        input?.addEventListener('change', () => {
            const lat = parseFloat(latInput?.value);
            const lng = parseFloat(lngInput?.value);
            if (!Number.isNaN(lat) && !Number.isNaN(lng)) {
                taruhTitik(lat, lng);
                map.setView([lat, lng], 17);
            }
        });
    });

    // Perbaiki ukuran tile map yang salah dihitung karena container disembunyikan
    // (mis. tab tidak aktif) saat Leaflet pertama kali init.
    setTimeout(() => map.invalidateSize(), 200);

    document.getElementById('btn-lokasi-sekarang')?.addEventListener('click', () => {
        if (!navigator.geolocation) {
            alert('Browser tidak mendukung lokasi GPS.');
            return;
        }
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                taruhTitik(pos.coords.latitude, pos.coords.longitude);
                map.setView([pos.coords.latitude, pos.coords.longitude], 17);
            },
            () => alert('Gagal mengambil lokasi. Pastikan izin lokasi diaktifkan.'),
            { enableHighAccuracy: true, timeout: 15000 }
        );
    });
}
