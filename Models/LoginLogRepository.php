<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

class LoginLogRepository
{
    public function __construct(private PDO $db)
    {
    }

    public function recordLogin(array $data): int
    {
        $statement = $this->db->prepare(
            'INSERT INTO user_login_logs (user_id, google_id, ip_address, user_agent, country)
             VALUES (:user_id, :google_id, :ip_address, :user_agent, :country)'
        );
        
        $statement->execute([
            'user_id' => $data['user_id'],
            'google_id' => $data['google_id'] ?? null,
            'ip_address' => $data['ip_address'],
            'user_agent' => $data['user_agent'] ?? null,
            'country' => $data['country'] ?? null,
        ]);
        
        return (int)$this->db->lastInsertId();
    }

    public function findByIp(string $ipAddress, int $limit = 100): array
    {
        $statement = $this->db->prepare(
            'SELECT l.*, u.email, u.name, u.plan 
             FROM user_login_logs l
             JOIN users u ON l.user_id = u.id
             WHERE l.ip_address = :ip_address
             ORDER BY l.logged_in_at DESC
             LIMIT :limit'
        );
        $statement->bindValue('ip_address', $ipAddress, PDO::PARAM_STR);
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByGoogleId(string $googleId, int $limit = 100): array
    {
        $statement = $this->db->prepare(
            'SELECT l.*, u.email, u.name, u.plan 
             FROM user_login_logs l
             JOIN users u ON l.user_id = u.id
             WHERE l.google_id = :google_id
             ORDER BY l.logged_in_at DESC
             LIMIT :limit'
        );
        $statement->bindValue('google_id', $googleId, PDO::PARAM_STR);
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByUserId(int $userId, int $limit = 100): array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM user_login_logs 
             WHERE user_id = :user_id
             ORDER BY logged_in_at DESC
             LIMIT :limit'
        );
        $statement->bindValue('user_id', $userId, PDO::PARAM_INT);
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function searchByIpAndGoogleId(?string $ipAddress = null, ?string $googleId = null, int $limit = 100): array
    {
        $conditions = [];
        $params = [];
        
        if ($ipAddress !== null && $ipAddress !== '') {
            $conditions[] = 'l.ip_address = :ip_address';
            $params['ip_address'] = $ipAddress;
        }
        
        if ($googleId !== null && $googleId !== '') {
            $conditions[] = 'l.google_id = :google_id';
            $params['google_id'] = $googleId;
        }
        
        if (empty($conditions)) {
            return [];
        }
        
        $whereClause = implode(' AND ', $conditions);
        $params['limit'] = $limit;
        
        $statement = $this->db->prepare(
            "SELECT l.*, u.email, u.name, u.plan 
             FROM user_login_logs l
             JOIN users u ON l.user_id = u.id
             WHERE {$whereClause}
             ORDER BY l.logged_in_at DESC
             LIMIT :limit"
        );
        
        foreach ($params as $key => $value) {
            if ($key === 'limit') {
                $statement->bindValue($key, $value, PDO::PARAM_INT);
            } else {
                $statement->bindValue($key, $value, PDO::PARAM_STR);
            }
        }
        
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
}

