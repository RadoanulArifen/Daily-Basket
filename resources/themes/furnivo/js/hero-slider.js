/** Home hero: slides change every few seconds, with pause/play and the progress bars. */
export default ({ count = 1, interval = 6000 } = {}) => ({
    count,
    index: 0,
    playing: true,
    timer: null,

    init() {
        this.start();
    },

    start() {
        clearInterval(this.timer);
        if (this.playing && this.count > 1) {
            this.timer = setInterval(() => this.next(), interval);
        }
    },

    next() {
        this.index = (this.index + 1) % this.count;
    },

    go(i) {
        this.index = i;
        this.start();
    },

    toggle() {
        this.playing = ! this.playing;
        this.start();
    },
});
