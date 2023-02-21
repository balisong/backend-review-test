<?php

declare(strict_types=1);

namespace App\Importer;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final class GitHubArchiveHttpStreamImporter implements GitHubArchiveImporter
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
            sprintf('https://data.gharchive.org/%s.json.gz', $importDateTime->format('Y-m-d-H')),
            [
                'headers' => [
                    'Accept-Encoding' => 'gzip',
                ]
            ]
        );

//        dd($response->getInfo());
//        dd($this->httpClient);

        foreach ($this->httpClient->stream($response) as $chunk) {
            dump('chunk content', $chunk->getContent());
//            dump($response->getInfo('debug'));
////            try {
//                $line = gzdecode($chunk->getContent());
//                $data = json_decode($line, true);
//                yield $data;
////            } catch (\Exception $e) {
////                dump($e->getMessage());
////            }
        }

    }
}
