<?php

namespace App\Services;

use App\Product;
use App\Restaurant;
use App\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class CatalogSearchService
{
    public function __construct(private CatalogRankingService $ranking) {}

    public function searchRestaurants(string $query, array $filters = [], int $limit = null): Collection
    {
        $limit = $limit ?? (int) config('commerce.search.restaurant_limit', 12);
        $candidateLimit = (int) config('commerce.search.candidate_limit', 50);
        $queryText = $this->normalize($query);

        $builder = Restaurant::query()
            ->with([
                'cuisines',
                'products' => function ($q) {
                    $q->select('id', 'restaurant_id', 'name', 'description');
                },
            ])
            ->withCount('ratings')
            ->withAvg('ratings', 'rating');

        if (Schema::hasColumn('restaurants', 'approved')) {
            $builder->where('approved', true);
        }

        if (!empty($filters['city'])) {
            $builder->where('city', 'like', '%' . $filters['city'] . '%');
        }

        if (!empty($filters['featured']) && Schema::hasColumn('restaurants', 'featured')) {
            $builder->where('featured', true);
        }

        if (!empty($filters['cuisine_id'])) {
            $builder->whereHas('cuisines', function ($q) use ($filters) {
                $q->where('cuisines.id', $filters['cuisine_id']);
            });
        }

        if (isset($filters['max_delivery_fee']) && $filters['max_delivery_fee'] !== '') {
            $builder->where(function ($q) use ($filters) {
                $q->whereNull('delivery_charges')
                    ->orWhere('delivery_charges', '<=', (float) $filters['max_delivery_fee']);
            });
        }

        if ($queryText !== '') {
            $builder->where(function ($q) use ($queryText) {
                $q->where('name', 'like', '%' . $queryText . '%')
                    ->orWhere('address', 'like', '%' . $queryText . '%')
                    ->orWhere('city', 'like', '%' . $queryText . '%')
                    ->orWhereHas('cuisines', function ($cq) use ($queryText) {
                        $cq->where('name', 'like', '%' . $queryText . '%');
                    })
                    ->orWhereHas('products', function ($pq) use ($queryText) {
                        $pq->where('name', 'like', '%' . $queryText . '%')
                            ->orWhere('description', 'like', '%' . $queryText . '%');
                    });
            });
        }

        $restaurants = $builder->limit($candidateLimit)->get();

        if (isset($filters['min_rating']) && $filters['min_rating'] !== '') {
            $minimumRating = (float) $filters['min_rating'];
            $restaurants = $restaurants
                ->filter(fn ($restaurant) => (float) ($restaurant->ratings_avg_rating ?? 0) >= $minimumRating)
                ->values();
        }

        return $this->ranking->rankRestaurants($restaurants, array_merge($filters, [
            'query' => $queryText,
            'limit' => $limit,
        ]));
    }

    public function searchProducts(string $query, array $filters = [], int $limit = null): Collection
    {
        $limit = $limit ?? (int) config('commerce.search.product_limit', 12);
        $candidateLimit = (int) config('commerce.search.candidate_limit', 50);
        $queryText = $this->normalize($query);

        $builder = Product::query()->with(['restaurants', 'categories']);

        if (Schema::hasColumn('products', 'is_available')) {
            $builder->where(function ($q) {
                $q->whereNull('is_available')
                    ->orWhere('is_available', true);
            });
        }

        if (Schema::hasColumn('restaurants', 'approved')) {
            $builder->whereHas('restaurants', fn ($q) => $q->where('approved', true));
        }

        if (!empty($filters['restaurant_id'])) {
            $builder->where('restaurant_id', $filters['restaurant_id']);
        }

        if (!empty($filters['category_id'])) {
            $builder->where('category_id', $filters['category_id']);
        }

        if (isset($filters['min_price']) && $filters['min_price'] !== '') {
            $builder->where('price', '>=', (float) $filters['min_price']);
        }

        if (isset($filters['max_price']) && $filters['max_price'] !== '') {
            $builder->where('price', '<=', (float) $filters['max_price']);
        }

        if (!empty($filters['featured'])) {
            $hasFeaturedColumn = Schema::hasColumn('products', 'featured') || Schema::hasColumn('products', 'is_featured');
            if ($hasFeaturedColumn) {
                $builder->where(function ($q) {
                    if (Schema::hasColumn('products', 'featured')) {
                        $q->where('featured', true);
                    }
                    if (Schema::hasColumn('products', 'is_featured')) {
                        $q->orWhere('is_featured', true);
                    }
                });
            }
        }

        if ($queryText !== '') {
            $builder->where(function ($q) use ($queryText) {
                $q->where('name', 'like', '%' . $queryText . '%')
                    ->orWhere('description', 'like', '%' . $queryText . '%')
                    ->orWhereHas('restaurants', function ($restaurantQuery) use ($queryText) {
                        $restaurantQuery->where('name', 'like', '%' . $queryText . '%')
                            ->orWhere('city', 'like', '%' . $queryText . '%');
                    })
                    ->orWhereHas('categories', function ($categoryQuery) use ($queryText) {
                        $categoryQuery->where('name', 'like', '%' . $queryText . '%');
                    });
            });
        }

        $products = $builder->limit($candidateLimit)->get();

        return $this->ranking->rankProducts($products, array_merge($filters, [
            'query' => $queryText,
            'limit' => $limit,
        ]));
    }

    public function rankRestaurants(Collection $restaurants, array $context = []): Collection
    {
        return $this->ranking->rankRestaurants($restaurants, $context);
    }

    public function rankProducts(Collection $products, array $context = []): Collection
    {
        return $this->ranking->rankProducts($products, $context);
    }

    public function recommendRestaurants(?User $user = null, array $context = [], int $limit = null): Collection
    {
        return $this->ranking->recommendRestaurants($user, $context, $limit);
    }

    public function recommendProducts(?User $user = null, array $context = [], int $limit = null): Collection
    {
        return $this->ranking->recommendProducts($user, $context, $limit);
    }

    public function recommendationProfile(?User $user = null, array $context = []): array
    {
        return $this->ranking->recommendationProfile($user, $context);
    }

    private function normalize(string $value): string
    {
        return trim(\Illuminate\Support\Str::lower($value));
    }
}
