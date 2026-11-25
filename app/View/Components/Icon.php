<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Inline SVG icon from resources/svg/icons (Lucide, ISC licence).
 *
 *   <x-icon name="package" class="size-4" />
 *
 * To add an icon, copy the inner <path>… elements of any Lucide icon
 * into resources/svg/icons/{name}.svg.
 */
class Icon extends Component
{
    /** @var array<string, string> */
    private static array $cache = [];

    public function __construct(public string $name) {}

    public function paths(): string
    {
        return self::$cache[$this->name] ??= $this->load();
    }

    private function load(): string
    {
        $file = resource_path("svg/icons/{$this->name}.svg");

        if (! is_file($file)) {
            report(new \InvalidArgumentException("Icon [{$this->name}] not found in resources/svg/icons."));

            return '';
        }

        return trim(file_get_contents($file));
    }

    public function render(): View
    {
        return view('components.icon');
    }
}
