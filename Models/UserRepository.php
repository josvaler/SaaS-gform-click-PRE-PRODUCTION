<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

class UserRepository
{
    public function __construct(private PDO $db)
    {
    }

    public function findByGoogleId(string $googleId): ?array
    {
        $statement = $this->db->prepare('SELECT * FROM users WHERE TRIM(google_id) = TRIM(:google_id) LIMIT 1');
        $statement->execute(['google_id' => $googleId]);
        $user = $statement->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $statement = $this->db->prepare('SELECT * FROM users WHERE LOWER(email) = LOWER(:email) LIMIT 1');
        $statement->execute(['email' => $email]);
        $user = $statement->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    public function upsertFromGoogle(array $profile): array
    {
        $statement = $this->db->prepare(
            'INSERT INTO users (google_id, email, name, avatar_url, locale, plan, lifetime_ops)
             VALUES (:google_id, :email, :name, :avatar_url, :locale, COALESCE(:plan, "FREE"), COALESCE(:lifetime_ops, 0))
             ON DUPLICATE KEY UPDATE 
                email = VALUES(email), 
                name = VALUES(name),
                avatar_url = VALUES(avatar_url),
                locale = VALUES(locale),
                updated_at = CURRENT_TIMESTAMP'
        );

        $statement->execute([
            'google_id' => trim((string)$profile['google_id']),
            'email' => isset($profile['email']) ? strtolower(trim((string)$profile['email'])) : null,
            'name' => $profile['name'] ?? null,
            'avatar_url' => $profile['avatar_url'] ?? null,
            'locale' => $profile['locale'] ?? null,
            'plan' => $profile['plan'] ?? 'FREE',
            'lifetime_ops' => $profile['lifetime_ops'] ?? 0,
        ]);

        return $this->findByGoogleId($profile['google_id']);
    }

    public function updatePlan(int $userId, string $plan, ?string $planExpiration = null): void
    {
        $sql = 'UPDATE users SET plan = :plan';
        $params = ['plan' => $plan, 'id' => $userId];
        
        if ($planExpiration !== null) {
            $sql .= ', plan_expiration = :plan_expiration';
            $params['plan_expiration'] = $planExpiration;
        }
        
        $sql .= ' WHERE id = :id';
        $statement = $this->db->prepare($sql);
        $statement->execute($params);
    }
    
    public function updateProfile(int $userId, array $profileData): void
    {
        $allowed = [
            'country', 'city', 'address', 'postal_code', 'phone',
            'company', 'website', 'bio', 'locale'
        ];
        
        $setParts = [];
        $params = ['id' => $userId];
        
        foreach ($allowed as $field) {
            if (array_key_exists($field, $profileData)) {
                $setParts[] = "{$field} = :{$field}";
                $params[$field] = $profileData[$field];
            }
        }
        
        if (empty($setParts)) {
            return;
        }
        
        $sql = 'UPDATE users SET ' . implode(', ', $setParts) . ' WHERE id = :id';
        $statement = $this->db->prepare($sql);
        $statement->execute($params);
    }
    
    public function findByRole(string $role): array
    {
        $statement = $this->db->prepare('SELECT * FROM users WHERE role = :role ORDER BY created_at DESC');
        $statement->execute(['role' => $role]);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getPlanLimits(string $plan): array
    {
        return match($plan) {
            'FREE' => ['daily' => 10, 'monthly' => 200],
            'PREMIUM' => ['daily' => null, 'monthly' => 600],
            'ENTERPRISE' => ['daily' => null, 'monthly' => null],
            default => ['daily' => 10, 'monthly' => 200],
        };
    }

    public function incrementLifetimeOps(int $userId): void
    {
        $statement = $this->db->prepare('UPDATE users SET lifetime_ops = lifetime_ops + 1 WHERE id = :id');
        $statement->execute(['id' => $userId]);
    }

    public function updateStripeCustomerId(int $userId, string $customerId): void
    {
        $statement = $this->db->prepare('UPDATE users SET stripe_customer_id = :customer_id WHERE id = :id');
        $statement->execute([
            'customer_id' => $customerId,
            'id' => $userId,
        ]);
    }

    public function updateSubscriptionMetadata(int $userId, array $attributes): void
    {
        $allowed = [
            'stripe_subscription_id',
            'cancel_at_period_end',
            'cancel_at',
            'current_period_end',
        ];

        $setParts = [];
        $params = ['id' => $userId];

        foreach ($allowed as $column) {
            if (array_key_exists($column, $attributes)) {
                $setParts[] = "{$column} = :{$column}";
                $params[$column] = $attributes[$column];
            }
        }

        if (empty($setParts)) {
            return;
        }

        $sql = 'UPDATE users SET ' . implode(', ', $setParts) . ' WHERE id = :id';
        $statement = $this->db->prepare($sql);
        $statement->execute($params);
    }
}

