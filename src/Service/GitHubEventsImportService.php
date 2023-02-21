<?php

declare(strict_types=1);

namespace App\Service;

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
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

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

    public function __construct(
        GitHubArchiveHttpFileImporter $fileImporter,
        ReadActorRepository $readActorRepository,
        ReadEventRepository $readEventRepository,
        ReadRepoRepository $readRepoRepository,
        WriteActorRepository $writeActorRepository,
        WriteEventRepository $writeEventRepository,
        WriteRepoRepository $writeRepoRepository,
        CacheInterface $cache
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
    }

    public function importEvents(\DateTimeInterface $importDateTime)
    {
        $serializer = new Serializer([
            new DateTimeNormalizer(),
            new ObjectNormalizer(null, new CamelCaseToSnakeCaseNameConverter())
        ]);

        $rawEventsIterator = $this->gitHubArchiveImporter->import($importDateTime);
        foreach ($rawEventsIterator as $rawEvent) {
            try {
                $rawEvent['type'] = $this->mapGitHubEventType($rawEvent['type']);
//                dump($rawEvent);

                /** @var Event $event */
                $event = $serializer->denormalize($rawEvent, Event::class);
//                dd($event);

                if(!$this->readActorRepository->exist($event->actor()->id)) {
                    $this->writeActorRepository->create($event->actor());
                }

                if (!$this->readRepoRepository->exist($event->repo()->id())) {
                    $this->writeRepoRepository->create($event->repo());
                }

                if (!$this->readEventRepository->exist($event->id())) {
                    $this->writeEventRepository->create($event);
                }
            } catch (UnsupportedEventTypeException $e) {
            }
        }
    }

    public function importEventsWithCache(\DateTimeInterface $importDateTime)
    {
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

                $actorExist = $this->cache->get('exist_actor_'.$event->actor()->id(), function (ItemInterface $item) use ($event) {
                    $item->expiresAfter(3600);
                    return $this->readActorRepository->exist($event->actor()->id);
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
            } catch (\Exception $e) {
                dump($e->getMessage());
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
