<?php

declare(strict_types=1);

namespace App;

/**
 * All runtime configuration comes from environment variables.
 *
 * CI/CD lesson: the SAME Docker image runs locally, in CI and in production.
 * Only the environment variables change - never the code.
 */
final class Config
{
    public function __construct(
        public readonly string $supabaseUrl,
        public readonly string $supabaseKey,
        public readonly string $appVersion,
        public readonly string $appEnv,
    ) {
    }

    public static function fromEnvironment(): self
    {
        return new self(
            supabaseUrl: rtrim(self::env('SUPABASE_URL'), '/'),
            supabaseKey: self::env('SUPABASE_SECRET_KEY'),
            appVersion: self::env('APP_VERSION', 'dev'),
            appEnv: self::env('APP_ENV', 'production'),
        );
    }

    public function isDatabaseConfigured(): bool
    {
        return $this->supabaseUrl !== '' && $this->supabaseKey !== '';
    }

    private static function env(string $name, string $default = ''): string
    {
        $value = getenv($name);

        return ($value === false || $value === '') ? $default : $value;
    }
}
