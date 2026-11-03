<?php declare(strict_types=1);

use Rajtik\Migrations\Migration\Migration;
use Rajtik\Migrations\Schema\Blueprint;
use Rajtik\Migrations\Schema\Schema;


return new class extends Migration {
	public function up(Schema $schema): void
	{
		$schema->create('users', function (Blueprint $table): void {
			$table->id();
			$table->string('name');
			$table->string('email')->unique();
			$table->boolean('admin')->default(false);
			$table->timestamps();
		});
	}


	public function down(Schema $schema): void
	{
		$schema->dropIfExists('users');
	}
};
