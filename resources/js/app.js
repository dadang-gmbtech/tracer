import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

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

Alpine.start();
