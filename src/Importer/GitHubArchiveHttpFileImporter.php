<?php

declare(strict_types=1);

namespace App\Importer;

use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Serializer;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class GitHubArchiveHttpFileImporter implements GitHubArchiveImporter
{
    private HttpClientInterface $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

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

//        if (200 !== $response->getStatusCode()) {
//            throw new \Exception('...');
//        }

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
                $json = $serializer->decode($line_of_text, 'json');
                yield $json;
            } catch (\Exception $e) {
                dump($e->getMessage());
            }
        }

        unlink($tmpFileName);
    }
}
