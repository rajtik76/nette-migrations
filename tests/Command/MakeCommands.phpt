<?php declare(strict_types=1);

use Nette\Utils\FileSystem;
use Nette\Utils\Finder;
use Rajtik\Console\Generator\FileGenerator;
use Rajtik\Migrations\Command\MakeFactoryCommand;
use Rajtik\Migrations\Command\MakeMigrationCommand;
use Rajtik\Migrations\Command\MakeSeederCommand;
use Rajtik\Migrations\Migration\Migration;
use Symfony\Component\Console\Tester\CommandTester;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


function assertValidPhp(string $file): string
{
	exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file), result_code: $result);
	Assert::same(0, $result);

	$source = FileSystem::read($file);
	Assert::true(str_starts_with($source, "<?php declare(strict_types=1);\n"));
	return $source;
}


/** @param array<string, mixed> $input */
function makeMigration(array $input): string
{
	$directory = createTempDir();
	$tester = new CommandTester(new MakeMigrationCommand(new FileGenerator, $directory));
	Assert::same(0, $tester->execute($input));

	$files = iterator_to_array(Finder::findFiles('*.php')->in($directory), preserve_keys: false);
	Assert::count(1, $files);
	Assert::match('~^\d{4}_\d{2}_\d{2}_\d{6}_[a-z0-9_]+\.php$~', $files[0]->getFilename());
	return (string) $files[0];
}


test('migration stub is inferred from the name', function () {
	$file = makeMigration(['name' => 'create_posts_table']);
	Assert::contains('_create_posts_table.php', $file);
	Assert::contains("\$schema->create('posts'", assertValidPhp($file));
	Assert::type(Migration::class, require $file);

	$file = makeMigration(['name' => 'AddBioToUsersTable']);
	Assert::contains('_add_bio_to_users_table.php', $file);
	Assert::contains("\$schema->table('users'", assertValidPhp($file));

	$source = assertValidPhp(makeMigration(['name' => 'cleanup']));
	Assert::notContains('$schema->', $source);
});


test('--create and --table override inference', function () {
	Assert::contains("\$schema->create('roles'", assertValidPhp(makeMigration(['name' => 'roles', '--create' => 'roles'])));
	Assert::contains("\$schema->table('roles'", assertValidPhp(makeMigration(['name' => 'seed_roles', '--table' => 'roles'])));
});


test('invalid migration name fails', function () {
	$tester = new CommandTester(new MakeMigrationCommand(new FileGenerator, createTempDir()));

	Assert::same(1, $tester->execute(['name' => '123']));
	Assert::contains('not a valid migration name', $tester->getDisplay());
});


test('make:seeder', function () {
	$directory = createTempDir();
	(new CommandTester(new MakeSeederCommand(new FileGenerator, 'Database\Seeders', $directory)))->execute(['name' => 'Blog/User']);

	$source = assertValidPhp($directory . '/Blog/UserSeeder.php');
	Assert::contains('namespace Database\Seeders\Blog;', $source);
	Assert::contains('final class UserSeeder extends Seeder', $source);
});


test('make:factory derives table from the name', function () {
	$directory = createTempDir();
	$tester = new CommandTester(new MakeFactoryCommand(new FileGenerator, 'Database\Factories', $directory));

	$tester->execute(['name' => 'BlogCategory']);
	$source = assertValidPhp($directory . '/BlogCategoryFactory.php');
	Assert::contains('final class BlogCategoryFactory extends Factory', $source);
	Assert::contains("return 'blog_categories';", $source);

	$tester->execute(['name' => 'Person', '--table' => 'people']);
	Assert::contains("return 'people';", assertValidPhp($directory . '/PersonFactory.php'));
});
