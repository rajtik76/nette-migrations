<?php declare(strict_types=1);

namespace Rajtik\Migrations\Command;

use Rajtik\Console\Command\Command;
use Rajtik\Console\Command\ExitCode;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;


abstract class DatabaseCommand extends Command
{
	private bool $productionMode = false;


	final public function setProductionMode(bool $productionMode): void
	{
		$this->productionMode = $productionMode;
	}


	protected function configure(): void
	{
		$this->addOption('force', null, InputOption::VALUE_NONE, 'Run without confirmation in production');
	}


	protected function confirmToProceed(InputInterface $input, OutputInterface $output): bool
	{
		if (!$this->productionMode || $input->getOption('force')) {
			return true;
		}

		$question = new ConfirmationQuestion('<comment>Application is in production mode. Run the command anyway? [y/N]</comment> ', false);
		if ((new QuestionHelper)->ask($input, $output, $question)) {
			return true;
		}

		$output->writeln('<comment>Command cancelled.</comment>');
		return false;
	}


	/** @return \Closure(string, float): void */
	protected function printProgress(OutputInterface $output, string $verb): \Closure
	{
		return function (string $name, float $milliseconds) use ($output, $verb): void {
			$output->writeln(sprintf('  <info>%s</info> %s <comment>(%.2f ms)</comment>', $verb, $name, $milliseconds));
		};
	}


	protected function seed(OutputInterface $output): ExitCode
	{
		$status = $this->getApplication()?->find('db:seed')->run(new ArrayInput(['--force' => true]), $output);

		return ExitCode::tryFrom($status ?? ExitCode::Failure->value) ?? ExitCode::Failure;
	}
}
