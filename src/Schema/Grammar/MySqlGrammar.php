<?php declare(strict_types=1);

namespace Rajtik\Migrations\Schema\Grammar;

use Rajtik\Migrations\Schema\ColumnDefinition;
use Rajtik\Migrations\Schema\ColumnType;


final class MySqlGrammar extends Grammar
{
	public function wrap(string $identifier): string
	{
		return '`' . str_replace('`', '``', $identifier) . '`';
	}


	public function supportsTransactionalDdl(): bool
	{
		return false;
	}


	public function compileDropAllTables(array $tables): array
	{
		return [
			'set foreign_key_checks = 0',
			...array_map($this->compileDrop(...), $tables),
			'set foreign_key_checks = 1',
		];
	}


	protected function type(ColumnDefinition $column): string
	{
		return match ($column->type) {
			ColumnType::Id => 'bigint unsigned not null auto_increment primary key',
			ColumnType::Integer => 'int',
			ColumnType::BigInteger => 'bigint',
			ColumnType::ForeignId => 'bigint unsigned',
			ColumnType::String => 'varchar(' . $column->length . ')',
			ColumnType::Text => 'text',
			ColumnType::Boolean => 'tinyint(1)',
			ColumnType::Timestamp => 'timestamp',
		};
	}


	protected function tableOptions(): string
	{
		return ' default character set utf8mb4 collate utf8mb4_unicode_ci engine = InnoDB';
	}


	protected function quote(string $value): string
	{
		return "'" . str_replace(['\\', "'"], ['\\\\', "''"], $value) . "'";
	}


	protected function compileDropIndex(string $table, string $name): string
	{
		return 'drop index ' . $this->wrap($name) . ' on ' . $this->wrap($table);
	}


	protected function compileDropForeign(string $table, string $name): string
	{
		return 'alter table ' . $this->wrap($table) . ' drop foreign key ' . $this->wrap($name);
	}
}
