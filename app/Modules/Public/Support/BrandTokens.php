<?php

namespace App\Modules\Public\Support;

use App\Modules\Branches\Models\Branch;

class BrandTokens
{
    private const FALLBACKS = [
        'primary_color'   => '#1a1a2e',
        'secondary_color' => '#16213e',
        'accent_color'    => '#e94560',
    ];

    public static function fromBranch(?Branch $branch): array
    {
        if (! $branch) {
            return self::defaults();
        }

        return [
            'business_name'   => $branch->business_name ?? $branch->name,
            'tagline'         => $branch->tagline,
            'story'           => $branch->story,
            'logo_path'       => $branch->logo_path ? asset('storage/'.$branch->logo_path) : null,
            'cover_path'      => $branch->cover_path ? asset('storage/'.$branch->cover_path) : null,
            'primary_color'   => $branch->primary_color   ?? self::FALLBACKS['primary_color'],
            'secondary_color' => $branch->secondary_color ?? self::FALLBACKS['secondary_color'],
            'accent_color'    => $branch->accent_color    ?? self::FALLBACKS['accent_color'],
            'whatsapp'        => $branch->whatsapp,
            'instagram_url'   => $branch->instagram_url,
            'facebook_url'    => $branch->facebook_url,
            'tiktok_url'      => $branch->tiktok_url,
            'google_maps_url' => $branch->google_maps_url,
            'opening_hours'   => $branch->opening_hours ?? [],
            'is_public'       => (bool) $branch->is_public,
            'public_slug'     => $branch->public_slug,
            'currency_code'   => $branch->currency_code,
        ];
    }

    public static function cssVars(array $tokens): string
    {
        return implode(';', [
            '--brand-primary: '   . $tokens['primary_color'],
            '--brand-secondary: ' . $tokens['secondary_color'],
            '--brand-accent: '    . $tokens['accent_color'],
        ]);
    }

    private static function defaults(): array
    {
        return [
            'business_name'   => config('app.name'),
            'tagline'         => null,
            'story'           => null,
            'logo_path'       => null,
            'cover_path'      => null,
            'primary_color'   => self::FALLBACKS['primary_color'],
            'secondary_color' => self::FALLBACKS['secondary_color'],
            'accent_color'    => self::FALLBACKS['accent_color'],
            'whatsapp'        => null,
            'instagram_url'   => null,
            'facebook_url'    => null,
            'tiktok_url'      => null,
            'google_maps_url' => null,
            'opening_hours'   => [],
            'is_public'       => false,
            'public_slug'     => null,
            'currency_code'   => 'USD',
        ];
    }
}
