const mapElement = document.getElementById('group-map');

if (mapElement) {
    if (!window.L) {
        mapElement.textContent = 'Peta tidak dapat dimuat. Periksa koneksi internet lalu muat ulang halaman.';
    } else {
        const map = window.L.map(mapElement).setView([-1.4300, 121.4456], 6);

        window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        }).addTo(map);
    }
}
