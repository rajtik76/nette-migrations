<?php declare(strict_types=1);

namespace Rajtik\Migrations\Schema\Grammar;

use Nette\Database\Connection;
use Rajtik\Migrations\Schema\UnsupportedOperation;


final class GrammarFactory
{
	public static function forConnection(Connection $connection): Grammar
	{
		$driver = $connection->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);

		return match ($driver) {
			'sqlite' => new SqliteGrammar,
			'mysql' => new MySqlGrammar,
			'pgsql' => new PostgresGrammar,
			default => throw new UnsupportedOperation("Database driver '$driver' is not supported, use SQLite, MySQL/MariaDB or PostgreSQL."),
		};
	}
}
