<?php

declare(strict_types=1);

/**
 * Every <x-icon-{name}> reference in the app must have a matching
 * resources/views/components/icon-{name}.blade.php file. A missing one
 * doesn't show up until that specific view is actually rendered — e.g.
 * icon-file-x sat broken for a long time because nothing in the test
 * suite ever rendered the public invoice page — so this scans every
 * Blade file directly instead of relying on incidental render coverage.
 */
test('every referenced icon component exists', function () {
    $existing = collect(glob(resource_path('views/components/icon-*.blade.php')))
        ->map(fn (string $path) => str(basename($path))
            ->beforeLast('.blade.php')
            ->after('icon-')
            ->toString())
        ->all();

    $missing = [];

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(resource_path('views'), FilesystemIterator::SKIP_DOTS)
    );

    foreach ($files as $file) {
        if (! str($file->getFilename())->endsWith('.blade.php')) {
            continue;
        }

        $content = file_get_contents($file->getPathname());

        // Dynamic icon names (e.g. <x-icon-arrow-{{ $dir }}) can't be statically
        // resolved — skip those (an atomic group so the engine can't backtrack
        // into a shorter, artificially-static-looking match).
        preg_match_all('/<x-icon-(?>[a-z0-9-]+)(?!\{\{)/', $content, $matches);

        foreach ($matches[0] as $fullMatch) {
            $name = rtrim(str($fullMatch)->after('<x-icon-')->toString(), '-');
            if ($name !== '' && ! in_array($name, $existing, true)) {
                $missing[] = "icon-{$name} (referenced in ".str_replace(resource_path('views').'/', '', $file->getPathname()).')';
            }
        }
    }

    expect(array_unique($missing))->toBe([]);
});
