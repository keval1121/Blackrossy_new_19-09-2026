@extends('layouts.admin')
@section('title', isset($product) ? 'Edit product' : 'Add product')
@section('content')
{{--
  IMPORTANT: do not nest image Remove/Primary forms inside the product save form.
  Nested forms are invalid HTML; browsers merge them and Remove can fire product DELETE.
--}}
<form id="product-form" method="post" enctype="multipart/form-data" action="{{ isset($product) ? route('admin.products.update', $product) : route('admin.products.store') }}" class="space-y-6">
    @csrf
    @isset($product) @method('PUT') @endisset

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <section class="admin-card">
                <h2 class="admin-card-title">Basic info</h2>
                <p class="admin-card-help">Name, SKU and descriptions shown on the storefront.</p>
                <div class="mt-4 space-y-3">
                    <div>
                        <label class="admin-label">Product name</label>
                        <input class="admin-input" name="name" value="{{ old('name', $product->name ?? '') }}" required>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="admin-label">Slug (optional)</label>
                            <input class="admin-input" name="slug" value="{{ old('slug', $product->slug ?? '') }}" placeholder="auto from name">
                        </div>
                        <div>
                            <label class="admin-label">SKU</label>
                            <input class="admin-input" name="sku" value="{{ old('sku', $product->sku ?? '') }}">
                        </div>
                    </div>
                    <div>
                        <label class="admin-label">Short description</label>
                        <textarea class="admin-input min-h-20" name="short_description" placeholder="Shown next to the product image">{{ old('short_description', $product->short_description ?? '') }}</textarea>
                    </div>
                    <div>
                        <label class="admin-label">Full description</label>
                        <textarea class="admin-input min-h-40" name="description">{{ old('description', $product->description ?? '') }}</textarea>
                    </div>
                    <div>
                        <label class="admin-label">Specifications <span class="font-normal text-stone-400">(Label: Value, one per line)</span></label>
                        <textarea class="admin-input min-h-24" name="specifications" placeholder="Brand: Black Rossy&#10;Care: Gentle wash">{{ old('specifications', isset($product) ? collect($product->specifications)->map(fn ($s) => $s['label'].': '.$s['value'])->implode("\n") : '') }}</textarea>
                    </div>
                    <div>
                        <label class="admin-label">Tags</label>
                        <input class="admin-input" name="tags" value="{{ old('tags', isset($product) ? $product->tags->pluck('name')->implode(', ') : '') }}" placeholder="home, decor, cushion">
                    </div>
                </div>
            </section>

            <section class="admin-card">
                <h2 class="admin-card-title">Add images</h2>
                <p class="admin-card-help">JPG / PNG / WebP, max 4MB each, up to 8 files. Click Save product after choosing files.</p>
                <div class="mt-4">
                    <input type="file" name="images[]" multiple accept="image/jpeg,image/png,image/webp" class="admin-input">
                </div>
            </section>

            @php
                $defaultSizeVariants = [
                    ['label' => 'Size: S', 'sku' => '001', 'stock' => '200'],
                    ['label' => 'Size: M', 'sku' => '002', 'stock' => '200'],
                    ['label' => 'Size: L', 'sku' => '003', 'stock' => '200'],
                    ['label' => 'Size: XL', 'sku' => '004', 'stock' => '200'],
                    ['label' => 'Size: XXL', 'sku' => '005', 'stock' => '200'],
                ];
                $variantRows = old('variants');
                if ($variantRows === null) {
                    if (isset($product)) {
                        $variantRows = $product->variants->values()->map(fn ($variant) => [
                            'id' => $variant->id,
                            'label' => $variant->values->map(fn ($v) => $v->attribute->name.': '.$v->attributeValue->value)->implode(', '),
                            'sku' => $variant->sku,
                            'stock' => $variant->stock,
                        ])->all();
                        if ($variantRows === []) {
                            $variantRows = $defaultSizeVariants;
                        }
                    } else {
                        $variantRows = $defaultSizeVariants;
                    }
                }
            @endphp
            <section id="variants" class="admin-card scroll-mt-6">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="admin-card-title">Variants</h2>
                        <p class="admin-card-help">Default sizes S–XXL are prefilled — edit SKU/stock or add colors as needed. Example: <code class="rounded bg-stone-100 px-1">Size: M, Color: Black</code></p>
                    </div>
                    <button type="button" id="add-variant-row" class="admin-btn admin-btn-ghost shrink-0 text-sm">+ Add variant</button>
                </div>
                <div class="mt-4 overflow-x-auto">
                    <table class="variant-editor">
                        <thead>
                            <tr>
                                <th class="variant-col-label">Variant</th>
                                <th class="variant-col-sku">SKU</th>
                                <th class="variant-col-stock">Stock</th>
                                <th class="variant-col-action"></th>
                            </tr>
                        </thead>
                        <tbody id="variant-rows">
                            @foreach($variantRows as $i => $row)
                                <tr class="variant-row">
                                    <td class="variant-col-label">
                                        @if(! empty($row['id']))
                                            <input type="hidden" name="variants[{{ $i }}][id]" value="{{ $row['id'] }}">
                                        @endif
                                        <input class="admin-input" name="variants[{{ $i }}][label]" value="{{ $row['label'] ?? '' }}" placeholder="Size:M, Color:Black">
                                    </td>
                                    <td class="variant-col-sku">
                                        <input class="admin-input" name="variants[{{ $i }}][sku]" value="{{ $row['sku'] ?? '' }}" placeholder="SKU">
                                    </td>
                                    <td class="variant-col-stock">
                                        <input class="admin-input" name="variants[{{ $i }}][stock]" value="{{ $row['stock'] ?? '' }}" placeholder="0" inputmode="numeric">
                                    </td>
                                    <td class="variant-col-action">
                                        <button type="button" class="admin-btn admin-btn-ghost remove-variant-row" title="Remove variant" aria-label="Remove variant">&times;</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <div class="space-y-6">
            <section class="admin-card">
                <h2 class="admin-card-title">Organisation</h2>
                <div class="mt-4 space-y-3">
                    <div>
                        <label class="admin-label">Category</label>
                        <select class="admin-input" name="category_id" required>
                            <option value="">Select category</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id ?? '') == $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="admin-label">Sub-category</label>
                        <select class="admin-input" name="sub_category_id">
                            <option value="">None</option>
                            @foreach($categories as $category)
                                @foreach($category->children as $child)
                                    <option value="{{ $child->id }}" @selected(old('sub_category_id', $product->sub_category_id ?? '') == $child->id)>{{ $category->name }} / {{ $child->name }}</option>
                                @endforeach
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="admin-label">Brand</label>
                        <select class="admin-input" name="brand_id">
                            <option value="">None</option>
                            @foreach($brands as $brand)
                                <option value="{{ $brand->id }}" @selected(old('brand_id', $product->brand_id ?? '') == $brand->id)>{{ $brand->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="admin-label">Status</label>
                        <select class="admin-input" name="status">
                            @foreach(['active' => 'Active', 'inactive' => 'Inactive', 'draft' => 'Draft'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', $product->status?->value ?? 'active') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </section>

            <section class="admin-card">
                <h2 class="admin-card-title">Pricing &amp; stock</h2>
                <div class="mt-4 space-y-3">
                    <div>
                        <label class="admin-label">MRP</label>
                        <input class="admin-input" name="mrp" type="number" step="0.01" value="{{ old('mrp', $product->mrp ?? '') }}" required>
                    </div>
                    <div>
                        <label class="admin-label">Selling price</label>
                        <input class="admin-input" name="selling_price" type="number" step="0.01" value="{{ old('selling_price', $product->selling_price ?? '') }}" required>
                    </div>
                    <div>
                        <label class="admin-label">Stock quantity <span id="stock-auto-hint" class="hidden font-normal text-stone-400">(auto = sum of variants)</span></label>
                        <input id="stock_quantity" class="admin-input" name="stock_quantity" type="number" min="0" value="{{ old('stock_quantity', $product->stock_quantity ?? 0) }}" required>
                        <p id="stock-help" class="mt-1 text-xs text-stone-500">No variants: enter stock here. With variants: total is the sum of variant stocks.</p>
                    </div>
                </div>
            </section>

            <section class="admin-card">
                <h2 class="admin-card-title">Flags</h2>
                <div class="mt-4 space-y-2 text-sm">
                    <label class="flex items-center gap-2 rounded-xl border border-stone-200 px-3 py-2.5">
                        <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $product->is_featured ?? false))>
                        Featured
                    </label>
                    <label class="flex items-center gap-2 rounded-xl border border-stone-200 px-3 py-2.5">
                        <input type="checkbox" name="is_best_seller" value="1" @checked(old('is_best_seller', $product->is_best_seller ?? false))>
                        Best seller
                    </label>
                    <label class="flex items-center gap-2 rounded-xl border border-stone-200 px-3 py-2.5">
                        <input type="checkbox" name="is_new_arrival" value="1" @checked(old('is_new_arrival', $product->is_new_arrival ?? false))>
                        New arrival
                    </label>
                </div>
            </section>

            <section class="admin-card">
                <h2 class="admin-card-title">SEO</h2>
                <div class="mt-4 space-y-3">
                    <div>
                        <label class="admin-label">SEO title</label>
                        <input class="admin-input" name="seo_title" value="{{ old('seo_title', $product->seo_title ?? '') }}">
                    </div>
                    <div>
                        <label class="admin-label">SEO description</label>
                        <textarea class="admin-input min-h-24" name="seo_description">{{ old('seo_description', $product->seo_description ?? '') }}</textarea>
                    </div>
                </div>
            </section>

            <button type="submit" class="admin-btn admin-btn-primary w-full py-3.5">Save product</button>
        </div>
    </div>
</form>

@isset($product)
    <section class="admin-card mt-6">
        <h2 class="admin-card-title">Current images</h2>
        <p class="admin-card-help">Remove and Set as primary work immediately — they do not delete the product.</p>
        <div class="mt-4">
            @if($product->images->isNotEmpty())
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4">
                    @foreach($product->images as $image)
                        <div class="overflow-hidden rounded-xl border border-stone-200 bg-stone-50">
                            <div class="relative">
                                <img src="{{ $image->url('thumb') }}" alt="" class="aspect-square w-full object-cover">
                                @if($image->is_primary)
                                    <span class="absolute left-2 top-2 rounded-full bg-amber-500 px-2 py-0.5 text-[10px] font-semibold text-white">Primary</span>
                                @endif
                            </div>
                            <div class="flex flex-col gap-1.5 p-2">
                                @unless($image->is_primary)
                                    <form method="post" action="{{ route('admin.products.images.primary', [$product, $image]) }}">
                                        @csrf
                                        <button type="submit" class="admin-btn admin-btn-ghost w-full py-1.5 text-xs">Set as primary</button>
                                    </form>
                                @endunless
                                <form method="post" action="{{ route('admin.products.images.destroy', [$product, $image]) }}" onsubmit="return confirm('Remove this image only? The product will stay.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="admin-btn admin-btn-danger w-full py-1.5 text-xs">Remove image</button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="rounded-xl bg-stone-50 px-3 py-2 text-xs text-stone-500">No images yet — upload above and Save product.</p>
            @endif
        </div>
    </section>

    <form method="post" action="{{ route('admin.products.destroy', $product) }}" class="mt-6 rounded-xl border border-rose-200 bg-rose-50 p-4" onsubmit="return confirm('Delete this ENTIRE product permanently? This cannot be undone.')">
        @csrf
        @method('DELETE')
        <p class="mb-3 text-sm text-rose-800">Remove this product from the catalog permanently.</p>
        <button type="submit" class="admin-btn admin-btn-danger">Delete entire product</button>
    </form>
@endisset

<template id="variant-row-template">
    <tr class="variant-row">
        <td class="variant-col-label">
            <input class="admin-input" data-name="label" placeholder="Size:M, Color:Black">
        </td>
        <td class="variant-col-sku">
            <input class="admin-input" data-name="sku" placeholder="SKU">
        </td>
        <td class="variant-col-stock">
            <input class="admin-input" data-name="stock" placeholder="0" inputmode="numeric">
        </td>
        <td class="variant-col-action">
            <button type="button" class="admin-btn admin-btn-ghost remove-variant-row" title="Remove variant" aria-label="Remove variant">&times;</button>
        </td>
    </tr>
</template>

<script>
(() => {
    const list = document.getElementById('variant-rows');
    const template = document.getElementById('variant-row-template');
    const addBtn = document.getElementById('add-variant-row');
    const stockInput = document.getElementById('stock_quantity');
    const stockHint = document.getElementById('stock-auto-hint');
    if (!list || !template || !addBtn || !stockInput) return;

    const rowHasVariant = (row) => {
        const label = row.querySelector('input[name*="[label]"]')?.value?.trim() || '';
        const sku = row.querySelector('input[name*="[sku]"]')?.value?.trim() || '';
        return label !== '' || sku !== '';
    };

    const syncTotalStock = () => {
        const rows = [...list.querySelectorAll('.variant-row')].filter(rowHasVariant);
        const usingVariants = rows.length > 0;
        stockHint?.classList.toggle('hidden', !usingVariants);
        stockInput.readOnly = usingVariants;
        stockInput.classList.toggle('bg-stone-100', usingVariants);
        if (!usingVariants) return;
        const total = rows.reduce((sum, row) => {
            const value = parseInt(row.querySelector('input[name*="[stock]"]')?.value || '0', 10);
            return sum + (Number.isFinite(value) ? value : 0);
        }, 0);
        stockInput.value = String(total);
    };

    const reindex = () => {
        [...list.querySelectorAll('.variant-row')].forEach((row, index) => {
            row.querySelectorAll('input[name], input[data-name]').forEach((input) => {
                const field = input.dataset.name || (input.name.match(/\[([^\]]+)\]$/) || [])[1];
                if (!field) return;
                input.name = `variants[${index}][${field}]`;
                input.removeAttribute('data-name');
            });
        });
        syncTotalStock();
    };

    const ensureOneEmpty = () => {
        if (list.querySelectorAll('.variant-row').length === 0) {
            addBtn.click();
        }
    };

    addBtn.addEventListener('click', () => {
        const node = template.content.firstElementChild.cloneNode(true);
        list.appendChild(node);
        reindex();
        node.querySelector('input')?.focus();
    });

    list.addEventListener('click', (event) => {
        const btn = event.target.closest('.remove-variant-row');
        if (!btn) return;
        btn.closest('.variant-row')?.remove();
        reindex();
        ensureOneEmpty();
    });

    list.addEventListener('input', syncTotalStock);
    syncTotalStock();
})();
</script>
@endsection
