<?php declare(strict_types=1);

namespace Rajtik\Migrations\Schema;


final readonly class IndexDefinition
{
	/** @param list<string> $columns */
	public function __construct(
		public string $name,
		public array $columns,
		public bool $unique,
	) {
	}
}
