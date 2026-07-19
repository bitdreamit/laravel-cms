<?php

namespace App\Domain\Personalization\Conditions;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Context
{
    public function __construct(
        public Request $request,
        public ?string $visitorId = null,
        public ?string $userId = null,
        public ?string $tenantId = null,
        public ?array $profile = null,
    ) {}

    public function getVisitorId(): ?string
    {
        return $this->visitorId ?? $this->request->cookie(config('personalization.visitor.cookie_name'));
    }

    public function getUserId(): ?string
    {
        return $this->userId ?? auth()->id();
    }

    public function getTenantId(): ?string
    {
        return $this->tenantId ?? tenant('id');
    }

    public function getVisitorSessions()
    {
        $visitorId = $this->getVisitorId();
        if (! $visitorId) return collect();

        return DB::table('visitor_sessions')
            ->where('visitor_id', $visitorId)
            ->where('tenant_id', $this->getTenantId())
            ->get();
    }
}
