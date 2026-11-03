<?php declare(strict_types=1);

use Contributte\Console\Application;
use Contributte\Console\DI\ConsoleExtension;
use Nette\Bootstrap\Configurator;
use Nette\Database\Connection;
use Rajtik\Console\DI\NetteConsoleExtension;
use Rajtik\Migrations\DI\MigrationsExtension;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


function createContainer(bool $cliMode, bool $lazy, bool $foreignKeys = true): Nette\DI\Container
{
	$dir = createTempDir();
	$configurator = new Configurator;
	$configurator->setTempDirectory($dir);
	$configurator->setDebugMode(true);
	$configurator->defaultExtensions['console'] = [ConsoleExtension::class, [$cliMode]];
	$configurator->defaultExtensions['netteConsole'] = [NetteConsoleExtension::class, [$cliMode]];
	$configurator->defaultExtensions['migrations'] = [MigrationsExtension::class, [$cliMode]];
	$configurator->addConfig([
		'database' => ['dsn' => "sqlite:$dir/db.sqlite", 'options' => ['lazy' => $lazy]],
		'migrations' => ['sqliteForeignKeys' => $foreignKeys],
	]);
	return $configurator->createContainer();
}


function foreignKeysEnabled(Nette\DI\Container $container): bool
{
	return (bool) $container->getByType(Connection::class)->fetchField('pragma foreign_keys');
}


test('enables SQLite foreign keys for eager and lazy connections', function () {
	Assert::true(foreignKeysEnabled(createContainer(cliMode: false, lazy: false)));
	Assert::true(foreignKeysEnabled(createContainer(cliMode: false, lazy: true)));
	Assert::false(foreignKeysEnabled(createContainer(cliMode: false, lazy: true, foreignKeys: false)));
});


test('registers commands in console mode only', function () {
	$application = createContainer(cliMode: true, lazy: true)->getByType(Application::class);
	foreach (['migrate', 'migrate:fresh', 'migrate:rollback', 'migrate:reset', 'migrate:status', 'db:seed', 'make:migration', 'make:seeder', 'make:factory'] as $command) {
		Assert::true($application->has($command), $command);
	}

	Assert::null(createContainer(cliMode: false, lazy: true)->getByType(Application::class, throw: false));
});
