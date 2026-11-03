<?php declare(strict_types=1);

use Rajtik\Migrations\Migration\Migration;
use Rajtik\Migrations\Schema\Blueprint;
use Rajtik\Migrations\Schema\Schema;


return new class extends Migration {
	public function up(Schema $schema): void
	{
		$schema->table('users', function (Blueprint $table): void {
			$table->text('bio')->nullable();
		});
	}


	public function down(Schema $schema): void
	{
		$schema->table('users', function (Blueprint $table): void {
			$table->dropColumn('bio');
		});
	}
};
