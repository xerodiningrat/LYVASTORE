<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;

class MobileUserAccess
{
    public function makeAccessToken(User $user): string
    {
        $signature = hash_hmac('sha256', $this->signaturePayload($user), (string) config('app.key'));

        return 'lyva.'.$user->id.'.'.$signature;
    }

    public function resolveUserFromRequest(Request $request): ?User
    {
        return $this->resolveUserFromToken($this->extractToken($request));
    }

    public function resolveUserFromToken(?string $submittedToken): ?User
    {
        $token = trim((string) $submittedToken);

        if ($token === '') {
            return null;
        }

        $parts = explode('.', $token);

        if (count($parts) !== 3 || $parts[0] !== 'lyva' || ! ctype_digit($parts[1])) {
            return null;
        }

        $user = User::query()->find((int) $parts[1]);

        if (! $user) {
            return null;
        }

        return hash_equals($this->makeAccessToken($user), $token) ? $user : null;
    }

    public function extractToken(Request $request): ?string
    {
        $bearerToken = trim((string) $request->bearerToken());

        if ($bearerToken !== '') {
            return $bearerToken;
        }

        $headerToken = trim((string) $request->header('X-Lyva-Mobile-Token'));

        if ($headerToken !== '') {
            return $headerToken;
        }

        $queryToken = trim((string) $request->query('token'));

        return $queryToken !== '' ? $queryToken : null;
    }

    private function signaturePayload(User $user): string
    {
        return implode('|', [
            (string) $user->id,
            (string) $user->email,
            (string) $user->password,
            (string) ($user->whatsapp_number ?? ''),
            (string) optional($user->updated_at)->timestamp,
            (string) optional($user->created_at)->timestamp,
        ]);
    }
}
