<?php declare(strict_types=1);

use Nette\Database\Connection;
use Rajtik\Migrations\Database\SqliteForeignKeys;
use Rajtik\Migrations\Schema\Grammar\GrammarFactory;
use Rajtik\Migrations\Schema\Schema;

require __DIR__ . '/../vendor/autoload.php';

Tester\Environment::setup();
Tester\Environment::setupFunctions();


function createTempDir(): string
{
	$dir = __DIR__ . '/tmp/' . getmypid() . '-' . bin2hex(random_bytes(4));
	Nette\Utils\FileSystem::createDir($dir);
	register_shutdown_function(fn() => Nette\Utils\FileSystem::delete($dir));
	return $dir;
}


function sqliteConnection(): Connection
{
	$connection = new Connection('sqlite:' . createTempDir() . '/test.sqlite');
	SqliteForeignKeys::enable($connection);
	return $connection;
}


/**
 * SQLite always; MySQL, MariaDB and PostgreSQL when MIGRATIONS_TEST_<NAME> holds "dsn|user|password".
 * @return array<string, Connection>
 */
function testConnections(): array
{
	Nette\Utils\FileSystem::createDir(__DIR__ . '/tmp');
	Tester\Environment::lock('database', __DIR__ . '/tmp');

	$connections = ['sqlite' => sqliteConnection()];
	foreach (['mysql', 'mariadb', 'pgsql'] as $name) {
		$config = getenv('MIGRATIONS_TEST_' . strtoupper($name));
		if (is_string($config) && $config !== '') {
			$parts = explode('|', $config);
			$connections[$name] = new Connection($parts[0], $parts[1] ?? null, $parts[2] ?? null);
		}
	}

	foreach ($connections as $connection) {
		createSchema($connection)->dropAllTables();
	}

	return $connections;
}


function createSchema(Connection $connection): Schema
{
	return new Schema($connection, GrammarFactory::forConnection($connection));
}


/** @return list<string> */
function columnNames(Connection $connection, string $table): array
{
	return array_column($connection->getDriver()->getColumns($table), 'name');
}


function createMigrator(Connection $connection, string $directory): Rajtik\Migrations\Migration\Migrator
{
	$schema = createSchema($connection);

	return new Rajtik\Migrations\Migration\Migrator(
		new Rajtik\Migrations\Migration\MigrationFiles($directory),
		new Rajtik\Migrations\Migration\MigrationRepository($schema),
		$schema,
	);
}


function createExplorer(Connection $connection): Nette\Database\Explorer
{
	return new Nette\Database\Explorer($connection, new Nette\Database\Structure($connection, new Nette\Caching\Storages\DevNullStorage));
}
