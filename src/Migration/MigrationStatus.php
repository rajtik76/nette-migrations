<?php declare(strict_types=1);

namespace Rajtik\Migrations\Migration;


final readonly class MigrationStatus
{
	public function __construct(
		public string $name,
		public ?int $batch,
	) {
	}


	public function ran(): bool
	{
		return $this->batch !== null;
	}
}
