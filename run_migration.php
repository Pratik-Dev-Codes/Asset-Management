<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Illuminate\Database\DatabaseManager;

// Ensure the current directory is the application's root
define('LARAVEL_START', microtime(true));

/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
|
| Composer provides a convenient, automatically generated class loader
| for our application. We just need to utilize it! We'll require it
| into the script here so that we do not have to worry about the
| loading of any of our classes manually. It's great to relax.
|
*/

require __DIR__.'/vendor/autoload.php';

/*
|--------------------------------------------------------------------------
| Turn On The Lights
|--------------------------------------------------------------------------
|
| We need to illuminate PHP development, so let's turn on the lights.
| This bootstraps the framework and gets it ready for use, then it
| will load up this application so that we can run it and send
| the responses back to the browser and delight our users.
|
*/

$app = require_once __DIR__.'/bootstrap/app.php';

/*
|--------------------------------------------------------------------------
| Run The Artisan Application
|--------------------------------------------------------------------------
|
| When we run the console application, the current CLI command will be
| executed in this console and the response sent back to a terminal
| or another output device for the developers. Here goes nothing!
|
*/

$kernel = $app->make(Kernel::class);

// Increase memory limit and disable time limit
ini_set('memory_limit', '2G');
set_time_limit(0);

// Bootstrap the application
$app->make('config');

// Disable query logging
if (app()->bound('db')) {
    app('db')->connection()->disableQueryLog();
}

// Run the migration with force option
$input = new ArgvInput(['artisan', 'migrate', '--force']);
$output = new ConsoleOutput();

// Run migrations one by one to reduce memory usage
$migrator = app('migrator');
$migrator->setOutput($output);

try {
    $migrator->run([database_path('migrations')], ['pretend' => false, 'step' => true]);
    $status = 0;
} catch (\Exception $e) {
    $output->writeln('<error>' . $e->getMessage() . '</error>');
    $status = 1;
}

$kernel->terminate($input, $status);

exit($status);
