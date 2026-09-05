import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

window.Alpine = Alpine;

Alpine.data('chartCanvas', (config) => ({
    chart: null,
    init() {
        this.chart = new Chart(this.$refs.canvas, config);
    },
}));

Alpine.start();
