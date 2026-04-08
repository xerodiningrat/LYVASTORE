<?php

use Illuminate\Support\Str;

$parseEmails = static function (?string $value): array {
    if (! filled($value)) {
        return [];
    }

    return collect(explode(',', (string) $value))
        ->map(fn (string $email) => Str::lower(trim($email)))
        ->filter()
        ->unique()
        ->values()
        ->all();
};

return [
    'owner_emails' => $parseEmails(env('OWNER_EMAILS')),
    'admin_emails' => $parseEmails(env('ADMIN_EMAILS')),
];
