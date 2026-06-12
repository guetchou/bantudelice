<?php

namespace App\Services;

use App\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SubstitutionService
{
    public function isAvailable(Product $product): bool
    {
        if (Schema::hasColumn('products', 'is_available') && isset($product->is_available)) {
            return (bool) $product->is_available;
        }

        return true;
    }

    public function suggestForCart(Collection $items, int $limit = 4): Collection
    {
        return $items
            ->map(function ($item) use ($limit) {
                $product = $item instanceof Product
                    ? $item
                    : Product::with(['restaurants', 'categories'])->find($item->product_id ?? null);

                if (!$product || $this->isAvailable($product)) {
                    return null;
                }

                $basePrice = (float) ($product->discount_price > 0 ? $product->discount_price : $product->price);

                return [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'restaurant_id' => $product->restaurant_id ?? null,
                    'restaurant_name' => optional($product->restaurants)->name,
                    'category_id' => $product->category_id ?? null,
                    'size' => $product->size ?? null,
                    'qty' => (int) ($item->qty ?? 1),
                    'reason' => 'out_of_stock',
                    'message' => 'Ce produit est en rupture pour le moment',
                    'suggestions' => $this->suggestForProduct($product, $limit, [
                        'target_price' => $basePrice,
                        'target_size' => $product->size ?? null,
                    ])->values()->all(),
                ];
            })
            ->filter()
            ->values();
    }

    public function suggestForProduct(Product $product, int $limit = 4, array $context = []): Collection
    {
        $candidateLimit = max($limit * 3, 12);
        $targetPrice = (float) ($context['target_price'] ?? ($product->discount_price > 0 ? $product->discount_price : $product->price));
        $targetSize = trim((string) ($context['target_size'] ?? ($product->size ?? '')));

        $query = Product::query()
            ->with(['restaurants', 'categories'])
            ->where('id', '!=', $product->id);

        if (Schema::hasColumn('products', 'is_available')) {
            $query->where(function ($builder) {
                $builder->whereNull('is_available')
                    ->orWhere('is_available', true);
            });
        }

        if (!empty($product->restaurant_id)) {
            $query->where(function ($builder) use ($product) {
                $builder->where('restaurant_id', $product->restaurant_id);
                if (Schema::hasColumn('products', 'featured')) {
                    $builder->orWhere('featured', true);
                }
                if (Schema::hasColumn('products', 'is_featured')) {
                    $builder->orWhere('is_featured', true);
                }
                if (!empty($product->category_id)) {
                    $builder->orWhere('category_id', $product->category_id);
                }
                if (!empty($product->size) && Schema::hasColumn('products', 'size')) {
                    $builder->orWhere('size', $product->size);
                }
            });
        }

        if ($query->count() === 0) {
            $query = Product::query()
                ->with(['restaurants', 'categories'])
                ->where('id', '!=', $product->id);

            if (Schema::hasColumn('products', 'is_available')) {
                $query->where(function ($builder) {
                    $builder->whereNull('is_available')
                        ->orWhere('is_available', true);
                });
            }
        }

        $candidates = $query->limit($candidateLimit)->get();

        $needle = Str::lower((string) $product->name);

        return $candidates
            ->map(function (Product $candidate) use ($needle, $product, $targetPrice, $targetSize) {
                $score = 0;

                if (!empty($candidate->restaurant_id) && $candidate->restaurant_id === $product->restaurant_id) {
                    $score += 4;
                }

                if (!empty($candidate->category_id) && !empty($product->category_id) && $candidate->category_id === $product->category_id) {
                    $score += 3;
                }

                if (!empty($candidate->size) && $targetSize !== '' && (string) $candidate->size === $targetSize) {
                    $score += 1.5;
                }

                $name = Str::lower((string) $candidate->name);
                if ($needle !== '' && Str::contains($name, $needle)) {
                    $score += 5;
                }

                if (!empty($candidate->featured) || !empty($candidate->is_featured)) {
                    $score += 2;
                }

                if (!empty($candidate->discount_price) && $candidate->discount_price < $candidate->price) {
                    $score += 1;
                }

                if ($targetPrice > 0) {
                    $candidatePrice = (float) ($candidate->discount_price > 0 ? $candidate->discount_price : $candidate->price);
                    $priceGapRatio = abs($candidatePrice - $targetPrice) / max(1, $targetPrice);
                    $score += max(0, 2.5 - min($priceGapRatio, 1) * 3);
                }

                if (!empty($candidate->image)) {
                    $score += 0.5;
                }

                $candidate->substitution_score = $score;
                return $candidate;
            })
            ->sortByDesc('substitution_score')
            ->take($limit)
            ->values()
            ->map(function (Product $candidate) {
                return [
                    'id' => $candidate->id,
                    'name' => $candidate->name,
                    'description' => $candidate->description,
                    'price' => (float) ($candidate->discount_price > 0 ? $candidate->discount_price : $candidate->price),
                    'image' => $candidate->image ? asset('images/product_images/' . $candidate->image) : null,
                    'restaurant' => optional($candidate->restaurants)->name,
                    'size' => $candidate->size ?? null,
                    'available' => $this->isAvailable($candidate),
                    'score' => round((float) ($candidate->substitution_score ?? 0), 2),
                    'url' => route('pro.detail', $candidate->id),
                ];
            });
    }
}
