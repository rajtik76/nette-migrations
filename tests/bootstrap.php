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
	return $dir;
}


/**
 * SQLite always; MySQL, MariaDB and PostgreSQL when MIGRATIONS_TEST_<NAME> holds "dsn|user|password".
 * @return array<string, Connection>
 */
function testConnections(): array
{
	Tester\Environment::lock('database', __DIR__ . '/tmp');

	$connections = ['sqlite' => new Connection('sqlite:' . createTempDir() . '/test.sqlite')];
	foreach (['mysql', 'mariadb', 'pgsql'] as $name) {
		$config = getenv('MIGRATIONS_TEST_' . strtoupper($name));
		if (is_string($config) && $config !== '') {
			$parts = explode('|', $config);
			$connections[$name] = new Connection($parts[0], $parts[1] ?? null, $parts[2] ?? null);
		}
	}

	foreach ($connections as $connection) {
		SqliteForeignKeys::enable($connection);
		createSchema($connection)->dropAllTables();
	}

	return $connections;
}


function createSchema(Connection $connection): Schema
{
	return new Schema($connection, GrammarFactory::forConnection($connection));
}
