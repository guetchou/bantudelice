<?php

namespace App\Console\Commands;

use App\AdminPermission;
use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AdminGrantWorkspace extends Command
{
    protected $signature = 'admin:grant
                            {email : Email de l\'admin}
                            {workspace : Workspace a accorder (bantudelice|kende|mema|*)}
                            {--revoke : Revoquer la permission au lieu de l\'accorder}
                            {--list : Lister les permissions de cet admin}';

    protected $description = 'Accorder ou revoquer l\'acces workspace a un administrateur';

    public function handle(): int
    {
        $email     = $this->argument('email');
        $workspace = $this->argument('workspace');

        $validWorkspaces = ['bantudelice', 'kende', 'mema', '*'];
        if (!in_array($workspace, $validWorkspaces, true)) {
            $this->error('Workspace invalide. Valeurs acceptees : ' . implode(', ', $validWorkspaces));
            return 1;
        }

        $user = User::where('email', $email)->where('type', 'admin')->first();
        if (!$user) {
            $this->error("Aucun admin trouve avec l'email : {$email}");
            return 1;
        }

        if ($this->option('list')) {
            $permissions = AdminPermission::where('user_id', $user->id)->get();
            $this->table(['ID', 'Workspace', 'Accorde par', 'Revoque le', 'Cree le'], $permissions->map(fn($p) => [
                $p->id,
                $p->workspace,
                $p->granted_by ?? '(systeme)',
                $p->revoked_at ?? '-',
                $p->created_at,
            ])->toArray());
            return 0;
        }

        if ($this->option('revoke')) {
            $updated = AdminPermission::where('user_id', $user->id)
                ->where('workspace', $workspace)
                ->whereNull('revoked_at')
                ->update(['revoked_at' => now()]);

            if ($updated === 0) {
                $this->warn("Aucune permission active trouvee pour {$email} sur {$workspace}.");
            } else {
                $this->info("Permission {$workspace} revoquee pour {$email}.");
            }
            return 0;
        }

        $existing = AdminPermission::where('user_id', $user->id)
            ->where('workspace', $workspace)
            ->whereNull('revoked_at')
            ->first();

        if ($existing) {
            $this->warn("Permission {$workspace} deja active pour {$email} (id={$existing->id}).");
            return 0;
        }

        $grantedBy = null;
        if ($this->input->isInteractive()) {
            $granterEmail = $this->ask('Email de l\'admin qui accorde (laisser vide pour systeme)');
            if ($granterEmail) {
                $granter    = User::where('email', $granterEmail)->where('type', 'admin')->first();
                $grantedBy  = $granter?->id;
            }
        }

        AdminPermission::create([
            'user_id'    => $user->id,
            'workspace'  => $workspace,
            'granted_by' => $grantedBy,
        ]);

        $this->info("Permission {$workspace} accordee a {$email}.");
        return 0;
    }
}
