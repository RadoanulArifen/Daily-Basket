@extends('admin.layouts.app')

@php
    // Arabic fields only appear when the store has Arabic switched on (Settings → Store details).
    $showArabic = app(\App\Support\Localization::class)->enabled('ar');

    // "Configure your piece" rows for the editor: values as "Walnut #6b4423, Charcoal".
    $configurationRows = collect(old('configuration', collect($product->configuration ?? [])->map(fn ($group) => [
        'label' => $group['label'] ?? '',
        'values' => collect($group['values'] ?? [])
            ->map(fn ($value) => $value.(isset($group['swatches'][$value]) ? ' '.$group['swatches'][$value] : ''))
            ->implode(', '),
    ])->all()))->values()->all();
@endphp

@section('title', $product->exists ? $product->name : __('admin.products.add'))

@section('content')
    @php
        $state = $formState + [
            'errors' => $errors->toArray(),
            'messages' => [
                'type' => __('admin.media.errors.type'),
                'size' => __('admin.media.errors.size'),
                'failed' => __('admin.media.upload_failed'),
            ],
        ];
        $selectedCategories = collect(old('category_ids', $product->exists ? $product->categories()->pluck('categories.id')->all() : []))->map(fn ($id) => (int) $id);
        $currency = config('store.base_currency');
        $status = old('status', $product->status?->value ?? 'draft');
    @endphp

    <x-admin.page-header :title="$product->exists ? $product->name : __('admin.products.add')" :back="route('admin.products.index')">
        @if ($product->exists)
            <x-slot:badges>
                <x-admin.badge :tone="match ($product->status->value) { 'active' => 'success', 'draft' => 'info', default => 'neutral' }">{{ $product->status->label() }}</x-admin.badge>
            </x-slot:badges>
            <x-slot:actions>
                <x-admin.button variant="secondary" :href="store_url('/products/'.$product->slug, config('store.default_locale'))" target="_blank" rel="noopener" icon="external-link">{{ __('admin.products.view') }}</x-admin.button>
            </x-slot:actions>
        @endif
    </x-admin.page-header>

    <form method="POST" action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}"
          x-data="productEditor(@js($state))" @input="dirty = true" @change="dirty = true" novalidate>
        @csrf
        @if ($product->exists) @method('PUT') @endif

        @if ($errors->any())
            <x-admin.alert tone="danger" class="mb-6" :title="__('admin.products.errors.title')">
                <ul class="list-disc ps-5">
                    @foreach (collect($errors->all())->unique()->take(6) as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </x-admin.alert>
        @endif

        <div class="grid gap-4 lg:grid-cols-3">
            {{-- ===== Main column ===== --}}
            <div class="min-w-0 space-y-4 lg:col-span-2">

                <x-admin.card data-tour="product-details">
                    <div class="space-y-5">
                        <x-admin.translatable-input name="name" :label="__('admin.products.fields.name')" :values="$product->getTranslations('name')" required maxlength="200" />
                        <x-admin.translatable-input name="short_description" :label="__('admin.products.fields.short_description')" :values="$product->getTranslations('short_description')" rich :rows="3" :help="__('admin.products.fields.short_description_help')" />
                        <x-admin.translatable-input name="description" :label="__('admin.products.fields.description')" :values="$product->getTranslations('description')" rich :rows="8" :help="__('admin.products.fields.description_help')" />
                        <x-admin.translatable-input name="dimensions" :label="__('admin.products.fields.dimensions')" :values="$product->getTranslations('dimensions')" rich :rows="4" :help="__('admin.products.fields.dimensions_help')" />
                        <x-admin.translatable-input name="delivery_returns" :label="__('admin.products.fields.delivery_returns')" :values="$product->getTranslations('delivery_returns')" rich :rows="3" :help="__('admin.products.fields.delivery_returns_help')" />
                        <x-admin.translatable-input name="trust_badges" :label="__('admin.products.fields.trust_badges')" :values="$product->getTranslations('trust_badges')" multiline :rows="3" maxlength="400" :help="__('admin.products.fields.trust_badges_help')" />
                    </div>
                </x-admin.card>

                {{-- Images --}}
                <x-admin.card data-tour="product-media" :title="__('admin.products.media_title')" :description="__('admin.products.media_text')">
                    <x-slot:actions>
                        <x-admin.button type="button" variant="ghost" size="sm" x-on:click="openLibrary()">{{ __('admin.media.choose_existing') }}</x-admin.button>
                    </x-slot:actions>

                    <div class="space-y-4">
                        <template x-for="item in media" :key="item.id">
                            <input type="hidden" name="media_ids[]" :value="item.id">
                        </template>

                        <ul x-show="media.length" class="grid grid-cols-3 gap-3 sm:grid-cols-4" x-sort="(id, position) => moveMedia(id, position)">
                            <template x-for="(item, index) in media" :key="item.id">
                                <li x-sort:item="item.id" class="group relative aspect-square overflow-hidden rounded-lg border border-line bg-canvas first:col-span-2 first:row-span-2">
                                    <img :src="index === 0 ? item.url : item.thumb" :alt="item.alt || ''" class="size-full cursor-grab object-cover active:cursor-grabbing">
                                    <span x-show="index === 0" class="absolute start-2 top-2 rounded-md bg-ink/75 px-1.5 py-0.5 text-[11px] font-medium text-white">{{ __('admin.products.main_image') }}</span>
                                    <div class="absolute inset-x-1.5 bottom-1.5 flex justify-end gap-1 opacity-100 transition sm:opacity-0 sm:group-hover:opacity-100 sm:group-focus-within:opacity-100">
                                        <button type="button" @click="shiftMedia(index, -1)" x-show="index > 0" class="rounded-md bg-surface/95 p-1 text-ink shadow-sm" aria-label="{{ __('admin.products.move_earlier') }}"><x-icon name="arrow-left" class="size-3.5 rtl:rotate-180" /></button>
                                        <button type="button" @click="shiftMedia(index, 1)" x-show="index < media.length - 1" class="rounded-md bg-surface/95 p-1 text-ink shadow-sm" aria-label="{{ __('admin.products.move_later') }}"><x-icon name="arrow-right" class="size-3.5 rtl:rotate-180" /></button>
                                        <button type="button" @click="removeMedia(item.id)" class="rounded-md bg-surface/95 p-1 text-danger-600 shadow-sm" aria-label="{{ __('admin.products.remove_image') }}"><x-icon name="x" class="size-3.5" /></button>
                                    </div>
                                </li>
                            </template>
                        </ul>

                        <label @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false" @drop.prevent="dropFiles($event)"
                               class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed px-4 py-8 text-center transition"
                               :class="dragging ? 'border-brand-600 bg-brand-50' : 'border-line-strong hover:border-brand-500 hover:bg-canvas/60'">
                            <x-icon name="image" class="size-6 text-muted" />
                            <span class="text-sm font-medium text-ink">{{ __('admin.media.drop_title') }}</span>
                            <span class="text-[13px] text-muted">{{ __('admin.media.drop_help') }}</span>
                            <input type="file" multiple accept="image/jpeg,image/png,image/webp,image/gif" class="sr-only" @change="pickFiles($event)">
                        </label>

                        @include('admin.catalog.partials.upload-list')
                        <x-admin.field-error name="media_ids" />
                    </div>
                </x-admin.card>

                {{-- Pricing / inventory / shipping for a product without options --}}
                <template x-if="! hasOptions()">
                    <div class="space-y-6">
                        <x-admin.card :title="__('admin.products.pricing_title')">
                            <div class="grid gap-5 sm:grid-cols-2">
                                <div class="space-y-1.5">
                                    <x-admin.label for="price" required>{{ __('admin.products.fields.price') }}</x-admin.label>
                                    <div class="flex">
                                        <span class="inline-flex items-center rounded-s-lg border border-e-0 border-line-strong bg-canvas px-3 text-sm text-muted">{{ $currency }}</span>
                                        <input id="price" type="number" step="0.01" min="0" inputmode="decimal" name="variants[0][price]" x-model="variants[0].price" required dir="ltr" class="admin-field rounded-s-none" :class="err('variants.0.price') && 'admin-field-error'">
                                    </div>
                                    <p x-show="err('variants.0.price')" x-text="err('variants.0.price')" class="text-[13px] text-danger-600"></p>
                                </div>
                                <div class="space-y-1.5">
                                    <x-admin.label for="compare_at_price">{{ __('admin.products.fields.compare_at_price') }}</x-admin.label>
                                    <div class="flex">
                                        <span class="inline-flex items-center rounded-s-lg border border-e-0 border-line-strong bg-canvas px-3 text-sm text-muted">{{ $currency }}</span>
                                        <input id="compare_at_price" type="number" step="0.01" min="0" inputmode="decimal" name="variants[0][compare_at_price]" x-model="variants[0].compare_at_price" dir="ltr" class="admin-field rounded-s-none" :class="err('variants.0.compare_at_price') && 'admin-field-error'">
                                    </div>
                                    <p x-show="err('variants.0.compare_at_price')" x-text="err('variants.0.compare_at_price')" class="text-[13px] text-danger-600"></p>
                                    <p x-show="! err('variants.0.compare_at_price')" class="text-[13px] text-muted">{{ __('admin.products.fields.compare_at_help') }}</p>
                                </div>
                                <div class="space-y-1.5">
                                    <x-admin.label for="cost_price">{{ __('admin.products.fields.cost_price') }}</x-admin.label>
                                    <div class="flex">
                                        <span class="inline-flex items-center rounded-s-lg border border-e-0 border-line-strong bg-canvas px-3 text-sm text-muted">{{ $currency }}</span>
                                        <input id="cost_price" type="number" step="0.01" min="0" inputmode="decimal" name="variants[0][cost_price]" x-model="variants[0].cost_price" dir="ltr" class="admin-field rounded-s-none">
                                    </div>
                                    <p class="text-[13px] text-muted">{{ __('admin.products.fields.cost_help') }}</p>
                                </div>
                                <div x-show="profit(variants[0])" class="self-end rounded-lg bg-canvas px-4 py-3 text-sm">
                                    <span class="text-muted">{{ __('admin.products.profit') }}</span>
                                    <span class="font-medium" dir="ltr" x-text="profit(variants[0]) && (currency + ' ' + profit(variants[0]).profit)"></span>
                                    <span class="text-muted">· {{ __('admin.products.margin') }}</span>
                                    <span class="font-medium" x-text="profit(variants[0]) && (profit(variants[0]).margin + '%')"></span>
                                </div>
                            </div>
                        </x-admin.card>

                        <x-admin.card :title="__('admin.products.inventory_title')">
                            <div class="space-y-5">
                                <div class="grid gap-5 sm:grid-cols-2">
                                    <div class="space-y-1.5">
                                        <x-admin.label for="sku">{{ __('admin.products.fields.sku') }}</x-admin.label>
                                        <input id="sku" type="text" name="variants[0][sku]" x-model="variants[0].sku" maxlength="64" dir="ltr" class="admin-field" :class="err('variants.0.sku') && 'admin-field-error'">
                                        <p x-show="err('variants.0.sku')" x-text="err('variants.0.sku')" class="text-[13px] text-danger-600"></p>
                                    </div>
                                    <div class="space-y-1.5">
                                        <x-admin.label for="barcode">{{ __('admin.products.fields.barcode') }}</x-admin.label>
                                        <input id="barcode" type="text" name="variants[0][barcode]" x-model="variants[0].barcode" maxlength="64" dir="ltr" class="admin-field">
                                    </div>
                                </div>

                                <label class="flex items-center gap-3 text-sm">
                                    <input type="checkbox" x-model="variants[0].track_inventory" class="size-4 accent-brand-600">
                                    {{ __('admin.products.fields.track_inventory') }}
                                </label>
                                <input type="hidden" name="variants[0][track_inventory]" :value="variants[0].track_inventory ? 1 : 0">

                                <div x-show="variants[0].track_inventory" class="grid gap-5 sm:grid-cols-2">
                                    <div class="space-y-1.5">
                                        <x-admin.label for="stock">{{ __('admin.products.fields.stock') }}</x-admin.label>
                                        <input id="stock" type="number" step="1" name="variants[0][stock_quantity]" x-model="variants[0].stock_quantity" dir="ltr" class="admin-field" :class="err('variants.0.stock_quantity') && 'admin-field-error'">
                                        <p x-show="err('variants.0.stock_quantity')" x-text="err('variants.0.stock_quantity')" class="text-[13px] text-danger-600"></p>
                                    </div>
                                    <label class="flex items-center gap-3 self-end pb-2 text-sm">
                                        <input type="checkbox" x-model="variants[0].allow_backorder" class="size-4 accent-brand-600">
                                        {{ __('admin.products.fields.allow_backorder') }}
                                    </label>
                                </div>
                                <input type="hidden" name="variants[0][allow_backorder]" :value="variants[0].allow_backorder ? 1 : 0">
                                <input type="hidden" name="variants[0][original_stock]" :value="variants[0].original_stock ?? ''">
                                <input type="hidden" name="variants[0][id]" :value="variants[0].id ?? ''">
                                <input type="hidden" name="variants[0][is_active]" value="1">
                            </div>
                        </x-admin.card>

                        <x-admin.card :title="__('admin.products.shipping_title')">
                            <div class="max-w-xs space-y-1.5">
                                <x-admin.label for="weight">{{ __('admin.products.fields.weight') }}</x-admin.label>
                                <div class="flex">
                                    <input id="weight" type="number" step="1" min="0" name="variants[0][weight_grams]" x-model="variants[0].weight_grams" dir="ltr" class="admin-field rounded-e-none">
                                    <span class="inline-flex items-center rounded-e-lg border border-s-0 border-line-strong bg-canvas px-3 text-sm text-muted">{{ __('admin.products.grams') }}</span>
                                </div>
                                <p class="text-[13px] text-muted">{{ __('admin.products.fields.weight_help') }}</p>
                            </div>
                        </x-admin.card>
                    </div>
                </template>

                {{-- Options and variants --}}
                <x-admin.card data-tour="product-options" :title="__('admin.products.options_title')" :description="__('admin.products.options_text')">
                    <div class="space-y-4">
                        <template x-for="(option, oi) in options" :key="option.key">
                            <div class="rounded-lg border border-line p-4">
                                <input type="hidden" :name="`options[${oi}][id]`" :value="option.id ?? ''">
                                <input type="hidden" :name="`options[${oi}][key]`" :value="option.key">

                                <div class="flex items-start gap-3">
                                    <div class="grid flex-1 gap-3 {{ $showArabic ? 'sm:grid-cols-2' : '' }}">
                                        <label class="block space-y-1.5">
                                            <span class="block text-sm font-medium">{{ __('admin.products.fields.option_name') }} <span class="font-normal text-muted">(English)</span></span>
                                            <input type="text" :name="`options[${oi}][name][en]`" x-model="option.name.en" maxlength="50" placeholder="{{ __('admin.products.option_placeholder') }}" class="admin-field" :class="err(`options.${oi}.name.en`) && 'admin-field-error'">
                                        </label>
                                        @if ($showArabic)
                                        <label class="block space-y-1.5">
                                            <span class="block text-sm font-medium" lang="ar">الاسم <span class="font-normal text-muted">(العربية)</span></span>
                                            <input type="text" :name="`options[${oi}][name][ar]`" x-model="option.name.ar" maxlength="50" dir="rtl" lang="ar" placeholder="المقاس" class="admin-field">
                                        </label>
                                        @endif
                                    </div>
                                    <button type="button" @click="removeOption(oi)" class="mt-7 rounded-lg p-2 text-muted hover:bg-danger-50 hover:text-danger-600" aria-label="{{ __('admin.products.remove_option') }}">
                                        <x-icon name="trash-2" class="size-4" />
                                    </button>
                                </div>

                                <p class="mb-2 mt-4 text-sm font-medium">{{ __('admin.products.fields.option_values') }}</p>
                                <p x-show="err(`options.${oi}.values`)" x-text="err(`options.${oi}.values`)" class="mb-2 text-[13px] text-danger-600"></p>
                                <ul class="space-y-2">
                                    <template x-for="(value, vi) in option.values" :key="value.key">
                                        <li class="flex items-center gap-2">
                                            <input type="hidden" :name="`options[${oi}][values][${vi}][id]`" :value="value.id ?? ''">
                                            <input type="hidden" :name="`options[${oi}][values][${vi}][key]`" :value="value.key">
                                            <input type="text" :name="`options[${oi}][values][${vi}][value][en]`" x-model="value.value.en" @change="regenerate()" maxlength="60"
                                                   :aria-label="`{{ __('admin.products.fields.option_value') }} ${vi + 1} (English)`" placeholder="{{ __('admin.products.value_placeholder') }}"
                                                   class="admin-field" :class="err(`options.${oi}.values.${vi}.value.en`) && 'admin-field-error'">
                                            @if ($showArabic)
                                            <input type="text" :name="`options[${oi}][values][${vi}][value][ar]`" x-model="value.value.ar" maxlength="60" dir="rtl" lang="ar"
                                                   :aria-label="`القيمة ${vi + 1} (العربية)`" placeholder="صغير" class="admin-field">
                                            @endif
                                            <button type="button" @click="removeValue(option, vi)" class="shrink-0 rounded-lg p-2 text-muted hover:bg-ink/5 hover:text-ink" aria-label="{{ __('admin.products.remove_value') }}">
                                                <x-icon name="x" class="size-4" />
                                            </button>
                                        </li>
                                    </template>
                                </ul>
                                <button type="button" @click="addValue(option)" class="mt-3 inline-flex items-center gap-1.5 text-sm font-medium text-brand-600 hover:underline">
                                    <x-icon name="plus" class="size-4" /> {{ __('admin.products.add_value') }}
                                </button>
                            </div>
                        </template>

                        <x-admin.button type="button" variant="secondary" icon="plus" x-show="options.length < 3" x-on:click="addOption()">
                            <span x-text="options.length ? @js(__('admin.products.add_another_option')) : @js(__('admin.products.add_option'))"></span>
                        </x-admin.button>
                        <p x-show="err('options')" x-text="err('options')" class="text-[13px] text-danger-600"></p>
                        <x-admin.alert tone="warning" x-show="tooMany" x-cloak>{{ __('admin.products.too_many_variants', ['max' => \App\Http\Requests\Admin\Catalog\ProductRequest::MAX_VARIANTS]) }}</x-admin.alert>

                        {{-- Variants list --}}
                        <template x-if="hasOptions()">
                            <div class="overflow-hidden rounded-lg border border-line">
                                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-line bg-canvas/60 px-4 py-2.5">
                                    <p class="text-sm font-medium" x-text="@js(__('admin.products.variants_count')).replace(':count', variants.length)"></p>
                                    <div class="flex gap-3 text-[13px]">
                                        <button type="button" @click="applyToAll('price')" class="text-brand-600 hover:underline">{{ __('admin.products.same_price') }}</button>
                                        <button type="button" @click="applyToAll('stock_quantity')" class="text-brand-600 hover:underline">{{ __('admin.products.same_stock') }}</button>
                                    </div>
                                </div>
                                <p x-show="err('variants')" x-text="err('variants')" class="px-4 pt-3 text-[13px] text-danger-600"></p>

                                <ul class="divide-y divide-line">
                                    <template x-for="(variant, i) in variants" :key="variant.option_keys.join('|') || i">
                                        <li class="px-4 py-3">
                                            <template x-for="key in variant.option_keys" :key="key">
                                                <input type="hidden" :name="`variants[${i}][option_keys][]`" :value="key">
                                            </template>
                                            <input type="hidden" :name="`variants[${i}][id]`" :value="variant.id ?? ''">
                                            <input type="hidden" :name="`variants[${i}][original_stock]`" :value="variant.original_stock ?? ''">
                                            <input type="hidden" :name="`variants[${i}][track_inventory]`" :value="variant.track_inventory ? 1 : 0">
                                            <input type="hidden" :name="`variants[${i}][allow_backorder]`" :value="variant.allow_backorder ? 1 : 0">
                                            <input type="hidden" :name="`variants[${i}][is_active]`" :value="variant.is_active ? 1 : 0">
                                            <input type="hidden" :name="`variants[${i}][media_id]`" :value="variant.media_id ?? ''">

                                            <div class="grid grid-cols-2 items-end gap-3 md:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)_minmax(0,1fr)_5.5rem_auto]">
                                                <div class="col-span-2 flex min-w-0 items-center gap-3 md:col-span-1 md:self-center">
                                                    <span class="flex size-9 shrink-0 items-center justify-center overflow-hidden rounded-md border border-line bg-canvas">
                                                        <img x-show="variantImage(variant)" :src="variantImage(variant)" alt="" class="size-full object-cover">
                                                        <x-icon name="image" class="size-4 text-muted" x-show="! variantImage(variant)" />
                                                    </span>
                                                    <span class="min-w-0">
                                                        <span class="block truncate text-sm font-medium" x-text="variantLabel(variant)"></span>
                                                        <span x-show="! variant.is_active" class="text-[12px] text-muted">{{ __('admin.products.variant_hidden') }}</span>
                                                    </span>
                                                </div>
                                                <label class="block space-y-1">
                                                    <span class="block text-[12px] text-muted">{{ __('admin.products.fields.price') }} ({{ $currency }})</span>
                                                    <input type="number" step="0.01" min="0" inputmode="decimal" :name="`variants[${i}][price]`" x-model="variant.price" dir="ltr" class="admin-field py-1.5" :class="err(`variants.${i}.price`) && 'admin-field-error'">
                                                </label>
                                                <label class="block space-y-1">
                                                    <span class="block text-[12px] text-muted">{{ __('admin.products.fields.sku') }}</span>
                                                    <input type="text" :name="`variants[${i}][sku]`" x-model="variant.sku" maxlength="64" dir="ltr" class="admin-field py-1.5" :class="err(`variants.${i}.sku`) && 'admin-field-error'">
                                                </label>
                                                <label class="block space-y-1">
                                                    <span class="block text-[12px] text-muted">{{ __('admin.products.fields.stock_short') }}</span>
                                                    <input type="number" step="1" :name="`variants[${i}][stock_quantity]`" x-model="variant.stock_quantity" :disabled="! variant.track_inventory" dir="ltr" class="admin-field py-1.5">
                                                </label>
                                                <button type="button" @click="variant.open = ! variant.open" :aria-expanded="variant.open" class="justify-self-end rounded-lg p-2 text-muted hover:bg-ink/5 hover:text-ink" aria-label="{{ __('admin.products.more_details') }}">
                                                    <x-icon name="chevron-down" class="size-4 transition" x-bind:class="variant.open && 'rotate-180'" />
                                                </button>
                                            </div>

                                            <template x-for="field in ['price', 'compare_at_price', 'sku', 'stock_quantity', 'cost_price', 'weight_grams']" :key="field">
                                                <p x-show="err(`variants.${i}.${field}`)" x-text="err(`variants.${i}.${field}`)" class="mt-1 text-[13px] text-danger-600"></p>
                                            </template>

                                            <div x-show="variant.open" x-collapse class="mt-4 grid gap-4 rounded-lg bg-canvas/60 p-4 sm:grid-cols-2 lg:grid-cols-3">
                                                <label class="block space-y-1">
                                                    <span class="block text-[12px] text-muted">{{ __('admin.products.fields.compare_at_price') }} ({{ $currency }})</span>
                                                    <input type="number" step="0.01" min="0" :name="`variants[${i}][compare_at_price]`" x-model="variant.compare_at_price" dir="ltr" class="admin-field py-1.5">
                                                </label>
                                                <label class="block space-y-1">
                                                    <span class="block text-[12px] text-muted">{{ __('admin.products.fields.cost_price') }} ({{ $currency }})</span>
                                                    <input type="number" step="0.01" min="0" :name="`variants[${i}][cost_price]`" x-model="variant.cost_price" dir="ltr" class="admin-field py-1.5">
                                                </label>
                                                <label class="block space-y-1">
                                                    <span class="block text-[12px] text-muted">{{ __('admin.products.fields.barcode') }}</span>
                                                    <input type="text" :name="`variants[${i}][barcode]`" x-model="variant.barcode" maxlength="64" dir="ltr" class="admin-field py-1.5">
                                                </label>
                                                <label class="block space-y-1">
                                                    <span class="block text-[12px] text-muted">{{ __('admin.products.fields.weight') }} ({{ __('admin.products.grams') }})</span>
                                                    <input type="number" step="1" min="0" :name="`variants[${i}][weight_grams]`" x-model="variant.weight_grams" dir="ltr" class="admin-field py-1.5">
                                                </label>
                                                <label class="block space-y-1">
                                                    <span class="block text-[12px] text-muted">{{ __('admin.products.fields.variant_image') }}</span>
                                                    <select x-model="variant.media_id" class="admin-field py-1.5">
                                                        <option value="">{{ __('admin.products.no_variant_image') }}</option>
                                                        <template x-for="(item, n) in media" :key="item.id">
                                                            <option :value="String(item.id)" :selected="Number(variant.media_id) === item.id" x-text="@js(__('admin.products.image_n')).replace(':n', n + 1)"></option>
                                                        </template>
                                                    </select>
                                                </label>
                                                <div class="space-y-2 text-sm sm:col-span-2 lg:col-span-1">
                                                    <label class="flex items-center gap-2"><input type="checkbox" x-model="variant.track_inventory" class="size-4 accent-brand-600"> {{ __('admin.products.fields.track_inventory') }}</label>
                                                    <label class="flex items-center gap-2"><input type="checkbox" x-model="variant.allow_backorder" class="size-4 accent-brand-600"> {{ __('admin.products.fields.allow_backorder') }}</label>
                                                    <label class="flex items-center gap-2"><input type="checkbox" x-model="variant.is_active" class="size-4 accent-brand-600"> {{ __('admin.products.fields.variant_active') }}</label>
                                                </div>
                                            </div>
                                        </li>
                                    </template>
                                </ul>
                            </div>
                        </template>
                    </div>
                </x-admin.card>

                {{-- Search engine listing: a preview of how Google shows the product, fields behind "Edit". --}}
                @php
                    $seoTitle = $product->getTranslation('seo_title', 'en', false) ?: ($product->getTranslation('name', 'en', false) ?: __('admin.products.add'));
                    $seoText = $product->getTranslation('seo_description', 'en', false) ?: \Illuminate\Support\Str::limit(strip_tags((string) ($product->getTranslation('short_description', 'en', false) ?: $product->getTranslation('description', 'en', false))), 160);
                    $seoOpen = $errors->hasAny(['seo_title.*', 'seo_description.*', 'slug']) || ! $product->exists;
                @endphp
                <x-admin.card :title="__('admin.products.seo_title')">
                    <div x-data="{ editing: @js($seoOpen) }">
                        <div x-show="! editing">
                            <p class="text-xs text-muted">{{ settings()->trans('general.store_name') ?? config('app.name') }} · <span dir="ltr">{{ parse_url(config('app.url'), PHP_URL_HOST) }} › products › {{ $product->slug ?: '…' }}</span></p>
                            <p class="mt-0.5 truncate text-[17px] leading-6 text-[#1a0dab]">{{ $seoTitle }}</p>
                            <p class="mt-0.5 line-clamp-2 text-[13px] text-ink-soft">{{ $seoText ?: __('admin.products.seo_empty') }}</p>
                            <button type="button" @click="editing = true" class="mt-2 text-[13px] font-medium text-brand-600 hover:underline">{{ __('admin.products.seo_edit') }}</button>
                        </div>
                        <div x-show="editing" x-cloak>
                            <p class="mb-4 text-[13px] text-muted">{{ __('admin.products.seo_text') }}</p>
                            <div class="space-y-5">
                        <x-admin.translatable-input name="seo_title" :label="__('admin.products.fields.seo_title')" :values="$product->getTranslations('seo_title')" maxlength="70" :help="__('admin.products.fields.seo_title_help')" />
                        <x-admin.translatable-input name="seo_description" :label="__('admin.products.fields.seo_description')" :values="$product->getTranslations('seo_description')" multiline :rows="2" maxlength="160" />
                        <x-admin.input name="slug" :label="__('admin.common.url_handle')" :value="$product->slug" maxlength="190" dir="ltr" prefix="/products/" :help="__('admin.common.url_handle_help')" />
                    </div>
                        </div>
                    </div>
                </x-admin.card>
            </div>

            {{-- ===== Side column ===== --}}
            <div class="min-w-0 space-y-4">
                <x-admin.card data-tour="product-status" :title="__('admin.common.status')">
                    <div class="space-y-4">
                        <x-admin.select name="status" :options="\App\Enums\ProductStatus::options()" :value="$status" :help="__('admin.products.status_help')" aria-label="{{ __('admin.common.status') }}" />
                        <div class="border-t border-line pt-3">
                            <p class="text-xs font-medium text-muted">{{ __('admin.nav.sales_channels') }}</p>
                            <p class="mt-1 flex items-center gap-2 text-[13px]"><span @class(['size-2 rounded-full', 'bg-success-600' => $status === 'active', 'bg-line-strong' => $status !== 'active'])></span>{{ __('admin.nav.online_store') }}</p>
                        </div>
                        <x-admin.toggle name="is_featured" :label="__('admin.products.fields.featured')" :description="__('admin.products.fields.featured_help')" :checked="$product->is_featured" />
                    </div>
                </x-admin.card>

                <x-admin.card :title="__('admin.products.organisation_title')">
                    <div class="space-y-5">
                        <fieldset>
                            <legend class="mb-2 text-sm font-medium">{{ __('admin.nav.categories') }}</legend>
                            @if ($categories->isEmpty())
                                <p class="text-[13px] text-muted">
                                    {{ __('admin.products.no_categories') }}
                                    <a href="{{ route('admin.categories.create') }}" class="font-medium text-brand-600 hover:underline">{{ __('admin.categories.add') }}</a>
                                </p>
                            @else
                                <div class="max-h-64 space-y-1 overflow-y-auto rounded-lg border border-line p-2">
                                    @foreach ($categories as $category)
                                        <label class="flex items-center gap-2 rounded-md px-2 py-1.5 text-sm hover:bg-canvas" style="padding-inline-start: {{ 0.5 + $category->depth * 1.25 }}rem">
                                            <input type="checkbox" name="category_ids[]" value="{{ $category->id }}" @checked($selectedCategories->contains($category->id)) class="size-4 accent-brand-600">
                                            <span @class(['text-muted' => ! $category->is_active])>{{ $category->name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            @endif
                            <x-admin.field-error name="category_ids" />
                        </fieldset>
                        {{-- "Configure your piece": the choices a customer picks before adding
                             to the bag. Unlike options these do not create variants. --}}
                        <div x-data="{ rows: @js($configurationRows) }" class="mb-6">
                            <p class="mb-1 text-[13px] font-medium">{{ __('admin.products.fields.configuration') }}</p>
                            <p class="mb-3 text-xs text-muted">{{ __('admin.products.fields.configuration_help') }}</p>

                            <template x-for="(row, ri) in rows" :key="ri">
                                <div class="mb-2 flex flex-wrap items-start gap-2">
                                    <input type="text" :name="`configuration[${ri}][label]`" x-model="row.label" maxlength="40"
                                           placeholder="{{ __('admin.products.fields.configuration_label') }}" class="admin-field w-40">
                                    <input type="text" :name="`configuration[${ri}][values]`" x-model="row.values" maxlength="400"
                                           placeholder="{{ __('admin.products.fields.configuration_values') }}" class="admin-field min-w-0 flex-1">
                                    <button type="button" @click="rows.splice(ri, 1)" class="rounded-lg p-2 text-muted hover:bg-danger-50 hover:text-danger-600" aria-label="{{ __('admin.common.delete') }}">
                                        <x-icon name="trash-2" class="size-4" />
                                    </button>
                                </div>
                            </template>

                            <button type="button" @click="rows.push({ label: '', values: '' })" class="mt-1 inline-flex items-center gap-1.5 text-[13px] font-medium text-brand-600">
                                <x-icon name="plus" class="size-4" /> {{ __('admin.products.fields.configuration_add') }}
                            </button>
                        </div>

                        <div>
                            <label for="brand_id" class="mb-1 block text-[13px] font-medium">{{ __('admin.products.fields.brand') }}</label>
                            <select id="brand_id" name="brand_id" class="admin-field">
                                <option value="">{{ __('admin.products.fields.brand_none') }}</option>
                                @foreach (\App\Models\Brand::query()->orderBy('position')->orderBy('name')->get() as $brandOption)
                                    <option value="{{ $brandOption->id }}" @selected((int) old('brand_id', $product->brand_id) === $brandOption->id)>{{ $brandOption->name }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-muted">
                                {{ __('admin.products.fields.brand_help') }}
                                <a href="{{ route('admin.brands.index') }}" class="underline">{{ __('admin.brands.title') }}</a>
                            </p>
                            @error('brand_id')<p class="mt-1 text-xs text-danger-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </x-admin.card>

                @if ($product->exists)
                    <x-admin.card>
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-[13px] text-muted">{{ __('admin.common.last_updated', ['date' => $product->updated_at->timezone(config('store.display_timezone'))->translatedFormat('j M Y, H:i')]) }}</p>
                        </div>
                    </x-admin.card>
                @endif
            </div>
        </div>

        @include('admin.settings._save-bar', ['always' => ! $product->exists, 'label' => __('admin.products.save')])
        @include('admin.catalog.partials.media-library-dialog')
    </form>

    @if ($product->exists)
        <div class="mt-6 flex justify-start">
            <x-admin.confirm-delete :action="route('admin.products.destroy', $product)" :label="__('admin.products.delete')"
                :title="__('admin.products.delete_title', ['name' => $product->name])" :message="__('admin.products.delete_text')" />
        </div>
    @endif
@endsection
