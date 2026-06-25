<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notification_logs')) {
            return;
        }

        if (! Schema::hasColumn('notification_logs', 'archived_at')) {
            Schema::table('notification_logs', function (Blueprint $table): void {
                $table->timestamp('archived_at')->nullable()->index()->after('read_at');
            });
        }

        $detailedNotifications = DB::table('notification_logs')
            ->where('recipient_type', 'user')
            ->where('title', 'Commande annulée')
            ->where('body', 'like', '%paiement n\'a pas été finalisé à temps.%')
            ->orderBy('id')
            ->get(['id', 'recipient_id', 'body', 'context', 'created_at']);

        foreach ($detailedNotifications as $notification) {
            $context = json_decode((string) $notification->context, true);
            $orderNo = is_array($context) ? ($context['order_no'] ?? null) : null;

            if (! $orderNo && preg_match('/#([A-Z0-9-]+)/i', (string) $notification->body, $matches)) {
                $orderNo = $matches[1];
            }

            if (! $orderNo) {
                continue;
            }

            $createdAt = Carbon::parse($notification->created_at);

            DB::table('notification_logs')
                ->where('id', '<>', $notification->id)
                ->whereNull('archived_at')
                ->where('recipient_type', 'user')
                ->where('recipient_id', $notification->recipient_id)
                ->where('title', 'Commande annulée')
                ->whereIn('body', [
                    'La commande #' . $orderNo . ' a été annulée.',
                    'Votre commande #' . $orderNo . ' a été annulée.',
                ])
                ->whereBetween('created_at', [
                    $createdAt->copy()->subMinutes(10),
                    $createdAt->copy()->addMinutes(10),
                ])
                ->update(['archived_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('notification_logs') && Schema::hasColumn('notification_logs', 'archived_at')) {
            Schema::table('notification_logs', function (Blueprint $table): void {
                $table->dropColumn('archived_at');
            });
        }
    }
};
