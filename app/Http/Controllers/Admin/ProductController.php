<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\ImageService;
use App\Services\ProductImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::query()
            ->with(['category', 'subCategory', 'brand', 'primaryImage', 'variants.values.attribute', 'variants.values.attributeValue'])
            ->when($request->category_id, function ($q, $id) {
                $q->where(function ($builder) use ($id) {
                    $builder->where('category_id', $id)->orWhere('sub_category_id', $id);
                });
            })
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->stock === 'low', fn ($q) => $q->where('stock_quantity', '<=', config('shop.low_stock_threshold')))
            ->when($request->stock === 'out', fn ($q) => $q->where('stock_quantity', 0))
            ->when($request->boolean('featured'), fn ($q) => $q->where('is_featured', true))
            ->when($request->boolean('best_seller'), fn ($q) => $q->where('is_best_seller', true))
            ->when($request->boolean('new_arrival'), fn ($q) => $q->where('is_new_arrival', true))
            ->when($request->q, fn ($q, $term) => $q->where(function ($builder) use ($term) {
                $builder->where('name', 'like', '%'.$term.'%')->orWhere('sku', 'like', '%'.$term.'%');
            }))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.products.index', [
            'products' => $products,
            'categories' => Category::query()->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.products.form', $this->formData());
    }

    public function store(Request $request, ImageService $images)
    {
        $product = Product::query()->create($this->validated($request));
        $product->syncTagsFromString($request->string('tags'));
        $this->syncImages($request, $product, $images);
        $this->syncVariants($request, $product);

        return redirect()->route('admin.products.index')->with('status', 'Product created.');
    }

    public function edit(Product $product)
    {
        $product->load(['images', 'variants.values.attribute', 'variants.values.attributeValue', 'tags']);

        return view('admin.products.form', $this->formData() + ['product' => $product]);
    }

    public function update(Request $request, Product $product, ImageService $images)
    {
        $product->update($this->validated($request, $product));
        $product->syncTagsFromString($request->string('tags'));
        $this->syncImages($request, $product, $images);
        $this->syncVariants($request, $product);

        return redirect()->route('admin.products.index')->with('status', 'Product updated.');
    }

    public function updateStock(Request $request, Product $product)
    {
        $product->load('variants');

        if ($product->variants->isNotEmpty()) {
            $data = $request->validate([
                'variants' => ['required', 'array', 'min:1'],
                'variants.*.id' => ['required', 'integer', Rule::exists('product_variants', 'id')->where('product_id', $product->id)],
                'variants.*.stock' => ['required', 'integer', 'min:0'],
            ]);

            foreach ($data['variants'] as $row) {
                $product->variants()->whereKey($row['id'])->update(['stock' => (int) $row['stock']]);
            }

            $product->recalculateStockFromVariants();

            return back()->with('status', 'Variant stock updated. Total stock is '.$product->fresh()->stock_quantity.'.');
        }

        $data = $request->validate([
            'stock_quantity' => ['required', 'integer', 'min:0'],
        ]);

        $product->update(['stock_quantity' => $data['stock_quantity']]);

        return back()->with('status', 'Stock updated.');
    }

    public function updateStatus(Request $request, Product $product)
    {
        // Checkbox posts "1" when on; hidden "0" when off — normalize before boolean validation.
        $request->merge([
            'active' => $request->boolean('active'),
        ]);

        $data = $request->validate([
            'active' => ['required', 'boolean'],
        ]);

        $product->update([
            'status' => $data['active'] ? ProductStatus::Active : ProductStatus::Inactive,
        ]);

        return back()->with('status', $data['active'] ? 'Product activated.' : 'Product deactivated.');
    }

    public function destroy(Product $product, ImageService $images)
    {
        foreach ($product->images as $image) {
            $images->deleteMany([$image->path_thumb, $image->path_medium, $image->path_large]);
        }
        $product->delete();

        return redirect()->route('admin.products.index')->with('status', 'Product deleted.');
    }

    public function destroyImage(Product $product, ProductImage $image, ImageService $images)
    {
        abort_unless($image->product_id === $product->id, 404);

        $wasPrimary = $image->is_primary;
        $images->deleteMany([$image->path_thumb, $image->path_medium, $image->path_large]);
        $image->delete();

        if ($wasPrimary) {
            $next = $product->images()->orderBy('display_order')->orderBy('id')->first();
            if ($next) {
                $next->update(['is_primary' => true]);
            }
        }

        return back()->with('status', 'Image removed.');
    }

    public function makePrimaryImage(Product $product, ProductImage $image)
    {
        abort_unless($image->product_id === $product->id, 404);

        $product->images()->update(['is_primary' => false]);
        $image->update(['is_primary' => true]);

        return back()->with('status', 'Primary image updated.');
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->validate(['ids' => ['required', 'array']])['ids'];
        Product::query()->whereIn('id', $ids)->delete();

        return back()->with('status', 'Selected products deleted.');
    }

    public function export()
    {
        $rows = Product::query()->with(['category', 'subCategory', 'brand', 'tags'])->get();
        $callback = function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['product_name', 'category', 'sub_category', 'sku', 'description', 'mrp', 'selling_price', 'stock', 'brand', 'tags', 'status']);
            foreach ($rows as $product) {
                fputcsv($out, [
                    $product->name,
                    $product->category?->name,
                    $product->subCategory?->name,
                    $product->sku,
                    $product->short_description,
                    $product->mrp,
                    $product->selling_price,
                    $product->stock_quantity,
                    $product->brand?->name,
                    $product->tags->pluck('name')->implode(','),
                    $product->status->value,
                ]);
            }
            fclose($out);
        };

        return response()->streamDownload($callback, 'products.csv', ['Content-Type' => 'text/csv']);
    }

    public function import(Request $request, ProductImportService $importer)
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:4096']]);
        $result = $importer->import($request->file('file')->getRealPath());

        if ($result['errors']) {
            return back()->withErrors(['import' => $result['errors']]);
        }

        return back()->with('status', $result['created'].' products imported.');
    }

    private function validated(Request $request, ?Product $product = null): array
    {
        // HTML checkboxes send "on" when checked — normalize before boolean validation.
        $request->merge([
            'is_featured' => $request->boolean('is_featured'),
            'is_best_seller' => $request->boolean('is_best_seller'),
            'is_new_arrival' => $request->boolean('is_new_arrival'),
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'slug' => ['nullable', 'string', 'max:180', Rule::unique('products', 'slug')->ignore($product?->id)],
            'category_id' => ['required', 'exists:categories,id'],
            'sub_category_id' => ['nullable', 'exists:categories,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'sku' => ['nullable', 'string', 'max:80', Rule::unique('products', 'sku')->ignore($product?->id)],
            'short_description' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'mrp' => ['required', 'numeric', 'min:1'],
            'selling_price' => ['required', 'numeric', 'min:1'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'min_order_qty' => ['nullable', 'integer', 'min:1'],
            'max_order_qty' => ['nullable', 'integer', 'min:1'],
            'status' => ['required', Rule::enum(ProductStatus::class)],
            'is_featured' => ['boolean'],
            'is_best_seller' => ['boolean'],
            'is_new_arrival' => ['boolean'],
            'video_url' => ['nullable', 'url'],
            'seo_title' => ['nullable', 'string', 'max:180'],
            'seo_description' => ['nullable', 'string', 'max:320'],
            'seo_keywords' => ['nullable', 'string', 'max:255'],
            'shipping_info' => ['nullable', 'string'],
            'return_info' => ['nullable', 'string'],
            'images' => ['nullable', 'array', 'max:8'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'primary_image_id' => ['nullable', 'integer'],
            'delete_images' => ['nullable', 'array'],
            'delete_images.*' => ['integer'],
        ]);

        $data['slug'] = ($data['slug'] ?? '') ?: Str::slug($data['name']);
        $data['min_order_qty'] = (int) ($data['min_order_qty'] ?? 1);
        if ($data['min_order_qty'] < 1) {
            $data['min_order_qty'] = 1;
        }

        if ($request->filled('specifications')) {
            $data['specifications'] = collect(explode("\n", $request->string('specifications')))
                ->map(function ($line) {
                    [$label, $value] = array_pad(explode(':', $line, 2), 2, null);

                    return $label && $value ? ['label' => trim($label), 'value' => trim($value)] : null;
                })
                ->filter()
                ->values()
                ->all();
        }

        // Image fields are handled in syncImages(), not mass-assigned on Product.
        unset($data['images'], $data['primary_image_id'], $data['delete_images']);

        return $data;
    }

    private function syncImages(Request $request, Product $product, ImageService $images): void
    {
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $file) {
                $paths = $images->storeProductImage($file, $product->id);
                $product->images()->create($paths + [
                    'alt' => $product->name,
                    'is_primary' => $product->images()->count() === 0 && $index === 0,
                    'display_order' => $product->images()->count() + $index,
                ]);
            }
        }

        if ($request->filled('primary_image_id')) {
            $product->images()->update(['is_primary' => false]);
            $product->images()->where('id', $request->integer('primary_image_id'))->update(['is_primary' => true]);
        }

        if ($request->filled('delete_images')) {
            $product->images()->whereIn('id', (array) $request->input('delete_images'))->each(function (ProductImage $image) use ($images) {
                $images->deleteMany([$image->path_thumb, $image->path_medium, $image->path_large]);
                $image->delete();
            });
        }
    }

    private function syncVariants(Request $request, Product $product): void
    {
        if (! $request->has('variants')) {
            return;
        }

        $rows = collect($request->input('variants', []))
            ->filter(fn ($row) => is_array($row) && (filled($row['label'] ?? null) || filled($row['sku'] ?? null)))
            ->values();

        $keep = [];
        foreach ($rows as $row) {
            $attributes = [
                'sku' => $row['sku'] ?? null,
                'price' => filled($row['price'] ?? null) ? $row['price'] : null,
                'mrp' => filled($row['mrp'] ?? null) ? $row['mrp'] : null,
                'stock' => (int) ($row['stock'] ?? 0),
                'is_active' => true,
            ];

            $existingId = filled($row['id'] ?? null) ? (int) $row['id'] : null;
            $variant = $existingId
                ? $product->variants()->whereKey($existingId)->first()
                : null;

            if ($variant) {
                $variant->update($attributes);
            } else {
                $variant = $product->variants()->create($attributes);
            }

            $keep[] = $variant->id;
            $variant->values()->delete();
            foreach (explode(',', (string) ($row['label'] ?? '')) as $pair) {
                if (! str_contains($pair, ':')) {
                    continue;
                }
                [$attrName, $valueName] = array_map('trim', explode(':', $pair, 2));
                if ($attrName === '' || $valueName === '') {
                    continue;
                }
                $attribute = Attribute::query()->firstOrCreate(
                    ['slug' => Str::slug($attrName)],
                    ['name' => $attrName, 'type' => 'select', 'is_filterable' => true]
                );
                $value = $attribute->values()->firstOrCreate(
                    ['slug' => Str::slug($valueName)],
                    ['value' => $valueName]
                );
                $variant->values()->create([
                    'attribute_id' => $attribute->id,
                    'attribute_value_id' => $value->id,
                ]);
            }
        }

        $product->variants()->whereNotIn('id', $keep)->delete();

        if ($keep !== []) {
            $product->recalculateStockFromVariants();
        }
    }

    private function formData(): array
    {
        return [
            'categories' => Category::query()->parents()->with('children')->orderBy('name')->get(),
            'brands' => Brand::query()->orderBy('name')->get(),
        ];
    }
}
