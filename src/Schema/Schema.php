<?php declare(strict_types=1);

namespace Rajtik\Migrations\Schema;

use Nette\Database\Connection;
use Rajtik\Migrations\Schema\Grammar\Grammar;


final readonly class Schema
{
	public function __construct(
		private Connection $connection,
		private Grammar $grammar,
	) {
	}


	/** @param \Closure(Blueprint): void $callback */
	public function create(string $table, \Closure $callback): void
	{
		$this->build(new Blueprint($table, creating: true), $callback);
	}


	/** @param \Closure(Blueprint): void $callback */
	public function table(string $table, \Closure $callback): void
	{
		$this->build(new Blueprint($table, creating: false), $callback);
	}


	public function drop(string $table): void
	{
		$this->statement($this->grammar->compileDrop($table));
	}


	public function dropIfExists(string $table): void
	{
		$this->statement($this->grammar->compileDropIfExists($table));
	}


	public function hasTable(string $table): bool
	{
		return in_array($table, $this->tables(), true);
	}


	public function dropAllTables(): void
	{
		foreach ($this->grammar->compileDropAllTables($this->tables()) as $sql) {
			$this->statement($sql);
		}
	}


	// raw PDO, because Nette's SQL preprocessor would treat `?` in string defaults as placeholders
	public function statement(string $sql): void
	{
		$this->connection->getPdo()->exec($sql);
	}


	/**
	 * @template T
	 * @param \Closure(): T $callback
	 * @return T
	 */
	public function transaction(\Closure $callback): mixed
	{
		return $this->grammar->supportsTransactionalDdl()
			? $this->connection->transaction($callback)
			: $callback();
	}


	public function connection(): Connection
	{
		return $this->connection;
	}


	/** @param \Closure(Blueprint): void $callback */
	private function build(Blueprint $blueprint, \Closure $callback): void
	{
		$callback($blueprint);

		foreach ($this->grammar->compile($blueprint) as $sql) {
			$this->statement($sql);
		}
	}


	/** @return list<string> */
	private function tables(): array
	{
		$tables = [];
		foreach ($this->connection->getDriver()->getTables() as $table) {
			if (!$table['view']) {
				$tables[] = $table['name'];
			}
		}

		return $tables;
	}
}
