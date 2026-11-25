// Analytics → Traffic: the world map (shading, hover, live pins) and the "Live now" panel.
// No library: the map is an inline SVG with one <path data-cc="AE"> per country.

const SHADES = ['#e8f1fc', '#c6dcf7', '#94bdf0', '#5b97e3', '#2f74d0', '#0a5fd6'];
const NS = 'http://www.w3.org/2000/svg';

// d3.geoNaturalEarth1, the projection the map was drawn with; scale/translate are on the <svg>.
function project(svg, lat, lon) {
    const k = +svg.dataset.k, tx = +svg.dataset.tx, ty = +svg.dataset.ty;
    const l = (lon * Math.PI) / 180, p = (lat * Math.PI) / 180, p2 = p * p, p4 = p2 * p2;
    const x = l * (0.8707 - 0.131979 * p2 + p4 * (-0.013791 + p4 * (0.003971 * p2 - 0.001529 * p4)));
    const y = p * (1.007226 + p2 * (0.015085 + p4 * (-0.044475 + 0.028874 * p2 - 0.005916 * p4)));
    return [tx + k * x, ty - k * y];
}

function parse(el, key, fallback) {
    try { return JSON.parse(el.dataset[key] || ''); } catch { return fallback; }
}

export function trafficMap() {
    return {
        tip: null,
        tipStyle: '',
        init() {
            const svg = this.$el.querySelector('[data-map] svg');
            if (!svg) return;
            svg.setAttribute('role', 'img');
            svg.style.width = '100%';
            svg.style.height = 'auto';
            svg.style.display = 'block';
            const data = parse(this.$el, 'countries', {});
            const labels = parse(this.$el, 'labels', {});
            const max = Math.max(1, ...Object.values(data).map((c) => c.visitors || 0));

            svg.querySelectorAll('path[data-cc]').forEach((path) => {
                const c = data[path.dataset.cc];
                const v = c ? c.visitors : 0;
                // Square-root scale, so a few big countries don't wash out the rest.
                const step = v ? Math.min(SHADES.length - 1, Math.floor(Math.sqrt(v / max) * SHADES.length)) : -1;
                path.style.fill = step < 0 ? '#eef0f3' : SHADES[step];
                path.style.stroke = '#fff';
                path.style.strokeWidth = '0.5';
                path.style.cursor = 'default';
                path.querySelector('title')?.remove();
                path.addEventListener('mousemove', (e) => {
                    const box = this.$el.getBoundingClientRect();
                    const name = c?.name || path.dataset.name || path.dataset.cc;
                    this.tip = {
                        name,
                        line: c ? `${labels.visitors || 'Visitors'}: ${c.visitors.toLocaleString()} · ${labels.visits || 'Visits'}: ${c.visits.toLocaleString()} · ${labels.pages || 'Pages'}: ${c.pages.toLocaleString()}` : (labels.none || 'No visitors'),
                    };
                    const x = Math.min(e.clientX - box.left + 12, box.width - 220);
                    this.tipStyle = `left:${Math.max(0, x)}px;top:${e.clientY - box.top + 12}px`;
                    path.style.stroke = '#1a1a1a';
                    path.style.strokeWidth = '1';
                });
                path.addEventListener('mouseleave', () => { this.tip = null; path.style.stroke = '#fff'; path.style.strokeWidth = '0.5'; });
            });

            this.pins(svg, parse(this.$el, 'live', []));
            window.addEventListener('traffic:live', (e) => this.pins(svg, e.detail || []));
        },
        // Green pins for people on the shop now (one per city).
        pins(svg, live) {
            svg.querySelectorAll('[data-pin]').forEach((p) => p.remove());
            const seen = new Set();
            for (const row of live) {
                if (row.lat == null || row.lon == null) continue;
                const key = `${row.lat.toFixed(1)},${row.lon.toFixed(1)}`;
                if (seen.has(key)) continue;
                seen.add(key);
                const [x, y] = project(svg, row.lat, row.lon);
                const g = document.createElementNS(NS, 'g');
                g.setAttribute('data-pin', '');
                g.innerHTML = `<circle cx="${x.toFixed(1)}" cy="${y.toFixed(1)}" r="7" fill="#16a34a" opacity=".25"><animate attributeName="r" values="4;10;4" dur="2s" repeatCount="indefinite"/></circle><circle cx="${x.toFixed(1)}" cy="${y.toFixed(1)}" r="3.6" fill="#16a34a" stroke="#fff" stroke-width="1.2"/>`;
                svg.appendChild(g);
            }
        },
    };
}

export function trafficLive() {
    return {
        on: true,
        timer: null,
        init() {
            this.timer = setInterval(() => { if (this.on && !document.hidden) this.refresh(); }, 10000);
            this.$watch('on', (v) => { if (v) this.refresh(); });
        },
        destroy() { clearInterval(this.timer); },
        async refresh() {
            try {
                const response = await fetch(this.$el.dataset.url, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                if (!response.ok) return;
                const data = await response.json();
                const list = this.$el.querySelector('[data-live-list]');
                const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
                if (list) {
                    list.innerHTML = data.live.length
                        ? data.live.map((r) => `<li class="flex items-start gap-3 px-4 py-2.5"><span class="mt-0.5 text-lg leading-none" aria-hidden="true">${esc(r.flag)}</span><span class="min-w-0 flex-1"><span class="block truncate text-[13px] font-medium text-ink-strong">${esc(r.place)}</span><span class="block truncate font-mono text-xs text-muted" dir="ltr">${esc(r.path)}</span></span><span class="shrink-0 text-xs text-muted">${esc(r.ago)}</span></li>`).join('')
                        : `<li class="px-4 py-8 text-center text-[13px] text-muted">${esc(data.empty)}</li>`;
                }
                document.querySelectorAll('[data-live-count]').forEach((el) => { el.textContent = Number(data.count).toLocaleString(); });
                window.dispatchEvent(new CustomEvent('traffic:live', { detail: data.live }));
            } catch {
                // Offline for a moment: try again on the next tick.
            }
        },
    };
}
