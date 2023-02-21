<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\GitHubEventsImportService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * This command must import GitHub events.
 * You can add the parameters and code you want in this command to meet the need.
 */
#[AsCommand(name: 'app:import-github-events')]
class ImportGitHubEventsCommand extends Command
{
    private GitHubEventsImportService $eventsImportService;

    public function __construct(GitHubEventsImportService $eventsImportService)
    {
        parent::__construct();
        $this->eventsImportService = $eventsImportService;
    }

    protected function configure(): void
    {
        $this->setDescription('Import GH events')
        ->addArgument('date', InputArgument::REQUIRED, 'The date to import in Y-m-d format')
        ->addOption('dateFrom', null, InputOption::VALUE_REQUIRED, 'The begin date to import in Y-m-d format');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $time_start = microtime(true);

        $io = new SymfonyStyle($input, $output);
        $io->title('GitHubArchive Event Importer');

        $gitHubArchiveTimeZone = new \DateTimeZone('UTC');

        $inputDate = $input->getArgument('date');
        $inputDateFrom = $input->getOption('dateFrom') ?? $inputDate;

        $date = \DateTime::createFromFormat('Y-m-d', $inputDate, $gitHubArchiveTimeZone);
        $date->setTime(23, 59, 59, 59);

        $dateFrom = \DateTime::createFromFormat('Y-m-d', $inputDateFrom, $gitHubArchiveTimeZone);
        $dateFrom->setTime(0, 0, 0, 0);

        $interval = \DateInterval::createFromDateString('1 hour');
        $dateRange = new \DatePeriod($dateFrom, $interval, $date);

        /** @var \DateTime $dateTime */
        foreach($dateRange as $dateTime) {
            $io->text('importing '.$dateTime->format('Y-m-d H:i:s'));
            $importDateTime = \DateTimeImmutable::createFromMutable($dateTime);
            $this->eventsImportService->importEvents($importDateTime);
//            $this->eventsImportService->importEventsWithCache($importDateTime);
        }

        $time_end = microtime(true);
        dump('execution time', $time_end - $time_start);

        return 1;
    }
}
