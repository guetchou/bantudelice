<?php

namespace App\Http\Controllers\admin;

use App\Domain\GePay\Enums\TransactionStatus;
use App\Domain\GePay\Enums\TransactionType;
use App\Domain\GePay\Models\GePayTransaction;
use App\Domain\GePay\Services\GePayGateway;
use App\Domain\GePay\Services\GePayInternalClientResolver;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GePayDisbursementController extends Controller
{
    public function __construct(
        private readonly GePayGateway $gateway,
        private readonly GePayInternalClientResolver $resolver,
    ) {}

    public function index(): \Illuminate\View\View
    {
        $kpis = $this->kpis();
        $recent = GePayTransaction::orderByDesc('created_at')
            ->limit(50)
            ->get(['uuid', 'type', 'status', 'amount', 'currency', 'phone_masked',
                   'external_reference', 'failure_code', 'created_at', 'completed_at']);

        return view('admin.gepay.disbursement', compact('kpis', 'recent'));
    }

    public function disburse(Request $request): JsonResponse
    {
        $data = $request->validate([
            'recipients'                => ['required', 'array', 'min:1', 'max:3'],
            'recipients.*.name'         => ['required', 'string', 'max:191'],
            'recipients.*.phone'        => ['required', 'string', 'max:30'],
            'recipients.*.amount'       => ['required', 'integer', 'min:100', 'max:2000000000'],
        ]);

        try {
            $client = $this->resolver->resolve();
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 503);
        }

        $batchId  = strtoupper(Str::random(8));
        $results  = [];

        foreach ($data['recipients'] as $i => $recipient) {
            $extRef = 'ADMIN-DISB-' . $batchId . '-' . ($i + 1);
            $ikey   = 'admin:disbursement:' . $batchId . ':' . $i;

            try {
                $tx = $this->gateway->initiate(
                    client: $client,
                    type: TransactionType::DISBURSEMENT,
                    payload: [
                        'amount'             => (int) $recipient['amount'],
                        'currency'           => 'XAF',
                        'phone'              => preg_replace('/\D+/', '', $recipient['phone']),
                        'external_reference' => $extRef,
                        'payer_message'      => 'Décaissement ' . $recipient['name'],
                        'payee_note'         => 'Virement BantuDelice ' . $batchId,
                    ],
                    idempotencyKey: $ikey,
                );

                $results[] = [
                    'name'      => $recipient['name'],
                    'phone'     => $recipient['phone'],
                    'amount'    => (int) $recipient['amount'],
                    'uuid'      => $tx->uuid,
                    'status'    => $tx->status->value,
                    'ext_ref'   => $extRef,
                    'success'   => ! $tx->status->isTerminal() || $tx->status === TransactionStatus::SUCCESSFUL,
                ];
            } catch (\Throwable $e) {
                $results[] = [
                    'name'    => $recipient['name'],
                    'phone'   => $recipient['phone'],
                    'amount'  => (int) $recipient['amount'],
                    'success' => false,
                    'message' => $e->getMessage(),
                ];
            }
        }

        $allOk = collect($results)->every(fn ($r) => $r['success'] ?? false);

        return response()->json([
            'success'  => $allOk,
            'batch_id' => $batchId,
            'results'  => $results,
        ], $allOk ? 202 : 207);
    }

    public function transactions(Request $request): JsonResponse
    {
        $type = $request->query('type');

        $query = GePayTransaction::orderByDesc('created_at')->limit(100);

        if ($type === 'collection') {
            $query->where('type', TransactionType::COLLECTION->value);
        } elseif ($type === 'disbursement') {
            $query->where('type', TransactionType::DISBURSEMENT->value);
        }

        $rows = $query->get(['uuid', 'type', 'status', 'amount', 'currency',
                             'phone_masked', 'external_reference', 'created_at', 'completed_at']);

        return response()->json(['success' => true, 'transactions' => $rows]);
    }

    private function kpis(): array
    {
        $base = GePayTransaction::query();

        return [
            'collected' => (clone $base)
                ->where('type', TransactionType::COLLECTION->value)
                ->where('status', TransactionStatus::SUCCESSFUL->value)
                ->sum('amount'),

            'disbursed' => (clone $base)
                ->where('type', TransactionType::DISBURSEMENT->value)
                ->where('status', TransactionStatus::SUCCESSFUL->value)
                ->sum('amount'),

            'pending' => (clone $base)
                ->whereIn('status', [
                    TransactionStatus::CREATED->value,
                    TransactionStatus::SUBMITTED->value,
                    TransactionStatus::PENDING->value,
                ])
                ->count(),

            'failed' => (clone $base)
                ->whereIn('status', [
                    TransactionStatus::FAILED->value,
                    TransactionStatus::CANCELLED->value,
                    TransactionStatus::EXPIRED->value,
                ])
                ->count(),
        ];
    }
}
