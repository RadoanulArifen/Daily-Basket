// Countdown boxes (the design's "Ends in" timers). `end` is an ISO date/time; with
// midnight=true and no end, it counts to the next midnight (Deal of the Day).
export default function fnCountdown(end, days = false, labels = null) {
    return {
        units: [],
        init() {
            this.tick();
            this.timer = setInterval(() => this.tick(), 1000);
        },
        destroy() {
            clearInterval(this.timer);
        },
        tick() {
            let target = end ? new Date(end) : new Date();
            if (!end) target.setHours(24, 0, 0, 0);
            const left = Math.max(0, Math.floor((target - new Date()) / 1000));
            const pad = (n) => String(n).padStart(2, '0');
            // Unit names come from the page (translated there); English if none given.
            const L = labels ?? (days ? ['Days', 'Hours', 'Mins', 'Secs'] : ['Hrs', 'Min', 'Sec']);
            this.units = days
                ? [[L[0], Math.floor(left / 86400)], [L[1], Math.floor(left / 3600) % 24], [L[2], Math.floor(left / 60) % 60], [L[3], left % 60]].map(([label, v]) => ({ label, value: pad(v) }))
                : [[L[0], Math.floor(left / 3600)], [L[1], Math.floor(left / 60) % 60], [L[2], left % 60]].map(([label, v]) => ({ label, value: pad(v) }));
        },
    };
}
