<?php

namespace App\Support;

use App\Models\Client;

class ClientDisplay
{
    /**
     * Human-readable client label (external account — not the internal Company entity).
     */
    public static function label(?Client $client): string
    {
        if (! $client) {
            return '---';
        }

        $person = trim(($client->first_name ?? '').' '.($client->last_name ?? ''));
        $organization = trim((string) ($client->company_name ?? ''));

        if ($person !== '' && $organization !== '') {
            return $person.' — '.$organization;
        }

        if ($person !== '') {
            return $person;
        }

        return $organization !== '' ? $organization : '---';
    }

    public static function organization(?Client $client): string
    {
        if (! $client) {
            return '---';
        }

        $organization = trim((string) ($client->company_name ?? ''));

        return $organization !== '' ? $organization : '---';
    }
}
