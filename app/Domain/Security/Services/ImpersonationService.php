<?php

namespace App\Domain\Security\Services;

use App\Models\Central\User;
use App\Models\Central\OauthConnection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

/**
 * Impersonation Service — allows admins to login as other users (audit-logged).
 *
 * Surpasses Statamic Pro which has impersonation but no audit trail.
 */
class ImpersonationService
{
    public function start(User $impersonator, User $impersonated): bool
    {
        if ($impersonator->id === $impersonated->id) {
            return false;
        }

        if (! $this->canImpersonate($impersonator, $impersonated)) {
            return false;
        }

        Session::put('impersonator_id', $impersonator->id);
        Session::put('impersonating_since', now()->toIso8601String());

        Auth::login($impersonated);

        // Audit log
        activity()
            ->causedBy($impersonator)
            ->performedOn($impersonated)
            ->withProperties([
                'impersonator_id' => $impersonator->id,
                'impersonator_email' => $impersonator->email,
                'ip' => request()->ip(),
            ])
            ->log('impersonation_started');

        return true;
    }

    public function stop(): void
    {
        $impersonatorId = Session::get('impersonator_id');
        if (! $impersonatorId) return;

        $impersonator = User::find($impersonatorId);
        $impersonated = auth()->user();

        Session::forget(['impersonator_id', 'impersonating_since']);

        if ($impersonator) {
            Auth::login($impersonator);

            activity()
                ->causedBy($impersonator)
                ->performedOn($impersonated)
                ->withProperties([
                    'impersonator_id' => $impersonator->id,
                    'impersonated_id' => $impersonated->id,
                    'duration_seconds' => now()->diffInSeconds(Session::get('impersonating_since', now())),
                ])
                ->log('impersonation_stopped');
        }
    }

    public function isImpersonating(): bool
    {
        return Session::has('impersonator_id');
    }

    public function getImpersonator(): ?User
    {
        $id = Session::get('impersonator_id');
        return $id ? User::find($id) : null;
    }

    protected function canImpersonate(User $impersonator, User $impersonated): bool
    {
        // Both users must be in the same tenant
        $impersonatorTenants = $impersonator->tenants()->pluck('tenants.id');
        $impersonatedTenants = $impersonated->tenants()->pluck('tenants.id');

        $commonTenants = $impersonatorTenants->intersect($impersonatedTenants);
        if ($commonTenants->isEmpty()) return false;

        // Impersonator must have 'owner' or 'admin' role in at least one common tenant
        foreach ($commonTenants as $tenantId) {
            $role = \DB::table('tenant_users')
                ->where('tenant_id', $tenantId)
                ->where('user_id', $impersonator->id)
                ->value('role');

            if (in_array($role, ['owner', 'admin'])) {
                return true;
            }
        }

        return false;
    }
}
