<?php

declare(strict_types=1);

namespace App\Importer;

use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Serializer;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class GitHubArchiveHttpFileImporter implements GitHubArchiveImporter
{
    private HttpClientInterface $httpClient;

    private LoggerInterface $logger;

    public function __construct(HttpClientInterface $httpClient, LoggerInterface $logger)
    {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
    }

    /** @return iterable<array<string, mixed>> */
    public function import(\DateTimeInterface $importDateTime): iterable
    {
        $response = $this->httpClient->request(
            'GET',
            sprintf('https://data.gharchive.org/%s.json.gz', $importDateTime->format('Y-m-d-G')),
            [
                'headers' => [
                    'Accept-Encoding' => 'gzip',
                ]
            ]
        );

        $tmpFileName = tempnam(sys_get_temp_dir(), 'backend-review-test_gh-archive_');
        $fileHandler = fopen($tmpFileName, 'w');
        foreach ($this->httpClient->stream($response) as $chunk) {
            fwrite($fileHandler, $chunk->getContent());
        }

        $serializer = new Serializer([], [new JsonEncoder()]);

        $handle = gzopen($tmpFileName, 'r');
        while(!feof($handle)){
            $line_of_text = fgets($handle);

            try {
                yield $serializer->decode($line_of_text, 'json');
            } catch (\Exception $e) {
                $this->logger->error($e->getTraceAsString());
            }
        }

        unlink($tmpFileName);
    }
}
