// Alpine.js for every page, bundled into our own scripts by Vite.
//
// It comes from Livewire's ES module build, so we get exactly the Alpine version and plugins
// (collapse, focus, intersect, anchor, mask, morph …) that Livewire ships. Before 1.0.61 it was
// loaded with @livewireScripts from the /livewire-…/livewire.js route; when a web server, proxy
// or firewall blocked that URL (403), Alpine never started and every button built on it —
// checkout steps, pack-size picker, fulfil form — stopped working. Now it arrives inside the
// page's own module script from /build, which every server already serves.
//
// The module starts Livewire (and with it Alpine) on DOMContentLoaded. Module scripts run
// before that event, so components registered on "alpine:init" in our files are always ready.
import { Alpine, Livewire } from '../../vendor/livewire/livewire/dist/livewire.esm.js';

export { Alpine, Livewire };
