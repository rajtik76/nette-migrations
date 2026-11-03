<?php declare(strict_types=1);

namespace Rajtik\Migrations\Migration;

use Rajtik\Migrations\Schema\Blueprint;
use Rajtik\Migrations\Schema\Schema;


final readonly class MigrationRepository
{
	public function __construct(
		private Schema $schema,
		private string $table = 'migrations',
	) {
	}


	public function ensureTable(): void
	{
		if ($this->schema->hasTable($this->table)) {
			return;
		}

		$this->schema->create($this->table, function (Blueprint $table): void {
			$table->id();
			$table->string('migration');
			$table->integer('batch');
		});
	}


	/** @return array<string, int> name => batch */
	public function ran(): array
	{
		$ran = $this->schema->connection()
			->query('SELECT migration, batch FROM ?name ORDER BY id', $this->table)
			->fetchPairs('migration', 'batch');

		return array_map(intval(...), $ran);
	}


	public function lastBatch(): int
	{
		return (int) $this->schema->connection()->fetchField('SELECT MAX(batch) FROM ?name', $this->table);
	}


	/** @return list<string> newest first */
	public function lastBatchMigrations(): array
	{
		return $this->names('SELECT migration FROM ?name WHERE batch = ? ORDER BY id DESC', $this->table, $this->lastBatch());
	}


	/** @return list<string> newest first */
	public function latest(int $count): array
	{
		return $this->names('SELECT migration FROM ?name ORDER BY id DESC LIMIT ?', $this->table, $count);
	}


	public function log(string $migration, int $batch): void
	{
		$this->schema->connection()->query('INSERT INTO ?name ?', $this->table, ['migration' => $migration, 'batch' => $batch]);
	}


	public function delete(string $migration): void
	{
		$this->schema->connection()->query('DELETE FROM ?name WHERE migration = ?', $this->table, $migration);
	}


	/**
	 * @param literal-string $sql
	 * @return list<string>
	 */
	private function names(string $sql, mixed ...$params): array
	{
		$names = $this->schema->connection()->query($sql, ...$params)->fetchPairs(null, 'migration');

		return array_values(array_map(strval(...), $names));
	}
}
