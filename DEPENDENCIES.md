# Dependencies — exact versions

Every dependency is pinned **twice**: to an exact version in `composer.json` / `package.json`
themselves, and again (with every transitive dependency) in the lock files
`composer.lock` and `package-lock.json`, both committed in the root of this archive.
Install with `composer install` and `npm ci`, which read the lock files.

## PHP (composer.json → composer.lock)

| Package | Constraint | Locked version |
|---|---|---|
| php | ^8.2 | 8.2.35 or higher (security fixes); 8.4 recommended |
| laravel/framework | 12.69.2 (exact) | v12.69.2 |
| livewire/livewire | 4.4.5 (exact) | v4.4.5 — used only for its Alpine.js build, which Vite bundles (`resources/js/alpine.js`) |
| spatie/laravel-translatable | 6.11.4 (exact) | 6.11.4 |
| fakerphp/faker | 1.24.1 (exact) | v1.24.1 |
| laravel/pail | 1.2.7 (exact) | v1.2.7 |
| laravel/pint | 1.30.4 (exact) | v1.30.4 |
| laravel/tinker | 2.11.1 (exact) | v2.11.1 |
| mockery/mockery | 1.6.15 (exact) | 1.6.15 |
| nunomaduro/collision | 8.9.5 (exact) | v8.9.5 |
| phpunit/phpunit | 11.5.56 (exact) | 11.5.56 |

## JavaScript (package.json → package-lock.json)

| Package | Constraint | Locked version |
|---|---|---|
| @fontsource/ibm-plex-sans | 5.3.0 (exact) | 5.3.0 |
| @fontsource/ibm-plex-sans-arabic | 5.3.0 (exact) | 5.3.0 |
| @fontsource/inter | 5.3.0 (exact) | 5.3.0 |
| @fontsource/plus-jakarta-sans | 5.3.0 (exact) | 5.3.0 |
| tinymce | 7.9.3 (exact) | 7.9.3 |
| @tailwindcss/vite | 4.3.3 (exact) | 4.3.3 |
| concurrently | 9.2.5 (exact) | 9.2.5 |
| laravel-vite-plugin | 2.1.0 (exact) | 2.1.0 |
| tailwindcss | 4.3.3 (exact) | 4.3.3 |
| vite | 7.3.7 (exact) | 7.3.7 |

No known critical vulnerabilities in these versions at release (`npm audit`: 0 vulnerabilities).
Check again any time with `composer audit` and `npm audit`. To upgrade, change the version in
`composer.json` / `package.json`, run `composer update <package>` / `npm install`, run the tests
(`php artisan test`) and commit both the manifest and the lock file.

## Security audit

| Check | Date | Result |
|---|---|---|
| `npm audit --omit=dev` | 2026-10-23 | **0 vulnerabilities** (0 low, 0 moderate, 0 high, 0 critical) |
| `npm audit` (all, incl. build tools) | 2026-10-23 | **0 vulnerabilities** |
| `composer audit --locked` | run before every release | checks every package in `composer.lock` against the PHP Security Advisories database |

Run both at once: `composer security-audit`. No package in either lock file is abandoned or a
pre-release (all stable tags).

## Lock files in this archive

Both lock files are committed in the archive root (some file listings hide lock files; they are
there). Checksums:

| File | Packages pinned | SHA-256 |
|---|---|---|
| `composer.lock` | 76 production + 36 development | `4a84b89a980622532e88a52ab0f29aaa2e28722a4e27523992f16cd7600b6d6c` |
| `package-lock.json` | 139 | `5aa94ff9dd241a299e5cace3d17f91703a15f49c72e0dce96bd36fe070b4d18c` |

## Every production PHP package (from composer.lock)

