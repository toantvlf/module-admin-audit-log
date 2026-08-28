<?php
declare(strict_types=1);

/**
 * Minimal PSR-4 autoloader for the module's own namespace — deliberately NOT the module's
 * root composer.json autoload, because that declares magento/module-* dependencies that
 * require repo.magento.com credentials and aren't needed to unit-test plain-PHP classes.
 * Only classes with zero Magento dependencies can be safely autoloaded and tested this way
 * (same convention as module-ai-copilot/tests/bootstrap.php and
 * module-ai-merchandiser/tests/bootstrap.php).
 */
spl_autoload_register(static function (string $class): void {
    $prefix  = 'TVTCommerce\\AdminAuditLog\\';
    $baseDir = dirname(__DIR__) . '/';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file     = $baseDir . str_replace('\\', '/', $relative) . '.php';

    if (is_file($file)) {
        require $file;
    }
});

require __DIR__ . '/vendor/autoload.php';

// Only Model\ParamSanitizer is tested here — it has zero Magento dependencies by design.
// Add a tests/stubs/Magento tree + guarded require here (same pattern as
// module-ai-copilot/tests/bootstrap.php) if a future test needs to mock a Magento class.
