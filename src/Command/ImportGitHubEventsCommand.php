<?php

declare(strict_types=1);

namespace App\Command;

use App\Dto\GitHubArchiveImportMessage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * This command must import GitHub events.
 * You can add the parameters and code you want in this command to meet the need.
 */
#[AsCommand(name: 'app:import-github-events')]
class ImportGitHubEventsCommand extends Command
{
    private MessageBusInterface $messageBus;

    public function __construct(MessageBusInterface $messageBus)
    {
        parent::__construct();
        $this->messageBus = $messageBus;
    }

    protected function configure(): void
    {
        $this->setDescription('Import GH events')
        ->addArgument('date', InputArgument::REQUIRED, 'The date to import in Y-m-d format')
        ->addOption('dateFrom', null, InputOption::VALUE_REQUIRED, 'The begin date to import in Y-m-d format');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('GitHubArchive Event Importer');

        $gitHubArchiveTimeZone = new \DateTimeZone('UTC');

        $inputDate = $input->getArgument('date');
        $inputDateFrom = $input->getOption('dateFrom') ?? $inputDate;

        $date = \DateTime::createFromFormat('Y-m-d', $inputDate, $gitHubArchiveTimeZone);
        $date->setTime(23, 59, 59, 59);

        $dateFrom = \DateTime::createFromFormat('Y-m-d', $inputDateFrom, $gitHubArchiveTimeZone);
        $dateFrom->setTime(0, 0, 0, 0);

        if ($dateFrom > $date) {
            $dateFrom->setDate((int)$date->format('Y'), (int)$date->format('m'), (int)$date->format('d'));
        }

        $interval = \DateInterval::createFromDateString('1 hour');
        $dateRange = new \DatePeriod($dateFrom, $interval, $date);

        $io->progressStart();
        $messagesCount = 0;

        /** @var \DateTime $dateTime */
        foreach($io->progressIterate($dateRange->getIterator()) as $dateTime) {
            $messagesCount++;
            $importDateTime = \DateTimeImmutable::createFromMutable($dateTime);
            try {
                $this->messageBus->dispatch(new GitHubArchiveImportMessage($importDateTime));
            } catch (\Exception $e) {
                $io->warning($e->getMessage());
            }
        }

        $io->info(sprintf('%s hourly import messages sent to bus, run workers to consume messages', $messagesCount));

        return Command::SUCCESS;
    }
}
