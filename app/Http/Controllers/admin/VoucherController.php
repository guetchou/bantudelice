<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Restaurant;
use App\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class VoucherController extends Controller
{
    public function index(Request $request)
    {
        $query = Voucher::with(['restaurant'])
            ->withCount('redemptions')
            ->orderByDesc('id');

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhereHas('restaurant', function ($rq) use ($search) {
                        $rq->where('name', 'like', '%' . $search . '%');
                    });
            });
        }

        if ($request->filled('restaurant_id')) {
            $query->where('restaurant_id', $request->restaurant_id);
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where(function ($q) {
                    $q->whereNull('is_active')->orWhere('is_active', true);
                });
            } elseif ($request->status === 'inactive') {
                $query->where(function ($q) {
                    $q->where('is_active', false);
                });
            }
        }

        $vouchers = $query->paginate(20)->withQueryString();
        $restaurants = Restaurant::orderBy('name')->get();

        return view('admin.vouchers.index', compact('vouchers', 'restaurants'));
    }

    public function create()
    {
        $voucher = new Voucher([
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'min_order_amount' => 0,
            'usage_limit' => null,
            'used_count' => 0,
            'per_user_limit' => 1,
            'stackable' => false,
            'is_active' => true,
        ]);

        $restaurants = Restaurant::orderBy('name')->get();

        return view('admin.vouchers.create', compact('voucher', 'restaurants'));
    }

    public function store(Request $request)
    {
        $data = $this->validateVoucher($request);
        $voucher = new Voucher();
        $this->fillVoucher($voucher, $data);
        $voucher->save();

        return redirect()
            ->route('admin.promotions.index')
            ->with('alert', [
                'type' => 'success',
                'message' => 'Promotion créée avec succès.',
            ]);
    }

    public function edit(Voucher $voucher)
    {
        $restaurants = Restaurant::orderBy('name')->get();

        return view('admin.vouchers.edit', compact('voucher', 'restaurants'));
    }

    public function update(Request $request, Voucher $voucher)
    {
        $data = $this->validateVoucher($request);
        $this->fillVoucher($voucher, $data);
        $voucher->save();

        return redirect()
            ->route('admin.promotions.index')
            ->with('alert', [
                'type' => 'success',
                'message' => 'Promotion mise à jour avec succès.',
            ]);
    }

    public function destroy(Voucher $voucher)
    {
        $voucher->delete();

        return redirect()
            ->route('admin.promotions.index')
            ->with('alert', [
                'type' => 'success',
                'message' => 'Promotion supprimée avec succès.',
            ]);
    }

    protected function validateVoucher(Request $request): array
    {
        $voucherId = optional($request->route('voucher'))->id;

        return $request->validate([
            'restaurant_id' => ['nullable', 'integer', 'exists:restaurants,id'],
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('vouchers', 'name')->ignore($voucherId),
            ],
            'discount_type' => ['required', 'in:percentage,fixed'],
            'discount_value' => ['required', 'numeric', 'min:0.01'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'max_discount_amount' => ['nullable', 'numeric', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'per_user_limit' => ['nullable', 'integer', 'min:1'],
            'stackable' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'rules_json' => ['nullable', 'string'],
        ]);
    }

    protected function fillVoucher(Voucher $voucher, array $data): void
    {
        $startsAt = !empty($data['starts_at']) ? Carbon::parse($data['starts_at']) : null;
        $endsAt = !empty($data['ends_at']) ? Carbon::parse($data['ends_at']) : null;
        $rules = $this->decodeRules($data['rules_json'] ?? null);

        $voucher->fill([
            'restaurant_id' => $data['restaurant_id'] ?: null,
            'name' => strtoupper(trim((string) $data['name'])),
            'discount' => (float) $data['discount_value'],
            'discount_type' => $data['discount_type'],
            'discount_value' => (float) $data['discount_value'],
            'min_order_amount' => (float) ($data['min_order_amount'] ?? 0),
            'max_discount_amount' => isset($data['max_discount_amount']) ? (float) $data['max_discount_amount'] : null,
            'usage_limit' => $data['usage_limit'] ?? null,
            'used_count' => $voucher->used_count ?? 0,
            'per_user_limit' => $data['per_user_limit'] ?? 1,
            'stackable' => (bool) ($data['stackable'] ?? false),
            'is_active' => (bool) ($data['is_active'] ?? true),
            'start_date' => $startsAt,
            'end_date' => $endsAt,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'rules' => $rules,
        ]);
    }

    protected function decodeRules(?string $rulesJson): array
    {
        if (!$rulesJson) {
            return [];
        }

        $decoded = json_decode($rulesJson, true);
        return is_array($decoded) ? $decoded : [];
    }
}
