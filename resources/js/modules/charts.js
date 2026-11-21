/** Crosshair + tooltip for the server-rendered line charts (components/chart/line.blade.php). */
export function initCharts() {
    document.querySelectorAll('[data-chart="line"]').forEach((figure) => {
        const points = JSON.parse(figure.dataset.points || '[]');
        const series = JSON.parse(figure.dataset.series || '{}');
        const svg = figure.querySelector('svg');
        const hit = figure.querySelector('[data-hit]');
        const crosshair = figure.querySelector('[data-crosshair]');
        const tooltip = figure.querySelector('[data-tooltip]');
        if (!svg || !points.length) return;
        const viewBox = svg.viewBox.baseVal;
        const yOf = {};
        // Re-derive each dot's y from the rendered polyline so the tooltip matches the drawing.
        figure.querySelectorAll('polyline').forEach((line, i) => {
            const key = Object.keys(series)[i];
            yOf[key] = line.getAttribute('points').split(' ').map((pair) => Number(pair.split(',')[1]));
        });

        const show = (index) => {
            const p = points[index];
            crosshair.setAttribute('x1', p.x);
            crosshair.setAttribute('x2', p.x);
            crosshair.setAttribute('visibility', 'visible');
            Object.keys(series).forEach((key) => {
                const dot = figure.querySelector(`[data-dot="${key}"]`);
                dot.setAttribute('cx', p.x);
                dot.setAttribute('cy', yOf[key][index]);
                dot.setAttribute('visibility', 'visible');
            });
            tooltip.innerHTML = `<p class="mb-1 font-medium text-white"></p>${Object.entries(series).map(([key, s]) => `<p class="flex items-center justify-between gap-4 text-slate-300"><span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full" style="background:${s.color}"></span>${s.label}</span><span class="font-mono text-white">${p[key]}</span></p>`).join('')}`;
            tooltip.firstElementChild.textContent = p.label;
            tooltip.classList.remove('hidden');
            const scale = svg.getBoundingClientRect().width / viewBox.width;
            const left = p.x * scale;
            const width = tooltip.offsetWidth;
            tooltip.style.left = `${Math.min(Math.max(0, left + 12), svg.getBoundingClientRect().width - width)}px`;
        };
        const hide = () => {
            crosshair.setAttribute('visibility', 'hidden');
            figure.querySelectorAll('[data-dot]').forEach((dot) => dot.setAttribute('visibility', 'hidden'));
            tooltip.classList.add('hidden');
        };
        const nearest = (event) => {
            const rect = svg.getBoundingClientRect();
            const x = ((event.clientX - rect.left) / rect.width) * viewBox.width;
            let best = 0;
            points.forEach((p, i) => { if (Math.abs(p.x - x) < Math.abs(points[best].x - x)) best = i; });
            return best;
        };

        hit.addEventListener('pointermove', (event) => show(nearest(event)));
        hit.addEventListener('pointerleave', hide);
        // Keyboard: focus the chart and step through days with the arrow keys.
        let index = points.length - 1;
        svg.setAttribute('tabindex', '0');
        svg.addEventListener('focus', () => show(index));
        svg.addEventListener('blur', hide);
        svg.addEventListener('keydown', (event) => {
            if (event.key === 'ArrowRight') index = Math.min(points.length - 1, index + 1);
            else if (event.key === 'ArrowLeft') index = Math.max(0, index - 1);
            else return;
            event.preventDefault();
            show(index);
        });
    });
}
