<?php

namespace App\Support;

use Illuminate\Validation\Rules\Password;

class PasswordRules
{
    /**
     * Strong password for creating a new account password.
     *
     * @return array<int, mixed>
     */
    public static function required(): array
    {
        return ['required', 'confirmed', static::strength()];
    }

    /**
     * Strong password without confirmation (forms that only have one password field).
     *
     * @return array<int, mixed>
     */
    public static function requiredUnconfirmed(): array
    {
        return ['required', static::strength()];
    }

    /**
     * Strong password when the field is optional (leave blank to keep current).
     *
     * @return array<int, mixed>
     */
    public static function optional(): array
    {
        return ['nullable', 'confirmed', static::strength()];
    }

    public static function strength(): Password
    {
        return Password::min(8)
            ->letters()
            ->mixedCase()
            ->numbers()
            ->symbols();
    }

    public static function hint(): string
    {
        return __('Min 8 chars, upper & lower case, a number, and a special character');
    }
}
