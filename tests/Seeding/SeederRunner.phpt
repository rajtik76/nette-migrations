<?php declare(strict_types=1);

use Nette\Bootstrap\Configurator;
use Nette\Database\Connection;
use Nette\Database\Explorer;
use Rajtik\Migrations\Seeding\Seeder;
use Rajtik\Migrations\Seeding\SeederRunner;
use Rajtik\Migrations\Tests\fixtures\Factories\PostFactory;
use Rajtik\Migrations\Tests\fixtures\Factories\UserFactory;
use Rajtik\Migrations\Tests\fixtures\Seeders\DatabaseSeeder;
use Rajtik\Migrations\Tests\fixtures\Seeders\PostSeeder;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


function createRunner(Connection $connection): SeederRunner
{
	$configurator = new Configurator;
	$configurator->setTempDirectory(createTempDir());
	$configurator->setDebugMode(true);
	$configurator->addConfig(['services' => [
		'explorer' => ['type' => Explorer::class, 'imported' => true],
		UserFactory::class,
		PostFactory::class,
	]]);
	$configurator->addServices(['explorer' => createExplorer($connection)]);

	return new SeederRunner($configurator->createContainer());
}


foreach (testConnections() as $driver => $connection) {
	test("$driver: runs seeder with nested calls and injected factories", function () use ($connection) {
		createMigrator($connection, __DIR__ . '/../fixtures/migrations')->run();

		$seeded = [];
		createRunner($connection)->run(DatabaseSeeder::class, function (string $class, float $milliseconds) use (&$seeded): void {
			$seeded[] = $class;
		});

		Assert::same([PostSeeder::class, DatabaseSeeder::class], $seeded);
		Assert::same(1, (int) $connection->fetchField('SELECT COUNT(*) FROM users WHERE admin = ?', true));
		Assert::same(2, (int) $connection->fetchField('SELECT COUNT(*) FROM posts'));
	});
}


test('rejects classes that are not seeders', function () {
	Assert::exception(
		fn() => createRunner(sqliteConnection())->run(stdClass::class),
		InvalidArgumentException::class,
	);
});


test('call() requires the runner', function () {
	$seeder = new class extends Seeder {
		public function run(): void
		{
			$this->call(PostSeeder::class);
		}
	};

	Assert::exception(fn() => $seeder->run(), LogicException::class);
});
