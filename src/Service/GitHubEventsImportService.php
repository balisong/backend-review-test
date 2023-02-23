<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\GitHubArchiveImportMessage;
use App\Entity\Event;
use App\Entity\EventType;
use App\Exception\UnsupportedEventTypeException;
use App\Importer\GitHubArchiveHttpFileImporter;
use App\Importer\GitHubArchiveImporter;
use App\Repository\ReadActorRepository;
use App\Repository\ReadEventRepository;
use App\Repository\ReadRepoRepository;
use App\Repository\WriteActorRepository;
use App\Repository\WriteEventRepository;
use App\Repository\WriteRepoRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

#[AsMessageHandler]
final class GitHubEventsImportService
{
    private GitHubArchiveImporter $gitHubArchiveImporter;

    private ReadActorRepository $readActorRepository;

    private ReadEventRepository $readEventRepository;

    private ReadRepoRepository $readRepoRepository;

    private WriteActorRepository $writeActorRepository;

    private WriteEventRepository $writeEventRepository;

    private WriteRepoRepository $writeRepoRepository;

    private CacheInterface $cache;

    private LoggerInterface $logger;

    public function __construct(
        GitHubArchiveHttpFileImporter $fileImporter,
        ReadActorRepository $readActorRepository,
        ReadEventRepository $readEventRepository,
        ReadRepoRepository $readRepoRepository,
        WriteActorRepository $writeActorRepository,
        WriteEventRepository $writeEventRepository,
        WriteRepoRepository $writeRepoRepository,
        CacheInterface $cache,
        LoggerInterface $logger
    )
    {
        $this->gitHubArchiveImporter = $fileImporter;
        $this->readActorRepository = $readActorRepository;
        $this->readEventRepository = $readEventRepository;
        $this->readRepoRepository = $readRepoRepository;
        $this->writeActorRepository = $writeActorRepository;
        $this->writeEventRepository = $writeEventRepository;
        $this->writeRepoRepository = $writeRepoRepository;
        $this->cache = $cache;
        $this->logger = $logger;
    }

    public function __invoke(GitHubArchiveImportMessage $archiveImportMessage):void
    {
        $this->importEvents($archiveImportMessage->getArchiveDateTime());
    }

    public function importEvents(\DateTimeInterface $importDateTime): void
    {
        $this->logger->info(sprintf('Begin GitHubArchive import for %s', $importDateTime->format('Y-m-d H:i:s')));

        $serializer = new Serializer([
            new DateTimeNormalizer(),
            new ObjectNormalizer(null, new CamelCaseToSnakeCaseNameConverter())
        ]);

        $rawEventsIterator = $this->gitHubArchiveImporter->import($importDateTime);
        foreach ($rawEventsIterator as $rawEvent) {
            try {
                $rawEvent['type'] = $this->mapGitHubEventType($rawEvent['type']);

                /** @var Event $event */
                $event = $serializer->denormalize($rawEvent, Event::class);
//                $event = Event::fromArray($rawEvent);

                $actorExist = $this->cache->get('exist_actor_'.$event->actor()->id(), function (ItemInterface $item) use ($event) {
                    $item->expiresAfter(3600);
                    return $this->readActorRepository->exist($event->actor()->id());
                });
                if(!$actorExist) {
                    $this->writeActorRepository->create($event->actor());
                    $this->cache->delete('exist_actor_'.$event->actor()->id());
                }

                $repoExist = $this->cache->get('exist_repo_'.$event->repo()->id(), function (ItemInterface $item) use ($event) {
                    $item->expiresAfter(3600);
                    return $this->readRepoRepository->exist($event->repo()->id());
                });
                if (!$repoExist) {
                    $this->writeRepoRepository->create($event->repo());
                    $this->cache->delete('exist_repo_'.$event->repo()->id());
                }

                if (!$this->readEventRepository->exist($event->id())) {
                    $this->writeEventRepository->create($event);
                }
            } catch (UnsupportedEventTypeException $e) {
                $this->logger->debug($e->getMessage());
            }
        }
    }

    /**
     * Map GitHub event type to EventType
     *
     * @throws UnsupportedEventTypeException
     */
    private function mapGitHubEventType(string $eventType): string
    {
        switch ($eventType) {
            case 'PushEvent':
                return EventType::COMMIT;
            case 'CommitCommentEvent':
            case 'IssueCommentEvent':
            case 'PullRequestReviewCommentEvent':
                return EventType::COMMENT;
            case 'PullRequestEvent':
                return EventType::PULL_REQUEST;
            default:
                throw new UnsupportedEventTypeException(sprintf('GitHub event type "%s" is not mapped', $eventType));
        }
    }
}
