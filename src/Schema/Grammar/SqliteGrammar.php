<?php declare(strict_types=1);

namespace Rajtik\Migrations\Schema\Grammar;

use Rajtik\Migrations\Schema\Blueprint;
use Rajtik\Migrations\Schema\ColumnDefinition;
use Rajtik\Migrations\Schema\ColumnType;
use Rajtik\Migrations\Schema\ForeignKeyDefinition;
use Rajtik\Migrations\Schema\UnsupportedOperation;


// SQLite cannot alter constraints of an existing table; a foreign key may only be added inline with a new column
final class SqliteGrammar extends Grammar
{
	public function wrap(string $identifier): string
	{
		return '"' . str_replace('"', '""', $identifier) . '"';
	}


	public function supportsTransactionalDdl(): bool
	{
		return true;
	}


	public function compileDropAllTables(array $tables): array
	{
		return [
			'pragma foreign_keys = off',
			...array_map($this->compileDrop(...), $tables),
			'pragma foreign_keys = on',
		];
	}


	protected function compileAddColumn(Blueprint $blueprint, ColumnDefinition $column): string
	{
		$sql = parent::compileAddColumn($blueprint, $column);
		$foreignKey = $this->inlineForeignKey($blueprint, $column);

		return $foreignKey !== null ? $sql . ' ' . $this->compileReferences($foreignKey) : $sql;
	}


	protected function compileAddForeign(Blueprint $blueprint, ForeignKeyDefinition $foreignKey): array
	{
		foreach ($blueprint->columns() as $column) {
			if ($this->inlineForeignKey($blueprint, $column) === $foreignKey) {
				return [];
			}
		}

		throw new UnsupportedOperation("SQLite cannot add foreign key '$foreignKey->name' to an existing column.");
	}


	protected function type(ColumnDefinition $column): string
	{
		return match ($column->type) {
			ColumnType::Id => 'integer primary key autoincrement not null',
			ColumnType::Integer, ColumnType::BigInteger, ColumnType::ForeignId => 'integer',
			ColumnType::String => 'varchar',
			ColumnType::Text => 'text',
			ColumnType::Boolean => 'tinyint(1)',
			ColumnType::Timestamp => 'datetime',
		};
	}


	protected function compileDropIndex(string $table, string $name): string
	{
		return 'drop index ' . $this->wrap($name);
	}


	protected function compileDropForeign(string $table, string $name): string
	{
		throw new UnsupportedOperation("SQLite cannot drop foreign key '$name'.");
	}


	private function inlineForeignKey(Blueprint $blueprint, ColumnDefinition $column): ?ForeignKeyDefinition
	{
		foreach ($blueprint->foreignKeys() as $foreignKey) {
			if ($foreignKey->columns === [$column->name]) {
				return $foreignKey;
			}
		}

		return null;
	}
}
