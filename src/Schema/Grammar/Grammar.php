<?php declare(strict_types=1);

namespace Rajtik\Migrations\Schema\Grammar;

use Rajtik\Migrations\Schema\Blueprint;
use Rajtik\Migrations\Schema\ColumnDefinition;
use Rajtik\Migrations\Schema\ColumnType;
use Rajtik\Migrations\Schema\ForeignKeyDefinition;
use Rajtik\Migrations\Schema\IndexDefinition;


abstract class Grammar
{
	abstract public function wrap(string $identifier): string;


	abstract public function supportsTransactionalDdl(): bool;


	/**
	 * @param list<string> $tables
	 * @return list<string>
	 */
	abstract public function compileDropAllTables(array $tables): array;


	abstract protected function type(ColumnDefinition $column): string;


	abstract protected function compileDropIndex(string $table, string $name): string;


	abstract protected function compileDropForeign(string $table, string $name): string;


	/** @return list<string> */
	public function compile(Blueprint $blueprint): array
	{
		return $blueprint->creating
			? $this->compileCreate($blueprint)
			: $this->compileAlter($blueprint);
	}


	public function compileDrop(string $table): string
	{
		return 'drop table ' . $this->wrap($table);
	}


	public function compileDropIfExists(string $table): string
	{
		return 'drop table if exists ' . $this->wrap($table);
	}


	/** @return list<string> */
	protected function compileCreate(Blueprint $blueprint): array
	{
		$definitions = array_map($this->compileColumn(...), $blueprint->columns());

		if ($blueprint->primaryKey() !== []) {
			$definitions[] = 'primary key (' . $this->columnize($blueprint->primaryKey()) . ')';
		}

		foreach ($blueprint->foreignKeys() as $foreignKey) {
			$definitions[] = 'constraint ' . $this->wrap($foreignKey->name) . ' ' . $this->compileForeignKey($foreignKey);
		}

		$create = 'create table ' . $this->wrap($blueprint->table) . " (\n\t" . implode(",\n\t", $definitions) . "\n)" . $this->tableOptions();

		return [$create, ...$this->compileIndexes($blueprint)];
	}


	/** @return list<string> */
	protected function compileAlter(Blueprint $blueprint): array
	{
		$table = $this->wrap($blueprint->table);
		$statements = [];

		foreach ($blueprint->droppedForeignKeys as $name) {
			$statements[] = $this->compileDropForeign($blueprint->table, $name);
		}

		foreach ($blueprint->droppedIndexes as $name) {
			$statements[] = $this->compileDropIndex($blueprint->table, $name);
		}

		foreach ($blueprint->droppedColumns as $column) {
			$statements[] = "alter table $table drop column " . $this->wrap($column);
		}

		foreach ($blueprint->columns() as $column) {
			$statements[] = $this->compileAddColumn($blueprint, $column);
		}

		foreach ($blueprint->foreignKeys() as $foreignKey) {
			array_push($statements, ...$this->compileAddForeign($blueprint, $foreignKey));
		}

		return [...$statements, ...$this->compileIndexes($blueprint)];
	}


	protected function compileAddColumn(Blueprint $blueprint, ColumnDefinition $column): string
	{
		return 'alter table ' . $this->wrap($blueprint->table) . ' add column ' . $this->compileColumn($column);
	}


	/** @return list<string> */
	protected function compileAddForeign(Blueprint $blueprint, ForeignKeyDefinition $foreignKey): array
	{
		return ['alter table ' . $this->wrap($blueprint->table) . ' add constraint ' . $this->wrap($foreignKey->name) . ' ' . $this->compileForeignKey($foreignKey)];
	}


	protected function compileColumn(ColumnDefinition $column): string
	{
		$sql = $this->wrap($column->name) . ' ' . $this->type($column);

		if ($column->type === ColumnType::Id) {
			return $sql;
		}

		$sql .= $column->nullable ? ' null' : ' not null';

		if ($column->useCurrent) {
			$sql .= ' default CURRENT_TIMESTAMP';
		} elseif ($column->hasDefault) {
			$sql .= ' default ' . $this->value($column->default);
		}

		return $column->primary ? $sql . ' primary key' : $sql;
	}


	protected function compileForeignKey(ForeignKeyDefinition $foreignKey): string
	{
		return 'foreign key (' . $this->columnize($foreignKey->columns) . ') ' . $this->compileReferences($foreignKey);
	}


	protected function compileReferences(ForeignKeyDefinition $foreignKey): string
	{
		$sql = 'references ' . $this->wrap($foreignKey->referencedTable) . ' (' . $this->columnize($foreignKey->referencedColumns) . ')';

		if ($foreignKey->onDelete !== null) {
			$sql .= ' on delete ' . $foreignKey->onDelete;
		}

		if ($foreignKey->onUpdate !== null) {
			$sql .= ' on update ' . $foreignKey->onUpdate;
		}

		return $sql;
	}


	/** @return list<string> */
	protected function compileIndexes(Blueprint $blueprint): array
	{
		return array_map(
			fn(IndexDefinition $index): string => 'create ' . ($index->unique ? 'unique ' : '') . 'index '
				. $this->wrap($index->name) . ' on ' . $this->wrap($blueprint->table) . ' (' . $this->columnize($index->columns) . ')',
			$blueprint->indexes(),
		);
	}


	protected function tableOptions(): string
	{
		return '';
	}


	protected function value(string|int|float|bool|null $value): string
	{
		return match (true) {
			$value === null => 'null',
			is_bool($value) => $value ? '1' : '0',
			is_string($value) => $this->quote($value),
			default => (string) $value,
		};
	}


	protected function quote(string $value): string
	{
		return "'" . str_replace("'", "''", $value) . "'";
	}


	/** @param list<string> $columns */
	protected function columnize(array $columns): string
	{
		return implode(', ', array_map($this->wrap(...), $columns));
	}
}
