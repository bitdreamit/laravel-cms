<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant\SamlIdentityProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SamlIdpController extends Controller
{
    public function index()
    {
        $idps = SamlIdentityProvider::where('tenant_id', tenant('id'))->paginate(20);
        return response()->json($idps);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:200',
            'entity_id' => 'required|string',
            'metadata_xml' => 'required|string',
            'sso_url' => 'required|url',
            'slo_url' => 'nullable|url',
            'x509_certificate' => 'required|string',
            'attribute_mapping' => 'nullable|array',
            'role_mapping' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $idp = SamlIdentityProvider::create(array_merge($data, [
            'id' => Str::uuid(),
            'tenant_id' => tenant('id'),
        ]));

        return response()->json($idp, 201);
    }

    public function show(string $id)
    {
        $idp = SamlIdentityProvider::where('tenant_id', tenant('id'))->findOrFail($id);
        return response()->json($idp);
    }

    public function update(Request $request, string $id)
    {
        $idp = SamlIdentityProvider::where('tenant_id', tenant('id'))->findOrFail($id);
        $idp->update($request->only([
            'name', 'entity_id', 'metadata_xml', 'sso_url', 'slo_url',
            'x509_certificate', 'attribute_mapping', 'role_mapping', 'is_active',
        ]));
        return response()->json($idp);
    }

    public function destroy(string $id)
    {
        $idp = SamlIdentityProvider::where('tenant_id', tenant('id'))->findOrFail($id);
        $idp->delete();
        return response()->noContent();
    }

    public function testLogin(string $id)
    {
        $idp = SamlIdentityProvider::where('tenant_id', tenant('id'))->findOrFail($id);
        return redirect()->to('/saml/login/' . $idp->id . '?relay=/admin/saml-idps/' . $idp->id);
    }
}
