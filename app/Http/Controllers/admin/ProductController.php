<?php

namespace App\Http\Controllers\admin;

use App\Category;
use App\Http\Controllers\Controller;
use App\Product;
use App\Restaurant;
use App\Services\DataSyncService;
use App\Services\UnifiedMediaLibraryService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function __construct(private UnifiedMediaLibraryService $unifiedMediaLibraryService)
    {
    }

    public function index()
    {
        $mediaStatus = (string) request()->query('media_status', 'all');
        $restaurantId = max(0, (int) request()->integer('restaurant_id', 0));
        $currentRestaurant = $restaurantId > 0 ? Restaurant::find($restaurantId) : null;

        $baseQuery = Product::query()->with(['categories', 'restaurants']);
        $scopedBaseQuery = clone $baseQuery;

        if ($currentRestaurant) {
            $scopedBaseQuery->where('restaurant_id', $currentRestaurant->id);
        }

        $productsQuery = clone $scopedBaseQuery;
        $this->applyMediaStatusFilter($productsQuery, $mediaStatus);

        if ($mediaStatus === 'missing') {
            $productsQuery
                ->orderByDesc('featured')
                ->orderBy('restaurant_id')
                ->orderBy('name');
        } else {
            $productsQuery->orderByDesc('id');
        }

        $products = $productsQuery->get();

        $totalProducts = (clone $scopedBaseQuery)->count();
        $productsMissingMediaQuery = clone $scopedBaseQuery;
        $this->applyMissingMediaCondition($productsMissingMediaQuery);
        $productsMissingMedia = $productsMissingMediaQuery->count();
        $productsWithExternalMedia = (clone $scopedBaseQuery)
            ->where('image', 'like', 'http%')
            ->count();
        $featuredProductsMissingMedia = (clone $scopedBaseQuery)
            ->where('featured', 1);
        $this->applyMissingMediaCondition($featuredProductsMissingMedia);
        $featuredProductsMissingMedia = $featuredProductsMissingMedia
            ->count();
        $restaurantBacklog = Product::query()
            ->select(
                'restaurant_id',
                DB::raw('COUNT(*) as missing_count'),
                DB::raw('SUM(CASE WHEN featured = 1 THEN 1 ELSE 0 END) as featured_missing_count')
            );
        $this->applyMissingMediaCondition($restaurantBacklog);
        $restaurantBacklog = $restaurantBacklog
            ->whereNotNull('restaurant_id')
            ->groupBy('restaurant_id')
            ->orderByDesc('featured_missing_count')
            ->orderByDesc('missing_count')
            ->get();

        $restaurantNames = Restaurant::query()
            ->whereIn('id', $restaurantBacklog->pluck('restaurant_id')->all())
            ->pluck('name', 'id');

        $restaurantBacklog = $restaurantBacklog->map(function ($item) use ($restaurantNames) {
            return [
                'restaurant_id' => (int) $item->restaurant_id,
                'restaurant_name' => $restaurantNames[$item->restaurant_id] ?? ('Restaurant #' . $item->restaurant_id),
                'missing_count' => (int) $item->missing_count,
                'featured_missing_count' => (int) $item->featured_missing_count,
            ];
        })->values();
        $restaurantsWithMissingMedia = $restaurantBacklog->count();

        return view('admin.product.index', compact(
            'products',
            'mediaStatus',
            'currentRestaurant',
            'totalProducts',
            'productsMissingMedia',
            'productsWithExternalMedia',
            'featuredProductsMissingMedia',
            'restaurantBacklog',
            'restaurantsWithMissingMedia'
        ));
    }

    public function create()
    {
        return view('admin.product.create', [
            'restaurants' => Restaurant::orderBy('name')->get(),
            'categories' => Category::with('products')->orderBy('name')->get(),
            'mediaLibraryOptions' => $this->unifiedMediaLibraryService->groupedOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        $this->ensureCategoryMatchesRestaurant($data['restaurant_id'], $data['category_id']);

        $product = Product::create([
            'restaurant_id' => $data['restaurant_id'],
            'category_id' => $data['category_id'],
            'name' => $data['name'],
            'price' => $data['price'],
            'discount_price' => $data['discount_price'] ?? null,
            'description' => $data['description'] ?? null,
            'size' => $data['size'] ?? null,
        ]);

        $this->storeProductImage($product, $request);
        DataSyncService::invalidateProductCache($product->id);

        return redirect('/admin/all-products')->with('alert', [
            'type' => 'success',
            'message' => 'Plat créé avec succès',
        ]);
    }

    public function edit(Product $product)
    {
        $backlogContext = $this->backlogContextFromRequest(request());
        [$previousBacklogProductId, $nextBacklogProductId] = $this->adjacentProductIds($product->id, $backlogContext);

        return view('admin.product.edit', [
            'product' => $product,
            'restaurants' => Restaurant::orderBy('name')->get(),
            'categories' => Category::orderBy('name')->get(),
            'mediaLibraryOptions' => $this->unifiedMediaLibraryService->groupedOptions(),
            'backlogContext' => $backlogContext,
            'previousBacklogProductId' => $previousBacklogProductId,
            'nextBacklogProductId' => $nextBacklogProductId,
        ]);
    }

    public function show(Product $product)
    {
        return redirect('/admin/product/' . $product->id . '/edit');
    }

    public function update(Request $request, Product $product)
    {
        $data = $this->validatedData($request, false);
        $this->ensureCategoryMatchesRestaurant($data['restaurant_id'], $data['category_id']);

        $product->update([
            'restaurant_id' => $data['restaurant_id'],
            'category_id' => $data['category_id'],
            'name' => $data['name'],
            'price' => $data['price'],
            'discount_price' => $data['discount_price'] ?? null,
            'description' => $data['description'] ?? null,
            'size' => $data['size'] ?? null,
        ]);

        $this->storeProductImage($product, $request, true);
        DataSyncService::invalidateProductCache($product->id);

        $redirectTarget = $this->updateRedirectTarget($request);

        return redirect($redirectTarget)->with('alert', [
            'type' => 'success',
            'message' => 'Plat mis à jour avec succès',
        ]);
    }

    public function destroy(Product $product)
    {
        $restaurantId = $product->restaurant_id;
        $this->deleteLocalImage($product->image, true);

        $product->delete();
        DataSyncService::invalidateRestaurantCache($restaurantId);

        return redirect('/admin/all-products')->with('alert', [
            'type' => 'success',
            'message' => 'Plat supprimé avec succès',
        ]);
    }

    private function validatedData(Request $request, bool $requireImageSource = true): array
    {
        $imageRule = $requireImageSource
            ? 'nullable|image|mimes:jpeg,png,jpg,webp|max:8192|required_without_all:image_url,image_media_path'
            : 'nullable|image|mimes:jpeg,png,jpg,webp|max:8192';

        $imageUrlRule = $requireImageSource
            ? 'nullable|url|max:2048|required_without_all:image,image_media_path'
            : 'nullable|url|max:2048';

        $imageMediaPathRule = $requireImageSource
            ? 'nullable|string|max:2048|required_without_all:image,image_url'
            : 'nullable|string|max:2048';

        return $request->validate([
            'restaurant_id' => 'required|integer|exists:restaurants,id',
            'category_id' => 'required|integer|exists:categories,id',
            'name' => 'required|string|max:191',
            'image' => $imageRule,
            'image_url' => $imageUrlRule,
            'image_media_path' => $imageMediaPathRule,
            'price' => 'required',
            'discount_price' => 'nullable',
            'description' => 'nullable|string|max:191',
            'size' => 'nullable|string|max:191',
        ]);
    }

    private function ensureCategoryMatchesRestaurant(int $restaurantId, int $categoryId): void
    {
        Category::where('id', $categoryId)
            ->where('restaurant_id', $restaurantId)
            ->firstOrFail();
    }

    private function applyMediaStatusFilter(Builder $query, string $mediaStatus): void
    {
        if ($mediaStatus === 'missing') {
            $this->applyMissingMediaCondition($query);

            return;
        }

        if ($mediaStatus === 'external') {
            $query->where('image', 'like', 'http%');

            return;
        }

        if ($mediaStatus === 'ready') {
            $query->whereNotNull('image')
                ->where('image', '!=', '')
                ->where('image', '!=', 'default-food.jpg')
                ->where('image', 'not like', 'http%');
        }
    }

    private function applyMissingMediaCondition(Builder $query): void
    {
        $query->where(function (Builder $builder) {
            $builder->whereNull('image')
                ->orWhere('image', '')
                ->orWhere('image', 'default-food.jpg');
        });
    }

    private function backlogContextFromRequest(Request $request): array
    {
        $mediaStatus = (string) $request->query('media_status', '');
        $restaurantId = max(0, (int) $request->query('restaurant_id', 0));

        return [
            'media_status' => in_array($mediaStatus, ['all', 'missing', 'ready', 'external'], true) ? $mediaStatus : null,
            'restaurant_id' => $restaurantId > 0 ? $restaurantId : null,
        ];
    }

    private function adjacentProductIds(int $productId, array $backlogContext): array
    {
        $query = Product::query()->select('id');

        if (!empty($backlogContext['restaurant_id'])) {
            $query->where('restaurant_id', (int) $backlogContext['restaurant_id']);
        }

        if (!empty($backlogContext['media_status'])) {
            $this->applyMediaStatusFilter($query, (string) $backlogContext['media_status']);
        }

        if (($backlogContext['media_status'] ?? null) === 'missing') {
            $query->orderByDesc('featured')
                ->orderBy('restaurant_id')
                ->orderBy('name');
        } else {
            $query->orderByDesc('id');
        }

        $productIds = $query->pluck('id')->values();
        $currentIndex = $productIds->search($productId);

        if ($currentIndex === false) {
            return [null, null];
        }

        return [
            $productIds->get($currentIndex - 1),
            $productIds->get($currentIndex + 1),
        ];
    }

    private function updateRedirectTarget(Request $request): string
    {
        $mediaStatus = (string) $request->input('context_media_status', '');
        $restaurantId = max(0, (int) $request->input('context_restaurant_id', 0));
        $nextProductId = max(0, (int) $request->input('backlog_next_product_id', 0));

        $query = array_filter([
            'media_status' => in_array($mediaStatus, ['all', 'missing', 'ready', 'external'], true) ? $mediaStatus : null,
            'restaurant_id' => $restaurantId > 0 ? $restaurantId : null,
        ]);

        if ($request->boolean('continue_work') && $nextProductId > 0) {
            return route('admin.product.edit', $nextProductId) . (!empty($query) ? '?' . http_build_query($query) : '');
        }

        if (!empty($query)) {
            return route('total.pro', $query);
        }

        return '/admin/all-products';
    }

    private function storeProductImage(Product $product, Request $request, bool $replace = false): void
    {
        $oldImage = $product->image;
        $destination = public_path('images/product_images');

        if (! is_dir($destination)) {
            @mkdir($destination, 0775, true);
        }

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
                . '-' . uniqid() . '.' . $file->getClientOriginalExtension();

            $file->move($destination, $filename);
            $product->image = $filename;
            $product->save();

            $this->deleteLocalImage($oldImage, $replace);
            return;
        }

        if ($request->filled('image_media_path')) {
            $product->image = $this->unifiedMediaLibraryService->copyToDirectory(
                $request->input('image_media_path'),
                'images/product_images',
                'product-' . $product->id
            );
            $product->save();

            $this->deleteLocalImage($oldImage, $replace);
            return;
        }

        if ($request->filled('image_url')) {
            $product->image = $request->input('image_url');
            $product->save();

            $this->deleteLocalImage($oldImage, $replace);
        }
    }

    private function deleteLocalImage($oldImage, bool $replace): void
    {
        $relativePath = $this->localProductImagePath($oldImage);

        if (! $replace || ! $relativePath) {
            return;
        }

        $oldPath = public_path($relativePath);
        if (File::exists($oldPath)) {
            @unlink($oldPath);
        }
    }

    private function localProductImagePath($value): ?string
    {
        $normalized = ltrim((string) $value, '/');

        if ($normalized === '' || Str::startsWith($normalized, ['http://', 'https://'])) {
            return null;
        }

        if (basename($normalized) === 'default-food.jpg') {
            return null;
        }

        if (Str::contains($normalized, '/')) {
            return Str::startsWith($normalized, 'images/product_images/') ? $normalized : null;
        }

        return 'images/product_images/' . $normalized;
    }
}
