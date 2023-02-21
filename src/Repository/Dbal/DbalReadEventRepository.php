<?php

declare(strict_types=1);

namespace App\Repository\Dbal;

use App\Dto\SearchInput;
use App\Repository\ReadEventRepository;
use Doctrine\DBAL\Connection;

class DbalReadEventRepository implements ReadEventRepository
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function countAll(SearchInput $searchInput): int
    {
        $sql = <<<SQL
        SELECT sum(count) as count
        FROM event
        WHERE date(create_at) = :date
        AND CAST(payload as text) like :keyword
SQL;

        return (int) $this->connection->fetchOne($sql, [
            'date' => $searchInput->date->format('Y-m-d H:i:s'),
            'keyword' => '%'.$searchInput->keyword.'%'
        ]);
    }

    public function countByType(SearchInput $searchInput): array
    {
        $sql = <<<SQL
            SELECT type, sum(count) as count
            FROM event
            WHERE date(create_at) = :date
            AND CAST(payload as text) LIKE :keyword
            GROUP BY type
SQL;

        return $this->connection->fetchAllKeyValue($sql, [
            'date' => $searchInput->date->format('Y-m-d H:i:s'),
            'keyword' => '%'.$searchInput->keyword.'%'
        ]);
    }

    public function statsByTypePerHour(SearchInput $searchInput): array
    {
        $sql = <<<SQL
            SELECT extract(hour from create_at) as hour, CASE type
                WHEN 'COM' THEN 'commit'
                WHEN 'PR' THEN 'pullRequest'
                WHEN 'MSG' THEN 'comment'
            END as type, sum(count) as count
            FROM event
            WHERE date(create_at) = :date
            AND CAST(payload as text) like :keyword
            GROUP BY TYPE, EXTRACT(hour from create_at)
SQL;

        $stats = $this->connection->fetchAllAssociative($sql, [
            'date' => $searchInput->date->format('Y-m-d H:i:s'),
            'keyword' => '%'.$searchInput->keyword.'%'
        ]);

        $data = array_fill(0, 24, ['commit' => 0, 'pullRequest' => 0, 'comment' => 0]);

        foreach ($stats as $stat) {
            $data[(int) $stat['hour']][$stat['type']] = $stat['count'];
        }

        return $data;
    }

    public function getLatest(SearchInput $searchInput): array
    {
        $sql = <<<SQL
            SELECT e.type, to_json(r.*) as repo
            FROM (
                SELECT type, MAX(id) as max_id
                FROM event
                WHERE date(create_at) = :date AND CAST(payload as text) like :keyword
                GROUP BY type
                ORDER BY max_id DESC
            ) as filtered_event
            LEFT JOIN event e ON (filtered_event.max_id = e.id)
            LEFT JOIN repo r ON (e.repo_id = r.id)
SQL;

        $result = $this->connection->fetchAllAssociative($sql, [
            'date' => $searchInput->date->format('Y-m-d H:i:s'),
            'keyword' => '%'.$searchInput->keyword.'%'
        ]);

        $result = array_map(static function($item) {
            $item['repo'] = json_decode($item['repo'], true);

            return $item;
        }, $result);

        return $result;
    }

    public function exist(int $id): bool
    {
        $sql = <<<SQL
            SELECT 1
            FROM event
            WHERE id = :id
        SQL;

        $result = $this->connection->fetchOne($sql, [
            'id' => $id
        ]);

        return (bool) $result;
    }
}
