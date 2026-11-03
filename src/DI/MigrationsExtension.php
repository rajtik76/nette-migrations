<?php declare(strict_types=1);

namespace Rajtik\Migrations\DI;

use Nette\Database\Connection;
use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\ServiceDefinition;
use Nette\Schema\Expect;
use Nette\Schema\Schema as ConfigSchema;
use Rajtik\Migrations\Command\DatabaseCommand;
use Rajtik\Migrations\Command\DbSeedCommand;
use Rajtik\Migrations\Command\MakeFactoryCommand;
use Rajtik\Migrations\Command\MakeMigrationCommand;
use Rajtik\Migrations\Command\MakeSeederCommand;
use Rajtik\Migrations\Command\MigrateCommand;
use Rajtik\Migrations\Command\MigrateFreshCommand;
use Rajtik\Migrations\Command\MigrateResetCommand;
use Rajtik\Migrations\Command\MigrateRollbackCommand;
use Rajtik\Migrations\Command\MigrateStatusCommand;
use Rajtik\Migrations\Database\SqliteForeignKeys;
use Rajtik\Migrations\Migration\MigrationFiles;
use Rajtik\Migrations\Migration\MigrationRepository;
use Rajtik\Migrations\Migration\Migrator;
use Rajtik\Migrations\Schema\Grammar\Grammar;
use Rajtik\Migrations\Schema\Grammar\GrammarFactory;
use Rajtik\Migrations\Schema\Schema;
use Rajtik\Migrations\Seeding\SeederRunner;


/**
 * @property-read \stdClass $config
 */
final class MigrationsExtension extends CompilerExtension
{
	public function __construct(
		private readonly bool $cliMode = false,
	) {
	}


	public function getConfigSchema(): ConfigSchema
	{
		return Expect::structure([
			'table' => Expect::string('migrations'),
			'sqliteForeignKeys' => Expect::bool(true),
			'directory' => Expect::string()->nullable(),
			'stubs' => Expect::string()->nullable(),
			'seeders' => Expect::structure([
				'namespace' => Expect::string('Database\Seeders'),
				'directory' => Expect::string()->nullable(),
				'default' => Expect::string('DatabaseSeeder'),
			]),
			'factories' => Expect::structure([
				'namespace' => Expect::string('Database\Factories'),
				'directory' => Expect::string()->nullable(),
			]),
		]);
	}


	public function loadConfiguration(): void
	{
		if (!$this->cliMode) {
			return;
		}

		$builder = $this->getContainerBuilder();
		$config = $this->config;
		$databaseDir = $builder->parameters['rootDir'] . '/database';

		$builder->addDefinition($this->prefix('grammar'))
			->setType(Grammar::class)
			->setFactory([GrammarFactory::class, 'forConnection'])
			->setAutowired(false);

		$builder->addDefinition($this->prefix('schema'))
			->setFactory(Schema::class, ['grammar' => $this->prefix('@grammar')]);

		$builder->addDefinition($this->prefix('files'))
			->setFactory(MigrationFiles::class, [$config->directory ?? $databaseDir . '/migrations'])
			->setAutowired(false);

		$builder->addDefinition($this->prefix('repository'))
			->setFactory(MigrationRepository::class, ['table' => $config->table])
			->setAutowired(false);

		$builder->addDefinition($this->prefix('migrator'))
			->setFactory(Migrator::class, [
				'files' => $this->prefix('@files'),
				'repository' => $this->prefix('@repository'),
			]);

		$builder->addDefinition($this->prefix('seederRunner'))
			->setFactory(SeederRunner::class);

		$this->addDatabaseCommands([
			'migrate' => MigrateCommand::class,
			'migrateFresh' => MigrateFreshCommand::class,
			'migrateRollback' => MigrateRollbackCommand::class,
			'migrateReset' => MigrateResetCommand::class,
		]);

		$this->addDatabaseCommands(['dbSeed' => DbSeedCommand::class])['dbSeed']
			->setArguments(['namespace' => $config->seeders->namespace, 'defaultSeeder' => $config->seeders->default]);

		$builder->addDefinition($this->prefix('command.migrateStatus'))
			->setFactory(MigrateStatusCommand::class);

		$builder->addDefinition($this->prefix('command.makeMigration'))
			->setFactory(MakeMigrationCommand::class, [
				'directory' => $config->directory ?? $databaseDir . '/migrations',
				'stubDirectory' => $config->stubs,
			]);

		$builder->addDefinition($this->prefix('command.makeSeeder'))
			->setFactory(MakeSeederCommand::class, [
				'namespace' => $config->seeders->namespace,
				'directory' => $config->seeders->directory ?? $databaseDir . '/seeders',
				'stubDirectory' => $config->stubs,
			]);

		$builder->addDefinition($this->prefix('command.makeFactory'))
			->setFactory(MakeFactoryCommand::class, [
				'namespace' => $config->factories->namespace,
				'directory' => $config->factories->directory ?? $databaseDir . '/factories',
				'stubDirectory' => $config->stubs,
			]);
	}


	public function beforeCompile(): void
	{
		if (!$this->config->sqliteForeignKeys) {
			return;
		}

		foreach ($this->getContainerBuilder()->findByType(Connection::class) as $definition) {
			if (!$definition instanceof ServiceDefinition) {
				continue;
			}

			$definition->addSetup('$onConnect[]', [[SqliteForeignKeys::class, 'enable']]);

			// a non-lazy connection connects in its constructor, before onConnect handlers are added
			$arguments = $definition->getCreator()->arguments;
			$options = $arguments[3] ?? $arguments['options'] ?? [];
			if (!is_array($options) || empty($options['lazy'])) {
				$definition->addSetup([SqliteForeignKeys::class, 'enable'], ['@self']);
			}
		}
	}


	/**
	 * @param array<string, class-string<DatabaseCommand>> $commands
	 * @return array<string, ServiceDefinition>
	 */
	private function addDatabaseCommands(array $commands): array
	{
		$definitions = [];
		foreach ($commands as $name => $class) {
			$definitions[$name] = $this->getContainerBuilder()->addDefinition($this->prefix('command.' . $name))
				->setFactory($class)
				->addSetup('setProductionMode', [(bool) $this->getContainerBuilder()->parameters['productionMode']]);
		}

		return $definitions;
	}
}
