<?php declare(strict_types=1);

namespace Rajtik\Migrations\Database;

use Nette\Database\Connection;


// SQLite ignores foreign key constraints unless enabled for each connection
final class SqliteForeignKeys
{
	public static function enable(Connection $connection): void
	{
		if ($connection->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'sqlite') {
			$connection->getPdo()->exec('pragma foreign_keys = on');
		}
	}
}
