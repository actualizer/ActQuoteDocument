<?php declare(strict_types=1);

use Shopware\Core\TestBootstrapper;

// Plugin lives at custom/plugins/ActQuoteDocument/; project root is four levels up.
$projectRootAutoloader = __DIR__ . '/../../../../vendor/autoload.php';

if (!file_exists($projectRootAutoloader)) {
    fwrite(STDERR, "Project-root autoloader not found at {$projectRootAutoloader}.\n");
    fwrite(STDERR, "Run `composer install` (with --dev) in the project root.\n");
    exit(1);
}

require $projectRootAutoloader;

// The project's .env.local pins APP_ENV=dev; the integration kernel needs APP_ENV=test.
$_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = 'test';
putenv('APP_ENV=test');

// This installation boots Shopware\Core\Kernel; .env.test names a non-existent App\Kernel.
$_SERVER['KERNEL_CLASS'] = $_ENV['KERNEL_CLASS'] = \Shopware\Core\Kernel::class;
putenv('KERNEL_CLASS=' . \Shopware\Core\Kernel::class);

// The DB user may only create databases matching `test` / `test_%`.
$testDatabaseUrl = getenv('TEST_DATABASE_URL')
    ?: 'mysql://db:db@db:3306/test_quote_document';

$classLoader = (new TestBootstrapper())
    ->addActivePlugins('ActQuoteDocument')
    ->setDatabaseUrl($testDatabaseUrl)
    ->bootstrap()
    ->getClassLoader();

$classLoader->addPsr4('Act\\QuoteDocument\\Tests\\', __DIR__ . '/');
