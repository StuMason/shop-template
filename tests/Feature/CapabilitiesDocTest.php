<?php

/**
 * Keeps CAPABILITIES.md honest in both directions so features can't get
 * lost or silently rebuilt:
 *  - forward: every source path and FLAG=value the doc cites must exist
 *  - reverse: every payment gateway, console command and swappable manager
 *    in the codebase must be documented
 */

use Illuminate\Support\Facades\File;

function capabilitiesDoc(): string
{
    return File::get(base_path('CAPABILITIES.md'));
}

it('references only source files that exist', function () {
    // Backticked tokens that look like repo paths with a known extension.
    preg_match_all(
        '/`([a-z][\w\/.-]+\.(?:php|tsx?|css|conf))`/',
        capabilitiesDoc(),
        $matches,
    );

    $missing = collect($matches[1])
        ->unique()
        ->reject(fn (string $path): bool => File::exists(base_path($path)))
        ->values();

    expect($missing->all())->toBe([], 'CAPABILITIES.md points at missing files: '.$missing->implode(', '));
});

it('references only env flags that exist in .env.example', function () {
    $envExample = File::get(base_path('.env.example'));

    // Backticked `FLAG=value` toggles only.
    preg_match_all('/`([A-Z][A-Z0-9_]{2,})=/', capabilitiesDoc(), $matches);

    $missing = collect($matches[1])
        ->unique()
        // Accept commented defaults (e.g. PASSPORT_*), so match anywhere.
        ->reject(fn (string $flag): bool => str_contains($envExample, $flag.'='))
        ->values();

    expect($missing->all())->toBe([], 'CAPABILITIES.md cites flags absent from .env.example: '.$missing->implode(', '));
});

it('documents every payment gateway', function () {
    $doc = capabilitiesDoc();

    $gateways = collect(File::files(app_path('Payments/Gateways')))
        ->map(fn ($file): string => $file->getFilenameWithoutExtension());

    $undocumented = $gateways
        ->reject(fn (string $class): bool => str_contains($doc, "Payments/Gateways/{$class}.php"))
        ->values();

    expect($undocumented->all())->toBe([], 'Undocumented payment gateways: '.$undocumented->implode(', '));
});

it('documents every scheduled command and swappable manager', function () {
    $doc = capabilitiesDoc();

    // Each console command's artisan signature must appear in the doc.
    $signatures = collect(File::files(app_path('Console/Commands')))
        ->map(fn ($file): ?string => preg_match(
            '/\$signature\s*=\s*[\'"]([^\'"]+)[\'"]/',
            File::get($file->getPathname()),
            $m,
        ) ? $m[1] : null)
        ->filter();

    $undocumentedCommands = $signatures
        ->reject(fn (string $signature): bool => str_contains($doc, $signature))
        ->values();

    expect($undocumentedCommands->all())->toBe([], 'Undocumented commands: '.$undocumentedCommands->implode(', '));

    // The swappable-driver managers must each be listed.
    $managers = [
        'app/Payments/PaymentManager.php',
        'app/AddressLookup/AddressLookupManager.php',
        'app/Support/SupportDrafter/SupportDrafterManager.php',
    ];

    foreach ($managers as $manager) {
        expect($doc)->toContain($manager);
    }
});
