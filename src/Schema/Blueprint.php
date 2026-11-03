<?php declare(strict_types=1);

namespace Rajtik\Migrations\Schema;


final class Blueprint
{
	/** @var list<ColumnDefinition> */
	private array $columns = [];

	/** @var list<IndexDefinition> */
	private array $indexes = [];

	/** @var list<ForeignKeyDefinition> */
	private array $foreignKeys = [];

	/** @var list<string> */
	private array $primaryKey = [];

	/** @var list<string> */
	public private(set) array $droppedColumns = [];

	/** @var list<string> */
	public private(set) array $droppedIndexes = [];

	/** @var list<string> */
	public private(set) array $droppedForeignKeys = [];


	public function __construct(
		public readonly string $table,
		public readonly bool $creating,
	) {
	}


	public function id(string $name = 'id'): ColumnDefinition
	{
		return $this->addColumn(new ColumnDefinition($name, ColumnType::Id));
	}


	public function integer(string $name): ColumnDefinition
	{
		return $this->addColumn(new ColumnDefinition($name, ColumnType::Integer));
	}


	public function bigInteger(string $name): ColumnDefinition
	{
		return $this->addColumn(new ColumnDefinition($name, ColumnType::BigInteger));
	}


	public function foreignId(string $name): ForeignIdColumnDefinition
	{
		return $this->addColumn(new ForeignIdColumnDefinition($this, $name));
	}


	public function string(string $name, int $length = 255): ColumnDefinition
	{
		return $this->addColumn(new ColumnDefinition($name, ColumnType::String, $length));
	}


	public function text(string $name): ColumnDefinition
	{
		return $this->addColumn(new ColumnDefinition($name, ColumnType::Text));
	}


	public function boolean(string $name): ColumnDefinition
	{
		return $this->addColumn(new ColumnDefinition($name, ColumnType::Boolean));
	}


	public function timestamp(string $name): ColumnDefinition
	{
		return $this->addColumn(new ColumnDefinition($name, ColumnType::Timestamp));
	}


	public function timestamps(): void
	{
		$this->timestamp('created_at')->nullable();
		$this->timestamp('updated_at')->nullable();
	}


	public function primary(string ...$columns): void
	{
		$this->primaryKey = array_values($columns);
	}


	/** @param string|list<string> $columns */
	public function unique(string|array $columns, ?string $name = null): void
	{
		$this->addIndex((array) $columns, unique: true, name: $name);
	}


	/** @param string|list<string> $columns */
	public function index(string|array $columns, ?string $name = null): void
	{
		$this->addIndex((array) $columns, unique: false, name: $name);
	}


	public function foreign(string ...$columns): ForeignKeyDefinition
	{
		$columns = array_values($columns);

		return $this->foreignKeys[] = new ForeignKeyDefinition($this->indexName($columns, 'foreign'), $columns);
	}


	public function dropColumn(string ...$columns): void
	{
		array_push($this->droppedColumns, ...$columns);
	}


	public function dropIndex(string $name): void
	{
		$this->droppedIndexes[] = $name;
	}


	public function dropUnique(string $name): void
	{
		$this->droppedIndexes[] = $name;
	}


	public function dropForeign(string $name): void
	{
		$this->droppedForeignKeys[] = $name;
	}


	/** @return list<ColumnDefinition> */
	public function columns(): array
	{
		return $this->columns;
	}


	/** @return list<string> */
	public function primaryKey(): array
	{
		return $this->primaryKey;
	}


	/** @return list<IndexDefinition> */
	public function indexes(): array
	{
		$fromColumns = [];
		foreach ($this->columns as $column) {
			if ($column->unique) {
				$fromColumns[] = new IndexDefinition($this->indexName([$column->name], 'unique'), [$column->name], true);
			}

			if ($column->index) {
				$fromColumns[] = new IndexDefinition($this->indexName([$column->name], 'index'), [$column->name], false);
			}
		}

		return [...$fromColumns, ...$this->indexes];
	}


	/** @return list<ForeignKeyDefinition> */
	public function foreignKeys(): array
	{
		return $this->foreignKeys;
	}


	/**
	 * @template T of ColumnDefinition
	 * @param T $column
	 * @return T
	 */
	private function addColumn(ColumnDefinition $column): ColumnDefinition
	{
		$this->columns[] = $column;
		return $column;
	}


	/** @param array<string> $columns */
	private function addIndex(array $columns, bool $unique, ?string $name): void
	{
		$columns = array_values($columns);
		$this->indexes[] = new IndexDefinition($name ?? $this->indexName($columns, $unique ? 'unique' : 'index'), $columns, $unique);
	}


	/** @param list<string> $columns */
	private function indexName(array $columns, string $type): string
	{
		return strtolower(implode('_', [$this->table, ...$columns, $type]));
	}
}
