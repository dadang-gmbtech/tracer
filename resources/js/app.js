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

/**
 * Animates a number counting up from 0 to `target` the first time the
 * element scrolls into view. Usage: <span x-data="countUp(1234)" x-text="display">0</span>
 */
Alpine.data('countUp', (target, decimals = 0) => ({
    display: (0).toLocaleString('id-ID'),
    init() {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    this.run();
                    observer.disconnect();
                }
            });
        }, { threshold: 0.5 });

        observer.observe(this.$el);
    },
    run() {
        const duration = 1200;
        const start = performance.now();
        const step = (now) => {
            const progress = Math.min((now - start) / duration, 1);
            const value = target * progress;
            this.display = value.toLocaleString('id-ID', {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals,
            });
            if (progress < 1) {
                requestAnimationFrame(step);
            }
        };
        requestAnimationFrame(step);
    },
}));

Alpine.start();
