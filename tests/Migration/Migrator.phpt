<?php declare(strict_types=1);

use Nette\Utils\FileSystem;
use Rajtik\Migrations\Migration\MigrationException;
use Rajtik\Migrations\Migration\MigrationFiles;
use Rajtik\Migrations\Migration\MigrationStatus;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

const Users = '2026_01_01_000000_create_users_table';
const Posts = '2026_01_02_000000_create_posts_table';
const Bio = '2026_01_03_000000_add_bio_to_users_table';


function addMigration(string $directory, string $name): void
{
	FileSystem::copy(__DIR__ . "/../fixtures/migrations/$name.php", "$directory/$name.php");
}


/** @return array<string, ?int> */
function batches(Rajtik\Migrations\Migration\Migrator $migrator): array
{
	$batches = [];
	foreach ($migrator->status() as $status) {
		$batches[$status->name] = $status->batch;
	}

	return $batches;
}


foreach (testConnections() as $driver => $connection) {
	test("$driver: batches, rollback, reset and fresh", function () use ($connection) {
		$directory = createTempDir();
		addMigration($directory, Users);
		addMigration($directory, Posts);
		$migrator = createMigrator($connection, $directory);
		$schema = createSchema($connection);

		$reported = [];
		Assert::same([Users, Posts], $migrator->run(function (string $name, float $milliseconds) use (&$reported): void {
			$reported[] = $name;
		}));
		Assert::same([Users, Posts], $reported);
		Assert::same([], $migrator->run());

		addMigration($directory, Bio);
		Assert::same([Bio], $migrator->run());
		Assert::same([Users => 1, Posts => 1, Bio => 2], batches($migrator));
		Assert::contains('bio', columnNames($connection, 'users'));

		Assert::same([Bio], $migrator->rollback());
		Assert::notContains('bio', columnNames($connection, 'users'));
		Assert::equal(new MigrationStatus(Bio, null), $migrator->status()[2]);

		Assert::same([Posts], $migrator->rollback(steps: 1));
		Assert::false($schema->hasTable('posts'));

		Assert::same([Users], $migrator->reset());
		Assert::false($schema->hasTable('users'));
		Assert::true($schema->hasTable('migrations'));
		Assert::same([], $migrator->rollback());

		Assert::same([Users, Posts, Bio], $migrator->run());
		$connection->query('INSERT INTO users ?', ['name' => 'Jane', 'email' => 'jane@example.com']);

		Assert::same([Users, Posts, Bio], $migrator->fresh());
		Assert::same(0, (int) $connection->fetchField('SELECT COUNT(*) FROM users'));
		Assert::same([Users => 1, Posts => 1, Bio => 1], batches($migrator));
	});
}


test('migration file must return a Migration', function () {
	$directory = createTempDir();
	FileSystem::write("$directory/2026_01_01_000000_broken.php", '<?php return 42;');

	Assert::exception(
		fn() => (new MigrationFiles($directory))->load('2026_01_01_000000_broken'),
		MigrationException::class,
		'%a%must return an instance of%a%',
	);
	Assert::exception(fn() => (new MigrationFiles($directory))->load('missing'), MigrationException::class);
	Assert::same([], (new MigrationFiles($directory . '/none'))->all());
});
