<?php

namespace App\Domain\Security\Services;

use App\Models\Central\User;
use App\Models\Central\WebauthnCredential;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorService
{
    public function __construct(protected Google2FA $google2fa) {}

    public function generateSecret(): array
    {
        $secret = $this->google2fa->generateSecretKey();
        $qrCodeUrl = $this->google2fa->getQRCodeUrl(
            config('app.name'),
            auth()->user()->email,
            $secret
        );

        return [
            'secret' => $secret,
            'qr_code_url' => $qrCodeUrl,
            'recovery_codes' => $this->generateRecoveryCodes(),
        ];
    }

    public function enable(string $secret, string $code, array $recoveryCodes): bool
    {
        if (! $this->google2fa->verifyKey($secret, $code)) {
            return false;
        }

        $user = auth()->user();
        $user->forceFill([
            'two_factor_secret' => encrypt($secret),
            'two_factor_recovery_codes' => encrypt(json_encode($recoveryCodes)),
            'two_factor_confirmed_at' => now(),
        ])->save();

        return true;
    }

    public function disable(): void
    {
        $user = auth()->user();
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();
    }

    public function verify(string $code): bool
    {
        $user = auth()->user();

        // Check recovery code first
        if ($this->verifyRecoveryCode($user, $code)) {
            return true;
        }

        // Check TOTP
        $secret = decrypt($user->two_factor_secret);
        return $this->google2fa->verifyKey($secret, $code);
    }

    public function verifyRecoveryCode(User $user, string $code): bool
    {
        $codes = json_decode(decrypt($user->two_factor_recovery_codes), true);
        foreach ($codes as $i => $storedCode) {
            if (Hash::check($code, $storedCode)) {
                unset($codes[$i]);
                $user->forceFill([
                    'two_factor_recovery_codes' => encrypt(json_encode(array_values($codes))),
                ])->save();
                return true;
            }
        }
        return false;
    }

    protected function generateRecoveryCodes(int $count = 8): array
    {
        return collect(range(1, $count))
            ->map(fn() => $this->generateRecoveryCode())
            ->toArray();
    }

    protected function generateRecoveryCode(): string
    {
        return strtoupper(substr(bin2hex(random_bytes(5)), 0, 5) . '-' . substr(bin2hex(random_bytes(5)), 0, 5));
    }
}
