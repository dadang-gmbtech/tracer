import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

window.Alpine = Alpine;

Alpine.data('chartCanvas', (config) => ({
    chart: null,
    init() {
        this.chart = new Chart(this.$refs.canvas, config);
    },
}));

/**
 * Fades/slides an element in the first time it scrolls into view. Usage:
 *   <div x-data="revealOnScroll" :class="visible && 'opacity-100 translate-y-0'"
 *        class="opacity-0 translate-y-6 transition-all duration-700">
 */
Alpine.data('revealOnScroll', () => ({
    visible: false,
    init() {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    this.visible = true;
                    observer.disconnect();
                }
            });
        }, { threshold: 0.15 });

        observer.observe(this.$el);
    },
}));

/**
 * Plots alumni-per-province counts as proportional circle markers on an
 * OpenStreetMap base layer. Usage:
 *   <div x-data="provinceMap(points)"><div x-ref="map" style="height: 420px"></div></div>
 * where each point is {name, jumlah, lat, lng} — points with a null lat/lng
 * (e.g. no coordinate on record) are skipped, not plotted at (0, 0).
 */
Alpine.data('provinceMap', (points) => ({
    init() {
        const map = L.map(this.$refs.map, { scrollWheelZoom: false }).setView([-2.5, 118], 5);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 10,
        }).addTo(map);

        const plottable = points.filter((p) => p.lat !== null && p.lng !== null);
        const maxJumlah = Math.max(1, ...plottable.map((p) => p.jumlah));

        plottable.forEach((p) => {
            const radius = 6 + (p.jumlah / maxJumlah) * 26;

            L.circleMarker([p.lat, p.lng], {
                radius,
                color: '#1e40af',
                weight: 1,
                fillColor: '#3b82f6',
                fillOpacity: 0.55,
            })
                .addTo(map)
                .bindTooltip(`${p.name}: ${p.jumlah} alumni`);
        });
    },
}));

Alpine.start();
