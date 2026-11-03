<?php declare(strict_types=1);

namespace Rajtik\Migrations\Schema;

use Rajtik\Migrations\Support\Inflector;


final class ForeignIdColumnDefinition extends ColumnDefinition
{
	public function __construct(
		private readonly Blueprint $blueprint,
		string $name,
	) {
		parent::__construct($name, ColumnType::ForeignId);
	}


	/** Table defaults to the plural of the column name without `_id`, e.g. `user_id` → `users`. */
	public function constrained(?string $table = null, string $column = 'id'): ForeignKeyDefinition
	{
		$table ??= Inflector::plural((string) preg_replace('~_id$~', '', $this->name));

		return $this->blueprint->foreign($this->name)->references($column)->on($table);
	}
}
