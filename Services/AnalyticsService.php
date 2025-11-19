<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\ClickRepository;

class AnalyticsService
{
    public function __construct(
        private ClickRepository $clickRepo
    ) {
    }

    public function getLinkAnalytics(int $shortLinkId, int $days = 30): array
    {
        $stats = $this->clickRepo->getClickStats($shortLinkId);
        $totalClicks = (int)($stats['total_clicks'] ?? 0);
        
        $dailyClicks = $this->clickRepo->getDailyClicks($shortLinkId, $days);
        $deviceStats = $this->clickRepo->getDeviceStats($shortLinkId);
        $countryStats = $this->clickRepo->getCountryStats($shortLinkId);
        $hourlyStats = $this->clickRepo->getHourlyStats($shortLinkId, $days);
        
        return [
            'total_clicks' => $totalClicks,
            'daily_clicks' => $this->formatDailyClicks($dailyClicks, $days),
            'device_stats' => $deviceStats,
            'country_stats' => $countryStats,
            'hourly_stats' => $this->formatHourlyStats($hourlyStats),
        ];
    }

    private function formatDailyClicks(array $dailyClicks, int $days): array
    {
        // Create array with all days filled (0 for days with no clicks)
        $formatted = [];
        $startDate = new \DateTime();
        $startDate->modify("-{$days} days");
        
        $currentDate = clone $startDate;
        $today = new \DateTime();
        
        // Create map of existing clicks
        $clicksMap = [];
        foreach ($dailyClicks as $click) {
            $clicksMap[$click['date']] = (int)$click['clicks'];
        }
        
        // Fill all days
        while ($currentDate <= $today) {
            $dateStr = $currentDate->format('Y-m-d');
            $formatted[] = [
                'date' => $dateStr,
                'clicks' => $clicksMap[$dateStr] ?? 0
            ];
            $currentDate->modify('+1 day');
        }
        
        return $formatted;
    }

    private function formatHourlyStats(array $hourlyStats): array
    {
        // Fill all 24 hours (0 for hours with no clicks)
        $formatted = [];
        $clicksMap = [];
        
        foreach ($hourlyStats as $stat) {
            $clicksMap[(int)$stat['hour']] = (int)$stat['count'];
        }
        
        for ($hour = 0; $hour < 24; $hour++) {
            $formatted[] = [
                'hour' => $hour,
                'clicks' => $clicksMap[$hour] ?? 0
            ];
        }
        
        return $formatted;
    }

    public function getDeviceBreakdown(array $deviceStats): array
    {
        $total = 0;
        foreach ($deviceStats as $stat) {
            $total += (int)$stat['count'];
        }
        
        $breakdown = [];
        foreach ($deviceStats as $stat) {
            $breakdown[] = [
                'device' => $stat['device_type'],
                'count' => (int)$stat['count'],
                'percentage' => $total > 0 ? round(((int)$stat['count'] / $total) * 100, 2) : 0
            ];
        }
        
        return $breakdown;
    }

    public function getCountryBreakdown(array $countryStats): array
    {
        $total = 0;
        foreach ($countryStats as $stat) {
            $total += (int)$stat['count'];
        }
        
        $breakdown = [];
        foreach ($countryStats as $stat) {
            $breakdown[] = [
                'country' => $stat['country'],
                'count' => (int)$stat['count'],
                'percentage' => $total > 0 ? round(((int)$stat['count'] / $total) * 100, 2) : 0
            ];
        }
        
        return $breakdown;
    }
}