| Package | Locked version |
|---|---|
| brick/math | 0.14.8 |
| carbonphp/carbon-doctrine-types | 3.2.1 |
| dflydev/dot-access-data | v3.0.3 |
| doctrine/inflector | 2.1.0 |
| doctrine/lexer | 3.0.2 |
| dragonmantank/cron-expression | v3.6.0 |
| egulias/email-validator | 4.0.4 |
| fruitcake/php-cors | v1.4.0 |
| graham-campbell/result-type | v1.2.0 |
| guzzlehttp/guzzle | 7.15.5 |
| guzzlehttp/promises | 2.5.3 |
| guzzlehttp/psr7 | 2.13.1 |
| guzzlehttp/uri-template | v1.0.11 |
| laravel/framework | v12.69.2 |
| laravel/prompts | v0.3.24 |
| laravel/serializable-closure | v2.0.16 |
| league/commonmark | 2.10.2 |
| league/config | v1.2.0 |
| league/flysystem | 3.36.0 |
| league/flysystem-local | 3.35.3 |
| league/mime-type-detection | 1.17.0 |
| league/uri | 7.8.1 |
| league/uri-interfaces | 7.8.1 |
| livewire/livewire | v4.4.5 |
| monolog/monolog | 3.12.0 |
| nesbot/carbon | 3.14.0 |
| nette/schema | v1.3.6 |
| nette/utils | v4.1.5 |
| nunomaduro/termwind | v2.4.0 |
| phpoption/phpoption | 1.10.0 |
| psr/clock | 1.0.0 |
| psr/container | 2.0.2 |
| psr/event-dispatcher | 1.0.0 |
| psr/http-client | 1.0.3 |
| psr/http-factory | 1.1.0 |
| psr/http-message | 2.0 |
| psr/log | 3.0.2 |
| psr/simple-cache | 3.0.0 |
| ralouphie/getallheaders | 3.0.3 |
| ramsey/collection | 2.1.1 |
| ramsey/uuid | 4.9.4 |
| spatie/laravel-package-tools | 1.93.2 |
| spatie/laravel-translatable | 6.11.4 |
| symfony/clock | v7.4.8 |
| symfony/console | v7.4.19 |
| symfony/css-selector | v7.4.18 |
| symfony/deprecation-contracts | v3.7.1 |
| symfony/error-handler | v7.4.17 |
| symfony/event-dispatcher | v7.4.17 |
| symfony/event-dispatcher-contracts | v3.7.1 |
| symfony/finder | v7.4.19 |
| symfony/http-foundation | v7.4.19 |
| symfony/http-kernel | v7.4.19 |
| symfony/mailer | v7.4.19 |
| symfony/mime | v7.4.19 |
| symfony/polyfill-ctype | v1.37.0 |
| symfony/polyfill-intl-grapheme | v1.41.0 |
| symfony/polyfill-intl-idn | v1.42.0 |
| symfony/polyfill-intl-normalizer | v1.42.0 |
| symfony/polyfill-mbstring | v1.38.2 |
| symfony/polyfill-php80 | v1.37.0 |
| symfony/polyfill-php83 | v1.41.0 |
| symfony/polyfill-php84 | v1.38.1 |
| symfony/polyfill-php85 | v1.41.0 |
| symfony/polyfill-uuid | v1.37.0 |
| symfony/process | v7.4.19 |
| symfony/routing | v7.4.18 |
| symfony/service-contracts | v3.7.3 |
| symfony/string | v7.4.19 |
| symfony/translation | v7.4.17 |
| symfony/translation-contracts | v3.7.1 |
| symfony/uid | v7.4.17 |
| symfony/var-dumper | v7.4.18 |
| tijsverkoyen/css-to-inline-styles | v2.4.0 |
| vlucas/phpdotenv | v5.7.0 |
| voku/portable-ascii | 2.1.1 |

## Every JavaScript package (from package-lock.json; build tools, plus TinyMCE which is bundled into the admin only)

