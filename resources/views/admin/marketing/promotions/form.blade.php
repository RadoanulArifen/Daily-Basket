@extends('admin.layouts.app')

@section('title', $promotion->exists ? $promotion->label() : __('admin.promotions.add'))

@section('content')
    @php
        $tz = config('store.display_timezone');
        $local = fn ($date) => $date?->timezone($tz)->format('Y-m-d\TH:i');
        $selected = collect(old('product_ids', $promotion->product_ids ?? []))->map(fn ($id) => (int) $id);
    @endphp

    <x-admin.page-header :title="$promotion->exists ? $promotion->label() : __('admin.promotions.add')" :back="route('admin.promotions.index')" />

    <form method="POST" action="{{ $promotion->exists ? route('admin.promotions.update', $promotion) : route('admin.promotions.store') }}"
          x-data="{ dirty: false, scope: @js(old('applies_to', $promotion->applies_to)), buy: {{ (int) old('buy_quantity', $promotion->buy_quantity) }}, get: {{ (int) old('get_quantity', $promotion->get_quantity) }}, pct: {{ (int) old('get_percent', $promotion->get_percent) }} }"
          @input="dirty = true" @change="dirty = true" class="space-y-8">
        @csrf
        @if ($promotion->exists) @method('PUT') @endif

        <x-admin.form-section :title="__('admin.promotions.deal_title')" :description="__('admin.promotions.deal_text')">
            <div class="grid gap-5 sm:grid-cols-3">
                <x-admin.input name="buy_quantity" type="number" min="1" max="20" :label="__('admin.promotions.fields.buy')" :value="$promotion->buy_quantity" x-model.number="buy" required />
                <x-admin.input name="get_quantity" type="number" min="1" max="20" :label="__('admin.promotions.fields.get')" :value="$promotion->get_quantity" x-model.number="get" required />
                <x-admin.input name="get_percent" type="number" min="1" max="100" :label="__('admin.promotions.fields.percent')" :value="$promotion->get_percent" suffix="%" x-model.number="pct" required :help="__('admin.promotions.percent_help')" />
            </div>
            <p class="rounded-lg bg-canvas px-4 py-3 text-sm">
                <span class="text-muted">{{ __('admin.promotions.preview') }}</span>
                <span class="font-medium" x-text="pct >= 100 ? @js(__('store.deals.summary_free')).replace(':buy', buy).replace(':get', get) : @js(__('store.deals.summary_percent')).replace(':buy', buy).replace(':get', get).replace(':percent', pct)"></span>
            </p>
            <x-admin.translatable-input name="name" :label="__('admin.promotions.fields.name')" :values="$promotion->getTranslations('name')" maxlength="80" :help="__('admin.promotions.name_help')" />
        </x-admin.form-section>

        <x-admin.form-section :title="__('admin.promotions.scope_title')" :description="__('admin.promotions.scope_text')">
            <div class="grid gap-2 sm:grid-cols-3">
                @foreach (\App\Models\Promotion::SCOPES as $scope)
                    <label class="flex cursor-pointer items-center gap-2 rounded-lg border px-3 py-2.5 text-sm" :class="scope === @js($scope) ? 'border-brand-600 bg-brand-50' : 'border-line-strong'">
                        <input type="radio" name="applies_to" value="{{ $scope }}" x-model="scope" class="accent-brand-600"> {{ __("admin.promotions.scopes.{$scope}") }}
                    </label>
                @endforeach
            </div>
            <div x-show="scope === 'category'" x-cloak>
                <x-admin.select name="category_id" :label="__('admin.promotions.fields.category')" :value="$promotion->category_id" :placeholder="__('admin.promotions.choose_category')"
                    :options="$categories->mapWithKeys(fn ($c) => [$c->id => str_repeat('— ', $c->depth).$c->name])->all()" :help="__('admin.promotions.category_help')" />
            </div>
            <fieldset x-show="scope === 'products'" x-cloak>
                <legend class="mb-2 text-sm font-medium">{{ __('admin.promotions.fields.products') }}</legend>
                <div class="max-h-72 space-y-1 overflow-y-auto rounded-lg border border-line p-2">
                    @foreach ($products as $product)
                        <label class="flex items-center gap-2 rounded-md px-2 py-1.5 text-sm hover:bg-canvas">
                            <input type="checkbox" name="product_ids[]" value="{{ $product->id }}" @checked($selected->contains($product->id)) class="size-4 accent-brand-600"> {{ $product->name }}
                        </label>
                    @endforeach
                </div>
                <x-admin.field-error name="product_ids" />
            </fieldset>
        </x-admin.form-section>

        <x-admin.form-section :title="__('admin.banners.schedule_title')" :description="__('admin.banners.schedule_text')">
            <div class="grid gap-5 sm:grid-cols-2">
                <x-admin.input name="starts_at" type="datetime-local" :label="__('admin.discounts.fields.starts_at')" :value="$local($promotion->starts_at)" />
                <x-admin.input name="ends_at" type="datetime-local" :label="__('admin.discounts.fields.ends_at')" :value="$local($promotion->ends_at)" />
            </div>
            <x-admin.toggle name="is_active" :label="__('admin.discounts.fields.active')" :checked="$promotion->is_active ?? true" />
        </x-admin.form-section>

        @include('admin.settings._save-bar', ['always' => ! $promotion->exists])
    </form>

    @if ($promotion->exists)
        <div class="mt-6"><x-admin.confirm-delete :action="route('admin.promotions.destroy', $promotion)" :label="__('admin.promotions.delete')" :title="__('admin.promotions.delete_title')" :message="__('admin.promotions.delete_text')" /></div>
    @endif
@endsection
