<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\QuotaRepository;

class QuotaService
{
    public function __construct(
        private QuotaRepository $quotaRepo
    ) {
    }

    public function canCreateLink(int $userId, string $plan): array
    {
        $quotaStatus = $this->quotaRepo->getQuotaStatus($userId, $plan);
        
        $canCreate = $quotaStatus['can_create_daily'] && $quotaStatus['can_create_monthly'];
        
        $message = null;
        if (!$canCreate) {
            if (!$quotaStatus['can_create_daily']) {
                $message = "Has alcanzado tu límite diario de {$quotaStatus['daily_limit']} enlaces. ";
            }
            if (!$quotaStatus['can_create_monthly']) {
                $message .= "Has alcanzado tu límite mensual de {$quotaStatus['monthly_limit']} enlaces. ";
            }
            $message .= "Considera actualizar a PREMIUM para obtener más límites.";
        }
        
        return [
            'can_create' => $canCreate,
            'quota_status' => $quotaStatus,
            'message' => $message,
            'suggestion' => $plan === 'FREE' ? 'upgrade_to_premium' : null
        ];
    }

    public function recordLinkCreation(int $userId): void
    {
        $today = date('Y-m-d');
        $currentMonth = (int)date('Ym');
        
        $this->quotaRepo->incrementDailyQuota($userId, $today);
        $this->quotaRepo->incrementMonthlyQuota($userId, $currentMonth);
    }

    public function getQuotaStatus(int $userId, string $plan): array
    {
        return $this->quotaRepo->getQuotaStatus($userId, $plan);
    }
}

