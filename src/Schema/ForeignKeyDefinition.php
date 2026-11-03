<?php declare(strict_types=1);

namespace Rajtik\Migrations\Schema;


final class ForeignKeyDefinition
{
	public private(set) string $referencedTable = '';

	/** @var list<string> */
	public private(set) array $referencedColumns = ['id'];

	public private(set) ?string $onDelete = null;
	public private(set) ?string $onUpdate = null;


	/** @param list<string> $columns */
	public function __construct(
		public readonly string $name,
		public readonly array $columns,
	) {
	}


	public function references(string ...$columns): self
	{
		$this->referencedColumns = array_values($columns);
		return $this;
	}


	public function on(string $table): self
	{
		$this->referencedTable = $table;
		return $this;
	}


	public function cascadeOnDelete(): self
	{
		$this->onDelete = 'cascade';
		return $this;
	}


	public function nullOnDelete(): self
	{
		$this->onDelete = 'set null';
		return $this;
	}


	public function restrictOnDelete(): self
	{
		$this->onDelete = 'restrict';
		return $this;
	}


	public function cascadeOnUpdate(): self
	{
		$this->onUpdate = 'cascade';
		return $this;
	}
}
