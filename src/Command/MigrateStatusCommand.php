<?php declare(strict_types=1);

namespace Rajtik\Migrations\Command;

use Rajtik\Console\Command\Command;
use Rajtik\Console\Command\ExitCode;
use Rajtik\Migrations\Migration\Migrator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


#[AsCommand(name: 'migrate:status', description: 'Show the status of each migration')]
final class MigrateStatusCommand extends Command
{
	public function __construct(
		private readonly Migrator $migrator,
	) {
		parent::__construct();
	}


	protected function handle(InputInterface $input, OutputInterface $output): ExitCode
	{
		$statuses = $this->migrator->status();
		if ($statuses === []) {
			$output->writeln('No migrations found.');
			return ExitCode::Success;
		}

		$table = new Table($output);
		$table->setHeaders(['Migration', 'Batch', 'Status']);
		foreach ($statuses as $status) {
			$table->addRow([
				$status->name,
				$status->batch ?? '',
				$status->ran() ? '<info>Ran</info>' : '<comment>Pending</comment>',
			]);
		}

		$table->render();
		return ExitCode::Success;
	}
}