| Package | Locked version |
|---|---|
| @emnapi/core | 1.11.1 |
| @emnapi/runtime | 1.11.1 |
| @emnapi/wasi-threads | 1.2.2 |
| @esbuild/aix-ppc64 | 0.28.2 |
| @esbuild/android-arm | 0.28.2 |
| @esbuild/android-arm64 | 0.28.2 |
| @esbuild/android-x64 | 0.28.2 |
| @esbuild/darwin-arm64 | 0.28.2 |
| @esbuild/darwin-x64 | 0.28.2 |
| @esbuild/freebsd-arm64 | 0.28.2 |
| @esbuild/freebsd-x64 | 0.28.2 |
| @esbuild/linux-arm | 0.28.2 |
| @esbuild/linux-arm64 | 0.28.2 |
| @esbuild/linux-ia32 | 0.28.2 |
| @esbuild/linux-loong64 | 0.28.2 |
| @esbuild/linux-mips64el | 0.28.2 |
| @esbuild/linux-ppc64 | 0.28.2 |
| @esbuild/linux-riscv64 | 0.28.2 |
| @esbuild/linux-s390x | 0.28.2 |
| @esbuild/linux-x64 | 0.28.2 |
| @esbuild/netbsd-arm64 | 0.28.2 |
| @esbuild/netbsd-x64 | 0.28.2 |
| @esbuild/openbsd-arm64 | 0.28.2 |
| @esbuild/openbsd-x64 | 0.28.2 |
| @esbuild/openharmony-arm64 | 0.28.2 |
| @esbuild/sunos-x64 | 0.28.2 |
| @esbuild/win32-arm64 | 0.28.2 |
| @esbuild/win32-ia32 | 0.28.2 |
| @esbuild/win32-x64 | 0.28.2 |
| @fontsource/ibm-plex-sans | 5.3.0 |
| @fontsource/ibm-plex-sans-arabic | 5.3.0 |
| @fontsource/inter | 5.3.0 |
| @fontsource/plus-jakarta-sans | 5.3.0 |
| @jridgewell/gen-mapping | 0.3.13 |
| @jridgewell/remapping | 2.3.5 |
| @jridgewell/resolve-uri | 3.1.2 |
| @jridgewell/sourcemap-codec | 1.6.0 |
| @jridgewell/trace-mapping | 0.3.31 |
| @napi-rs/lzma-linux-x64-gnu | 1.5.1 |
| @napi-rs/wasm-runtime | 1.1.4 |
| @rollup/rollup-android-arm-eabi | 4.63.4 |
| @rollup/rollup-android-arm64 | 4.63.4 |
| @rollup/rollup-darwin-arm64 | 4.63.4 |
| @rollup/rollup-darwin-x64 | 4.63.4 |
| @rollup/rollup-freebsd-arm64 | 4.63.4 |
| @rollup/rollup-freebsd-x64 | 4.63.4 |
| @rollup/rollup-linux-arm-gnueabihf | 4.63.4 |
| @rollup/rollup-linux-arm-musleabihf | 4.63.4 |
| @rollup/rollup-linux-arm64-gnu | 4.63.4 |
| @rollup/rollup-linux-arm64-musl | 4.63.4 |
| @rollup/rollup-linux-loong64-gnu | 4.63.4 |
| @rollup/rollup-linux-loong64-musl | 4.63.4 |
| @rollup/rollup-linux-ppc64-gnu | 4.63.4 |
| @rollup/rollup-linux-ppc64-musl | 4.63.4 |
| @rollup/rollup-linux-riscv64-gnu | 4.63.4 |
| @rollup/rollup-linux-riscv64-musl | 4.63.4 |
| @rollup/rollup-linux-s390x-gnu | 4.63.4 |
| @rollup/rollup-linux-x64-gnu | 4.63.4 |
| @rollup/rollup-linux-x64-musl | 4.63.4 |
| @rollup/rollup-openbsd-x64 | 4.63.4 |
| @rollup/rollup-openharmony-arm64 | 4.63.4 |
| @rollup/rollup-win32-arm64-msvc | 4.63.4 |
| @rollup/rollup-win32-ia32-msvc | 4.63.4 |
| @rollup/rollup-win32-x64-gnu | 4.63.4 |
| @rollup/rollup-win32-x64-msvc | 4.63.4 |
| @tailwindcss/node | 4.3.3 |
| @tailwindcss/oxide | 4.3.3 |
| @tailwindcss/oxide-android-arm64 | 4.3.3 |
| @tailwindcss/oxide-darwin-arm64 | 4.3.3 |
| @tailwindcss/oxide-darwin-x64 | 4.3.3 |
| @tailwindcss/oxide-freebsd-x64 | 4.3.3 |
| @tailwindcss/oxide-linux-arm-gnueabihf | 4.3.3 |
| @tailwindcss/oxide-linux-arm64-gnu | 4.3.3 |
| @tailwindcss/oxide-linux-arm64-musl | 4.3.3 |
| @tailwindcss/oxide-linux-x64-gnu | 4.3.3 |
| @tailwindcss/oxide-linux-x64-musl | 4.3.3 |
| @tailwindcss/oxide-wasm32-wasi | 4.3.3 |
| @tailwindcss/oxide-win32-arm64-msvc | 4.3.3 |
| @tailwindcss/oxide-win32-x64-msvc | 4.3.3 |
| @tailwindcss/vite | 4.3.3 |
| @tybys/wasm-util | 0.10.2 |
| @types/estree | 1.0.9 |
| ansi-regex | 5.0.1 |
| ansi-styles | 4.3.0 |
| chalk | 4.1.2 |
| cliui | 8.0.1 |
| color-convert | 2.0.1 |
| color-name | 1.1.4 |
| concurrently | 9.2.5 |
| detect-libc | 2.1.2 |
| emoji-regex | 8.0.0 |
| enhanced-resolve | 5.25.1 |
| esbuild | 0.28.2 |
| escalade | 3.2.0 |
| fdir | 6.5.0 |
| fsevents | 2.3.3 |
| get-caller-file | 2.0.5 |
| graceful-fs | 4.2.11 |
| has-flag | 4.0.0 |
| is-fullwidth-code-point | 3.0.0 |
| jiti | 2.7.0 |
| laravel-vite-plugin | 2.1.0 |
| lightningcss | 1.32.0 |
| lightningcss-android-arm64 | 1.32.0 |
| lightningcss-darwin-arm64 | 1.32.0 |
| lightningcss-darwin-x64 | 1.32.0 |
| lightningcss-freebsd-x64 | 1.32.0 |
| lightningcss-linux-arm-gnueabihf | 1.32.0 |
| lightningcss-linux-arm64-gnu | 1.32.0 |
| lightningcss-linux-arm64-musl | 1.32.0 |
| lightningcss-linux-x64-gnu | 1.32.0 |
| lightningcss-linux-x64-musl | 1.32.0 |
| lightningcss-win32-arm64-msvc | 1.32.0 |
| lightningcss-win32-x64-msvc | 1.32.0 |
| magic-string | 0.30.21 |
| nanoid | 3.3.19 |
| picocolors | 1.1.1 |
| picomatch | 2.3.2 |
| postcss | 8.5.28 |
| require-directory | 2.1.1 |
| rollup | 4.63.4 |
| rxjs | 7.8.2 |
| shell-quote | 1.12.0 |
| source-map-js | 1.2.2 |
| string-width | 4.2.3 |
| strip-ansi | 6.0.1 |
| supports-color | 8.1.1 |
| tailwindcss | 4.3.3 |
| tapable | 2.3.3 |
| tinyglobby | 0.2.17 |
| tinymce | 7.9.3 |
| tree-kill | 1.2.2 |
| tslib | 2.8.1 |
| vite | 7.3.7 |
| vite-plugin-full-reload | 1.2.0 |
| wrap-ansi | 7.0.0 |
| y18n | 5.0.8 |
| yargs | 17.7.2 |
| yargs-parser | 21.1.1 |
