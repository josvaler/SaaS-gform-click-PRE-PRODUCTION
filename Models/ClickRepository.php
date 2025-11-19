<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

class ClickRepository
{
    public function __construct(private PDO $db)
    {
    }

    public function recordClick(array $data): int
    {
        $statement = $this->db->prepare(
            'INSERT INTO clicks (short_link_id, ip_address, user_agent, device_type, country, referrer)
             VALUES (:short_link_id, :ip_address, :user_agent, :device_type, :country, :referrer)'
        );
        
        $statement->execute([
            'short_link_id' => $data['short_link_id'],
            'ip_address' => $data['ip_address'],
            'user_agent' => $data['user_agent'] ?? null,
            'device_type' => $data['device_type'] ?? null,
            'country' => $data['country'] ?? null,
            'referrer' => $data['referrer'] ?? null,
        ]);
        
        return (int)$this->db->lastInsertId();
    }

    public function getClickStats(int $shortLinkId): array
    {
        $statement = $this->db->prepare(
            'SELECT COUNT(*) as total_clicks FROM clicks WHERE short_link_id = :short_link_id'
        );
        $statement->execute(['short_link_id' => $shortLinkId]);
        return $statement->fetch(PDO::FETCH_ASSOC);
    }

    public function getClicksByLink(int $shortLinkId, int $limit = 100, int $offset = 0): array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM clicks 
             WHERE short_link_id = :short_link_id 
             ORDER BY clicked_at DESC 
             LIMIT :limit OFFSET :offset'
        );
        $statement->bindValue('short_link_id', $shortLinkId, PDO::PARAM_INT);
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->bindValue('offset', $offset, PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getClicksByDateRange(int $shortLinkId, string $startDate, string $endDate): array
    {
        $statement = $this->db->prepare(
            'SELECT DATE(clicked_at) as date, COUNT(*) as clicks 
             FROM clicks 
             WHERE short_link_id = :short_link_id 
             AND clicked_at >= :start_date 
             AND clicked_at <= :end_date
             GROUP BY DATE(clicked_at)
             ORDER BY date ASC'
        );
        $statement->execute([
            'short_link_id' => $shortLinkId,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDeviceStats(int $shortLinkId): array
    {
        $statement = $this->db->prepare(
            'SELECT device_type, COUNT(*) as count 
             FROM clicks 
             WHERE short_link_id = :short_link_id 
             AND device_type IS NOT NULL
             GROUP BY device_type 
             ORDER BY count DESC'
        );
        $statement->execute(['short_link_id' => $shortLinkId]);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCountryStats(int $shortLinkId): array
    {
        $statement = $this->db->prepare(
            'SELECT country, COUNT(*) as count 
             FROM clicks 
             WHERE short_link_id = :short_link_id 
             AND country IS NOT NULL
             GROUP BY country 
             ORDER BY count DESC'
        );
        $statement->execute(['short_link_id' => $shortLinkId]);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getHourlyStats(int $shortLinkId, int $days = 30): array
    {
        $statement = $this->db->prepare(
            'SELECT HOUR(clicked_at) as hour, COUNT(*) as count 
             FROM clicks 
             WHERE short_link_id = :short_link_id 
             AND clicked_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
             GROUP BY HOUR(clicked_at)
             ORDER BY hour ASC'
        );
        $statement->bindValue('short_link_id', $shortLinkId, PDO::PARAM_INT);
        $statement->bindValue('days', $days, PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDailyClicks(int $shortLinkId, int $days = 30): array
    {
        $statement = $this->db->prepare(
            'SELECT DATE(clicked_at) as date, COUNT(*) as clicks 
             FROM clicks 
             WHERE short_link_id = :short_link_id 
             AND clicked_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
             GROUP BY DATE(clicked_at)
             ORDER BY date ASC'
        );
        $statement->bindValue('short_link_id', $shortLinkId, PDO::PARAM_INT);
        $statement->bindValue('days', $days, PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
}

