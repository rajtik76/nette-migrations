<?php declare(strict_types=1);

namespace Rajtik\Migrations\Schema;


class ColumnDefinition
{
	public private(set) bool $nullable = false;
	public private(set) bool $hasDefault = false;
	public private(set) string|int|float|bool|null $default = null;
	public private(set) bool $useCurrent = false;
	public private(set) bool $primary = false;
	public private(set) bool $unique = false;
	public private(set) bool $index = false;


	public function __construct(
		public readonly string $name,
		public readonly ColumnType $type,
		public readonly ?int $length = null,
	) {
	}


	public function nullable(bool $nullable = true): static
	{
		$this->nullable = $nullable;
		return $this;
	}


	public function default(string|int|float|bool|null $value): static
	{
		$this->hasDefault = true;
		$this->default = $value;
		return $this;
	}


	public function useCurrent(): static
	{
		$this->useCurrent = true;
		return $this;
	}


	public function primary(): static
	{
		$this->primary = true;
		return $this;
	}


	public function unique(): static
	{
		$this->unique = true;
		return $this;
	}


	public function index(): static
	{
		$this->index = true;
		return $this;
	}
}
