<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\MailService;
use App\Services\StallApplicationService;
use App\Services\StatementService;
use App\Support\LikeSearch;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class TenantManager extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $statusFilter = '';

    public ?int $statementUserId = null;
    public ?int $actionTenantId = null;

    public function openActions(int $id): void
    {
        $this->actionTenantId = $this->tenant($id)?->id;
    }

    public function closeActions(): void
    {
        $this->actionTenantId = null;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    /**
     * Tenants only: admin accounts are never managed from this page.
     */
    private function tenant(int $id): ?User
    {
        return User::where('role', 'user')->find($id);
    }

    public function deactivate(int $id, MailService $mailer): void
    {
        $tenant = $this->tenant($id);
        if (!$tenant) {
            $this->dispatch('notify', type: 'error', message: 'Only tenant accounts can be deactivated.');
            return;
        }

        if ($tenant->isDeactivated()) {
            $this->dispatch('notify', type: 'info', message: "{$tenant->name} is already deactivated.");
            return;
        }

        $tenant->forceFill(['deactivated_at' => now()])->save();
        AuditLog::record("Deactivated tenant account {$tenant->email}", 'User', $tenant->id);

        $message = 'Your tenant account has been deactivated. Please contact the office if you have questions.';
        $mailer->sendEmail($tenant->email, $tenant->name, 'Your account has been deactivated', '<p>Hello ' . e($tenant->name) . ',</p><p>' . e($message) . '</p>', $message);

        $this->dispatch('notify', type: 'success', message: "{$tenant->name} can no longer sign in.");
    }

    public function reactivate(int $id): void
    {
        $tenant = $this->tenant($id);
        if (!$tenant) {
            return;
        }

        $tenant->forceFill(['deactivated_at' => null])->save();
        AuditLog::record("Reactivated tenant account {$tenant->email}", 'User', $tenant->id);
        $this->dispatch('notify', type: 'success', message: "{$tenant->name} can sign in again.");
    }

    public function resendSetPassword(int $id, StallApplicationService $applications): void
    {
        $tenant = $this->tenant($id);
        if (!$tenant) {
            return;
        }

        $sent = $applications->sendSetPasswordLink($tenant);
        AuditLog::record("Sent set-password link to {$tenant->email}", 'User', $tenant->id);

        $sent
            ? $this->dispatch('notify', type: 'success', message: "Set-password link sent to {$tenant->email}.")
            : $this->dispatch('notify', type: 'error', message: 'The email could not be sent. Check the mail settings and try again.');
    }

    public function openStatement(int $id): void
    {
        $this->closeActions();
        $this->statementUserId = $this->tenant($id)?->id;
    }

    public function closeStatement(): void
    {
        $this->statementUserId = null;
    }

    public function render()
    {
        $tenants = User::where('role', 'user')
            ->withCount(['activeMappings as active_leases'])
            ->withSum(['invoices as unpaid_balance' => fn ($q) => $q->where('status', 'pending')], 'total_amount')
            ->when($this->search !== '', function ($q) {
                $term = trim($this->search);
                $q->where(fn ($w) => LikeSearch::contains(LikeSearch::contains(LikeSearch::contains($w, 'name', $term), 'email', $term, 'or'), 'phone', $term, 'or'));
            })
            ->when($this->statusFilter === 'active', fn ($q) => $q->whereNull('deactivated_at'))
            ->when($this->statusFilter === 'deactivated', fn ($q) => $q->whereNotNull('deactivated_at'))
            ->orderBy('name')
            ->paginate(15);

        $statementUser = $this->statementUserId ? User::find($this->statementUserId) : null;

        return view('livewire.admin.tenant-manager', [
            'tenants' => $tenants,
            'actionTenant' => $this->actionTenantId ? $this->tenant($this->actionTenantId) : null,
            'statementUser' => $statementUser,
            'statement' => $statementUser ? app(StatementService::class)->forUser($statementUser) : null,
        ]);
    }
}
