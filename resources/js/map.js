import L from 'leaflet';

// Corrige les chemins d'icônes de marqueur par défaut sous bundling Vite.
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';

L.Icon.Default.mergeOptions({
    iconRetinaUrl: markerIcon2x,
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
});

/**
 * Initialise une carte OpenStreetMap avec un marqueur par intervention géolocalisée.
 * @param {string} elementId
 * @param {Array<{lat:number, lng:number, label:string}>} points
 */
window.initKdiMap = function (elementId, points) {
    const el = document.getElementById(elementId);
    if (!el || el.dataset.kdiMapReady === '1') {
        return;
    }

    // Attendre que le conteneur soit dimensionné (rendu Livewire) avant d'initialiser :
    // sinon Leaflet mesure une taille nulle (largeur ou hauteur) et retombe au zoom monde.
    if (el.clientHeight === 0 || el.clientWidth === 0) {
        setTimeout(() => window.initKdiMap(elementId, points), 100);
        return;
    }
    el.dataset.kdiMapReady = '1';

    const map = L.map(el);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 18,
        attribution: '© OpenStreetMap',
    }).addTo(map);

    const markers = [];
    (points || []).forEach((p) => {
        if (typeof p.lat === 'number' && typeof p.lng === 'number') {
            const m = L.marker([p.lat, p.lng]).addTo(map);
            if (p.label) {
                m.bindPopup(p.label);
            }
            markers.push(m);
        }
    });

    const applyView = () => {
        map.invalidateSize();
        if (markers.length > 0) {
            map.fitBounds(L.featureGroup(markers).getBounds().pad(0.3), { maxZoom: 12 });
        } else {
            map.setView([9.6412, -13.5784], 6); // Guinée par défaut
        }
    };

    applyView();
    // Re-cadrage après stabilisation du layout (largeur définitive).
    setTimeout(applyView, 250);
};
