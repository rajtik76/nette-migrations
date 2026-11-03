<?php declare(strict_types=1);

namespace Rajtik\Migrations\Migration;

use Rajtik\Migrations\Schema\Schema;


abstract class Migration
{
	abstract public function up(Schema $schema): void;


	public function down(Schema $schema): void
	{
	}
}
