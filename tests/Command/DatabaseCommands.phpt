<?php declare(strict_types=1);

use Contributte\Console\Application;
use Contributte\Console\DI\ConsoleExtension;
use Nette\Bootstrap\Configurator;
use Nette\Database\Connection;
use Rajtik\Console\DI\NetteConsoleExtension;
use Rajtik\Migrations\DI\MigrationsExtension;
use Rajtik\Migrations\Tests\fixtures\Factories\PostFactory;
use Rajtik\Migrations\Tests\fixtures\Factories\UserFactory;
use Symfony\Component\Console\Tester\ApplicationTester;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


function createCommandContainer(bool $debugMode = true): Nette\DI\Container
{
	$dir = createTempDir();
	$configurator = new Configurator;
	$configurator->setTempDirectory($dir);
	$configurator->setDebugMode($debugMode);
	$configurator->defaultExtensions['console'] = [ConsoleExtension::class, [true]];
	$configurator->defaultExtensions['netteConsole'] = [NetteConsoleExtension::class, [true]];
	$configurator->defaultExtensions['migrations'] = [MigrationsExtension::class, [true]];
	$configurator->addConfig([
		'database' => ['dsn' => "sqlite:$dir/db.sqlite"],
		'migrations' => [
			'directory' => __DIR__ . '/../fixtures/migrations',
			'seeders' => ['namespace' => 'Rajtik\Migrations\Tests\fixtures\Seeders'],
		],
		'services' => [UserFactory::class, PostFactory::class],
	]);
	return $configurator->createContainer();
}


/** @param array<string, mixed> $input */
function runCommand(Nette\DI\Container $container, array $input): ApplicationTester
{
	$application = $container->getByType(Application::class);
	$application->setAutoExit(false);
	$tester = new ApplicationTester($application);
	$tester->run($input, ['interactive' => false]);
	return $tester;
}


function rowCount(Nette\DI\Container $container, string $table): int
{
	return (int) $container->getByType(Connection::class)->fetchField('SELECT COUNT(*) FROM ?name', $table);
}


test('migrate, status, seed, rollback and reset', function () {
	$container = createCommandContainer();

	$tester = runCommand($container, ['command' => 'migrate']);
	Assert::same(0, $tester->getStatusCode());
	Assert::same(3, substr_count($tester->getDisplay(), 'Migrated'));
	Assert::contains('Nothing to migrate.', runCommand($container, ['command' => 'migrate'])->getDisplay());

	$status = runCommand($container, ['command' => 'migrate:status'])->getDisplay();
	Assert::same(3, substr_count($status, 'Ran'));

	$tester = runCommand($container, ['command' => 'db:seed']);
	Assert::same(0, $tester->getStatusCode());
	Assert::contains('Seeded Rajtik\Migrations\Tests\fixtures\Seeders\DatabaseSeeder', $tester->getDisplay());
	Assert::same(1, rowCount($container, 'users'));

	$tester = runCommand($container, ['command' => 'db:seed', '--class' => 'Missing']);
	Assert::same(1, $tester->getStatusCode());
	Assert::contains('not found', $tester->getDisplay());

	Assert::contains('Rolled back 2026_01_03_000000_add_bio_to_users_table', runCommand($container, ['command' => 'migrate:rollback', '--step' => '1'])->getDisplay());
	Assert::same(2, substr_count(runCommand($container, ['command' => 'migrate:reset'])->getDisplay(), 'Rolled back'));
	Assert::contains('Nothing to roll back.', runCommand($container, ['command' => 'migrate:rollback'])->getDisplay());
});


test('migrate:fresh --seed', function () {
	$container = createCommandContainer();
	runCommand($container, ['command' => 'migrate', '--seed' => true]);
	Assert::same(1, rowCount($container, 'users'));

	$tester = runCommand($container, ['command' => 'migrate:fresh', '--seed' => true]);
	Assert::same(0, $tester->getStatusCode());
	Assert::same(1, rowCount($container, 'users'));
	Assert::same(2, rowCount($container, 'posts'));
});


test('production mode requires confirmation or --force', function () {
	$container = createCommandContainer(debugMode: false);

	$tester = runCommand($container, ['command' => 'migrate']);
	Assert::same(1, $tester->getStatusCode());
	Assert::contains('Command cancelled.', $tester->getDisplay());

	Assert::same(0, runCommand($container, ['command' => 'migrate', '--force' => true])->getStatusCode());
	Assert::same(0, runCommand($container, ['command' => 'migrate:status'])->getStatusCode());
});
