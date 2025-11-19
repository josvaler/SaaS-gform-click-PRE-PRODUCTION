<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

class ShortLinkRepository
{
    public function __construct(private PDO $db)
    {
    }

    public function create(array $data): array
    {
        $statement = $this->db->prepare(
            'INSERT INTO short_links (user_id, original_url, short_code, label, expires_at, is_active, has_preview_page, qr_code_path)
             VALUES (:user_id, :original_url, :short_code, :label, :expires_at, :is_active, :has_preview_page, :qr_code_path)'
        );
        
        $statement->execute([
            'user_id' => $data['user_id'],
            'original_url' => $data['original_url'],
            'short_code' => $data['short_code'],
            'label' => $data['label'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
            'is_active' => $data['is_active'] ?? 1,
            'has_preview_page' => $data['has_preview_page'] ?? 0,
            'qr_code_path' => $data['qr_code_path'] ?? null,
        ]);
        
        $id = (int)$this->db->lastInsertId();
        return $this->findById($id);
    }

    public function findById(int $id): ?array
    {
        $statement = $this->db->prepare('SELECT * FROM short_links WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $link = $statement->fetch(PDO::FETCH_ASSOC);
        return $link ?: null;
    }

    public function findByShortCode(string $shortCode): ?array
    {
        $statement = $this->db->prepare('SELECT * FROM short_links WHERE short_code = :short_code LIMIT 1');
        $statement->execute(['short_code' => $shortCode]);
        $link = $statement->fetch(PDO::FETCH_ASSOC);
        return $link ?: null;
    }

    public function findByUserId(int $userId, int $limit = 50, int $offset = 0): array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM short_links 
             WHERE user_id = :user_id 
             ORDER BY created_at DESC 
             LIMIT :limit OFFSET :offset'
        );
        $statement->bindValue('user_id', $userId, PDO::PARAM_INT);
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->bindValue('offset', $offset, PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countByUserId(int $userId): int
    {
        $statement = $this->db->prepare('SELECT COUNT(*) FROM short_links WHERE user_id = :user_id');
        $statement->execute(['user_id' => $userId]);
        return (int)$statement->fetchColumn();
    }

    public function update(int $id, array $data): void
    {
        $allowed = [
            'original_url', 'short_code', 'label', 'expires_at',
            'is_active', 'has_preview_page', 'qr_code_path'
        ];
        
        $setParts = [];
        $params = ['id' => $id];
        
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $setParts[] = "{$field} = :{$field}";
                $params[$field] = $data[$field];
            }
        }
        
        if (empty($setParts)) {
            return;
        }
        
        $sql = 'UPDATE short_links SET ' . implode(', ', $setParts) . ' WHERE id = :id';
        $statement = $this->db->prepare($sql);
        $statement->execute($params);
    }

    public function deactivate(int $id): void
    {
        $statement = $this->db->prepare('UPDATE short_links SET is_active = 0 WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    public function activate(int $id): void
    {
        $statement = $this->db->prepare('UPDATE short_links SET is_active = 1 WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    public function delete(int $id): void
    {
        $statement = $this->db->prepare('DELETE FROM short_links WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    public function getActiveLinks(int $userId): array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM short_links 
             WHERE user_id = :user_id AND is_active = 1 
             AND (expires_at IS NULL OR expires_at > NOW())
             ORDER BY created_at DESC'
        );
        $statement->execute(['user_id' => $userId]);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getExpiredLinks(int $userId): array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM short_links 
             WHERE user_id = :user_id 
             AND expires_at IS NOT NULL 
             AND expires_at <= NOW()
             ORDER BY expires_at DESC'
        );
        $statement->execute(['user_id' => $userId]);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function searchByUser(int $userId, string $query, int $limit = 50, int $offset = 0): array
    {
        $searchTerm = '%' . $query . '%';
        $statement = $this->db->prepare(
            'SELECT * FROM short_links 
             WHERE user_id = :user_id 
             AND (label LIKE :query OR original_url LIKE :query OR short_code LIKE :query)
             ORDER BY created_at DESC 
             LIMIT :limit OFFSET :offset'
        );
        $statement->bindValue('user_id', $userId, PDO::PARAM_INT);
        $statement->bindValue('query', $searchTerm, PDO::PARAM_STR);
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->bindValue('offset', $offset, PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function isShortCodeUnique(string $shortCode, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM short_links WHERE short_code = :short_code';
        $params = ['short_code' => $shortCode];
        
        if ($excludeId !== null) {
            $sql .= ' AND id != :exclude_id';
            $params['exclude_id'] = $excludeId;
        }
        
        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        return (int)$statement->fetchColumn() === 0;
    }
}

