<?php declare(strict_types=1);

namespace Rajtik\Migrations\Migration;

use Nette\Database\IStructure;
use Rajtik\Migrations\Schema\Schema;


final readonly class Migrator
{
	public function __construct(
		private MigrationFiles $files,
		private MigrationRepository $repository,
		private Schema $schema,
		private ?IStructure $structure = null,
	) {
	}


	/**
	 * @param ?\Closure(string, float): void $onMigrated receives name and duration in ms
	 * @return list<string>
	 */
	public function run(?\Closure $onMigrated = null): array
	{
		$this->repository->ensureTable();
		$pending = array_keys(array_diff_key($this->files->all(), $this->repository->ran()));
		if ($pending === []) {
			return [];
		}

		$batch = $this->repository->lastBatch() + 1;
		foreach ($pending as $name) {
			$this->execute(
				$name,
				fn(Migration $migration) => $migration->up($this->schema),
				fn() => $this->repository->log($name, $batch),
				$onMigrated,
			);
		}

		$this->structure?->rebuild();
		return $pending;
	}


	/**
	 * @param int $steps number of migrations to roll back, 0 = the whole last batch
	 * @param ?\Closure(string, float): void $onMigrated
	 * @return list<string>
	 */
	public function rollback(int $steps = 0, ?\Closure $onMigrated = null): array
	{
		$this->repository->ensureTable();

		return $this->rollbackMigrations(
			$steps > 0 ? $this->repository->latest($steps) : $this->repository->lastBatchMigrations(),
			$onMigrated,
		);
	}


	/**
	 * @param ?\Closure(string, float): void $onMigrated
	 * @return list<string>
	 */
	public function reset(?\Closure $onMigrated = null): array
	{
		$this->repository->ensureTable();

		return $this->rollbackMigrations(array_reverse(array_keys($this->repository->ran())), $onMigrated);
	}


	/**
	 * @param ?\Closure(string, float): void $onMigrated
	 * @return list<string>
	 */
	public function fresh(?\Closure $onMigrated = null): array
	{
		$this->schema->dropAllTables();
		return $this->run($onMigrated);
	}


	/** @return list<MigrationStatus> */
	public function status(): array
	{
		$this->repository->ensureTable();
		$ran = $this->repository->ran();

		return array_map(
			fn(string $name): MigrationStatus => new MigrationStatus($name, $ran[$name] ?? null),
			array_keys($this->files->all()),
		);
	}


	/**
	 * @param list<string> $names
	 * @param ?\Closure(string, float): void $onMigrated
	 * @return list<string>
	 */
	private function rollbackMigrations(array $names, ?\Closure $onMigrated): array
	{
		foreach ($names as $name) {
			$this->execute(
				$name,
				fn(Migration $migration) => $migration->down($this->schema),
				fn() => $this->repository->delete($name),
				$onMigrated,
			);
		}

		if ($names !== []) {
			$this->structure?->rebuild();
		}

		return $names;
	}


	/**
	 * @param \Closure(Migration): void $apply
	 * @param \Closure(): void $record
	 * @param ?\Closure(string, float): void $onMigrated
	 */
	private function execute(string $name, \Closure $apply, \Closure $record, ?\Closure $onMigrated): void
	{
		$migration = $this->files->load($name);
		$start = hrtime(true);

		$this->schema->transaction(function () use ($migration, $apply, $record): void {
			$apply($migration);
			$record();
		});

		if ($onMigrated !== null) {
			$onMigrated($name, (hrtime(true) - $start) / 1e6);
		}
	}
}
