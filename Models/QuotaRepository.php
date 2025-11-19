<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

class QuotaRepository
{
    public function __construct(private PDO $db)
    {
    }

    public function checkDailyQuota(int $userId, string $date): int
    {
        $statement = $this->db->prepare(
            'SELECT links_created FROM quota_daily 
             WHERE user_id = :user_id AND date = :date'
        );
        $statement->execute([
            'user_id' => $userId,
            'date' => $date,
        ]);
        $result = $statement->fetch(PDO::FETCH_ASSOC);
        return $result ? (int)$result['links_created'] : 0;
    }

    public function checkMonthlyQuota(int $userId, int $yearMonth): int
    {
        $statement = $this->db->prepare(
            'SELECT links_created FROM quota_monthly 
             WHERE user_id = :user_id AND `year_month` = :year_month'
        );
        $statement->execute([
            'user_id' => $userId,
            'year_month' => $yearMonth,
        ]);
        $result = $statement->fetch(PDO::FETCH_ASSOC);
        return $result ? (int)$result['links_created'] : 0;
    }

    public function incrementDailyQuota(int $userId, string $date): void
    {
        $statement = $this->db->prepare(
            'INSERT INTO quota_daily (user_id, date, links_created)
             VALUES (:user_id, :date, 1)
             ON DUPLICATE KEY UPDATE links_created = links_created + 1'
        );
        $statement->execute([
            'user_id' => $userId,
            'date' => $date,
        ]);
    }

    public function incrementMonthlyQuota(int $userId, int $yearMonth): void
    {
        $statement = $this->db->prepare(
            'INSERT INTO quota_monthly (user_id, `year_month`, links_created)
             VALUES (:user_id, :year_month, 1)
             ON DUPLICATE KEY UPDATE links_created = links_created + 1'
        );
        $statement->execute([
            'user_id' => $userId,
            'year_month' => $yearMonth,
        ]);
    }

    public function getQuotaStatus(int $userId, string $plan): array
    {
        $today = date('Y-m-d');
        $currentMonth = (int)date('Ym');
        
        $dailyUsed = $this->checkDailyQuota($userId, $today);
        $monthlyUsed = $this->checkMonthlyQuota($userId, $currentMonth);
        
        $limits = match($plan) {
            'FREE' => ['daily' => 10, 'monthly' => 200],
            'PREMIUM' => ['daily' => null, 'monthly' => 600],
            'ENTERPRISE' => ['daily' => null, 'monthly' => null],
            default => ['daily' => 10, 'monthly' => 200],
        };
        
        return [
            'daily_used' => $dailyUsed,
            'daily_limit' => $limits['daily'],
            'monthly_used' => $monthlyUsed,
            'monthly_limit' => $limits['monthly'],
            'can_create_daily' => $limits['daily'] === null || $dailyUsed < $limits['daily'],
            'can_create_monthly' => $limits['monthly'] === null || $monthlyUsed < $limits['monthly'],
        ];
    }
}

