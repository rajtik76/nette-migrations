<?php declare(strict_types=1);

namespace Rajtik\Migrations\Factory;

use Faker\Generator;
use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;


abstract class Factory
{
	private static ?Generator $faker = null;

	/** @var list<array<string, mixed>|\Closure(array<string, mixed>): array<string, mixed>> */
	private array $states = [];


	public function __construct(
		private readonly Explorer $database,
	) {
	}


	abstract protected function table(): string;


	/** @return array<string, mixed> */
	abstract protected function definition(): array;


	/** @param array<string, mixed>|\Closure(array<string, mixed>): array<string, mixed> $state */
	public function state(array|\Closure $state): static
	{
		$clone = clone $this;
		$clone->states[] = $state;
		return $clone;
	}


	/**
	 * @param array<string, mixed> $attributes
	 * @return array<string, mixed>
	 */
	public function make(array $attributes = []): array
	{
		$data = $this->definition();
		foreach ([...$this->states, $attributes] as $state) {
			$data = $state instanceof \Closure ? [...$data, ...$state($data)] : [...$data, ...$state];
		}

		return $data;
	}


	/**
	 * @param array<string, mixed> $attributes
	 * @return list<array<string, mixed>>
	 */
	public function makeMany(int $count, array $attributes = []): array
	{
		return $count > 0 ? array_map(fn() => $this->make($attributes), range(1, $count)) : [];
	}


	/** @param array<string, mixed> $attributes */
	public function create(array $attributes = []): ActiveRow
	{
		$row = $this->database->table($this->table())->insert($this->make($attributes));

		return $row instanceof ActiveRow
			? $row
			: throw new \LogicException("Table '{$this->table()}' must have a primary key to return the created row.");
	}


	/**
	 * @param array<string, mixed> $attributes
	 * @return list<ActiveRow>
	 */
	public function createMany(int $count, array $attributes = []): array
	{
		return $count > 0 ? array_map(fn() => $this->create($attributes), range(1, $count)) : [];
	}


	protected function faker(): Generator
	{
		if (!class_exists(\Faker\Factory::class)) {
			throw new \LogicException('Factories need Faker: composer require --dev fakerphp/faker');
		}

		return self::$faker ??= \Faker\Factory::create();
	}
}
