const mapElement = document.getElementById('group-map');

if (mapElement) {
    if (!window.L) {
        mapElement.textContent = 'Peta tidak dapat dimuat. Periksa koneksi internet lalu muat ulang halaman.';
    } else {
        const defaultCenter = [-1.4300, 121.4456];
        const map = window.L.map(mapElement, {
            zoomControl: true,
            attributionControl: true,
        }).setView(defaultCenter, 6);

        const satelliteLayer = window.L.tileLayer(
            'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
            {
                maxZoom: 19,
                attribution: 'Tiles © Esri &mdash; Source: Esri, Maxar, Earthstar Geographics',
            },
        ).addTo(map);

        const imageryReference = window.L.tileLayer(
            'https://server.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}',
            {
                maxZoom: 19,
                attribution: 'Esri',
                pane: 'overlayPane',
            },
        ).addTo(map);

        const streetLayer = window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        });

        window.L.control.layers({
            'Satellite': window.L.layerGroup([satelliteLayer, imageryReference]),
            'Street Map': streetLayer,
        }, null, {
            position: 'topright',
            collapsed: false,
        }).addTo(map);

        const locations = JSON.parse(mapElement.dataset.locations || '[]');
        const escapeHtml = (value) => String(value ?? '-')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');

        const groupMarkerIcon = window.L.divIcon({
            className: 'group-map-marker',
            html: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 36" aria-hidden="true"><path d="M12 1C5.9 1 1 5.9 1 12c0 8 11 23 11 23s11-15 11-23C23 5.9 18.1 1 12 1Z" fill="#dc2626" stroke="#fff" stroke-width="2"/><circle cx="12" cy="12" r="4" fill="#fff"/></svg>',
            iconSize: [32, 44],
            iconAnchor: [16, 44],
            popupAnchor: [0, -40],
        });

        const markers = [];
        locations.forEach((location) => {
            const latitude = Number(location.latitude);
            const longitude = Number(location.longitude);
            if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) {
                return;
            }

            const popup = `
                <div style="min-width:230px">
                    <strong style="display:block;font-size:14px;color:#0f766e;margin-bottom:8px">${escapeHtml(location.nama_kelompok)}</strong>
                    <dl style="display:grid;grid-template-columns:auto 1fr;gap:4px 10px;margin:0;font-size:12px">
                        <dt>Tahun Pembinaan</dt><dd style="margin:0">${escapeHtml(location.tahun_pembinaan)}</dd>
                        <dt>Tahun Pembentukan</dt><dd style="margin:0">${escapeHtml(location.tahun_pembentukan)}</dd>
                        <dt>Tahun Monitoring</dt><dd style="margin:0">${escapeHtml(location.tahun_monitoring)}</dd>
                        <dt>Kawasan</dt><dd style="margin:0">${escapeHtml(location.kawasan)}</dd>
                        <dt>Desa</dt><dd style="margin:0">${escapeHtml(location.desa)}</dd>
                        <dt>Kecamatan</dt><dd style="margin:0">${escapeHtml(location.kecamatan)}</dd>
                        <dt>Kabupaten</dt><dd style="margin:0">${escapeHtml(location.kabupaten)}</dd>
                        <dt>Jenis Usaha</dt><dd style="margin:0">${escapeHtml(location.jenis_usaha)}</dd>
                        <dt>Jumlah Anggota Laki-laki</dt><dd style="margin:0">${escapeHtml(location.jumlah_anggota_laki_laki)}</dd>
                        <dt>Jumlah Anggota Perempuan</dt><dd style="margin:0">${escapeHtml(location.jumlah_anggota_perempuan)}</dd>
                        <dt>Status Kelompok</dt><dd style="margin:0">${escapeHtml(location.status_kelompok)}</dd>
                    </dl>
                    <a href="${escapeHtml(location.detail_url)}" style="display:inline-block;margin-top:10px;color:#0f766e;font-weight:700">Lihat detail lengkap</a>
                </div>`;

            markers.push(window.L.marker([latitude, longitude], { icon: groupMarkerIcon }).bindPopup(popup, { maxWidth: 280 }));
            markers[markers.length - 1].addTo(map);
        });

        if (markers.length > 0) {
            const bounds = window.L.featureGroup(markers).getBounds();
            if (bounds.isValid()) {
                map.fitBounds(bounds.pad(0.25), { maxZoom: 12 });
            }
        }
    }
}
