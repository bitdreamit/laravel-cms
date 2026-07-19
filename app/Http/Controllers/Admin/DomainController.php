<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Dns\Jobs\OrderSslCertificateJob;
use App\Domain\Dns\Jobs\VerifyDomainDnsJob;
use App\Domain\Dns\Services\DnsVerificationService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDomainRequest;
use App\Http\Resources\Api\DomainResource;
use App\Models\Central\Domain;
use App\Models\Central\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DomainController extends Controller
{
    public function __construct(protected DnsVerificationService $dnsService) {}

    public function index()
    {
        $domains = Domain::where('tenant_id', tenant('id'))
            ->with(['sslCertificate', 'theme'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return DomainResource::collection($domains);
    }

    public function store(StoreDomainRequest $request)
    {
        $data = $request->validated();
        $data['id'] = Str::uuid();
        $data['tenant_id'] = tenant('id');
        $data['dns_verification_status'] = 'unverified';

        // Detect wildcard
        if (str_starts_with($data['domain'], '*.')) {
            $data['is_wildcard'] = true;
            $data['wildcard_parent'] = substr($data['domain'], 2);
        }

        $domain = Domain::create($data);

        // Create DNS verification job
        $verificationJob = $this->dnsService->createVerificationJob($domain);
        VerifyDomainDnsJob::dispatch($verificationJob->id);

        return new DomainResource($domain);
    }

    public function show(string $id)
    {
        $domain = Domain::where('tenant_id', tenant('id'))->findOrFail($id);
        return new DomainResource($domain->load(['sslCertificate', 'theme']));
    }

    public function update(Request $request, string $id)
    {
        $domain = Domain::where('tenant_id', tenant('id'))->findOrFail($id);
        $domain->update($request->only([
            'is_primary', 'theme_id', 'site_id', 'default_collection_handle',
            'route_prefix', 'config', 'status', 'redirect_target',
            'analytics_property_id',
        ]));

        return new DomainResource($domain);
    }

    public function destroy(string $id)
    {
        $domain = Domain::where('tenant_id', tenant('id'))->findOrFail($id);

        // Free SSL certificate association
        $domain->update(['ssl_certificate_id' => null, 'ssl_status' => 'pending']);

        $domain->delete();
        return response()->noContent();
    }

    public function verifyDns(string $id)
    {
        $domain = Domain::where('tenant_id', tenant('id'))->findOrFail($id);

        $job = $domain->dnsVerificationJobs()->where('status', 'pending')->latest()->first();

        if (! $job) {
            $job = $this->dnsService->createVerificationJob($domain);
        }

        VerifyDomainDnsJob::dispatch($job->id);

        return response()->json([
            'message' => 'DNS verification started.',
            'instructions' => $this->dnsService->getInstructions($job),
        ]);
    }

    public function requestSsl(string $id)
    {
        $domain = Domain::where('tenant_id', tenant('id'))->findOrFail($id);

        if (! $domain->isVerified()) {
            return response()->json([
                'error' => 'Domain must be DNS-verified before SSL can be issued.',
            ], 422);
        }

        OrderSslCertificateJob::dispatch($domain->id);

        return response()->json([
            'message' => 'SSL certificate order dispatched.',
        ]);
    }

    public function renewSsl(string $id)
    {
        $domain = Domain::where('tenant_id', tenant('id'))->findOrFail($id);

        if (! $domain->sslCertificate) {
            return response()->json(['error' => 'No SSL certificate to renew.'], 422);
        }

        \App\Domain\Dns\Jobs\RenewSslCertificateJob::dispatch($domain->ssl_certificate_id);

        return response()->json(['message' => 'SSL renewal dispatched.']);
    }

    public function activateTheme(Request $request, string $id)
    {
        $domain = Domain::where('tenant_id', tenant('id'))->findOrFail($id);
        $domain->update(['theme_id' => $request->input('theme_id')]);

        return new DomainResource($domain);
    }

    public function activateSite(Request $request, string $id)
    {
        $domain = Domain::where('tenant_id', tenant('id'))->findOrFail($id);
        $domain->update(['site_id' => $request->input('site_id')]);

        return new DomainResource($domain);
    }
}
