<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant\ScimToken;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ScimTokenController extends Controller
{
    public function index()
    {
        $tokens = ScimToken::where('tenant_id', tenant('id'))->paginate(20);
        return response()->json($tokens);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:200',
            'expires_at' => 'nullable|date',
        ]);

        $rawToken = Str::random(60);
        $token = ScimToken::create([
            'id' => Str::uuid(),
            'tenant_id' => tenant('id'),
            'name' => $data['name'],
            'token_hash' => hash('sha256', $rawToken),
            'expires_at' => $data['expires_at'] ?? null,
        ]);

        return response()->json([
            'token' => $rawToken,  // shown only ONCE
            'scim_token' => $token->makeHidden('token_hash'),
            'message' => 'Save this token — it will not be shown again.',
        ], 201);
    }

    public function destroy(string $id)
    {
        $token = ScimToken::where('tenant_id', tenant('id'))->findOrFail($id);
        $token->delete();
        return response()->noContent();
    }
}
