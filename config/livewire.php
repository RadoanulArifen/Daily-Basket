<?php

/*
 * The store has no Livewire components: Livewire is installed only for its Alpine.js build,
 * which Vite bundles into our own scripts (resources/js/alpine.js). So Livewire must never add
 * its own <script src="/livewire-…/livewire.js"> to a page — that would load a second Alpine.
 * Every other Livewire option keeps its default.
 */
return [
    'inject_assets' => false,
];
