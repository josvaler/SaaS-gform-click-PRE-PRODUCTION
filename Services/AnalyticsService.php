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

    public function getLinkAnalyticsWithComparison(int $shortLinkId, int $days = 30): array
    {
        // Get current period data
        $currentAnalytics = $this->getLinkAnalytics($shortLinkId, $days);
        
        // Calculate date range for previous period
        $today = new \DateTime();
        $currentStartDate = clone $today;
        $currentStartDate->modify("-{$days} days");
        
        $previousEndDate = clone $currentStartDate;
        $previousEndDate->modify('-1 day');
        $previousStartDate = clone $previousEndDate;
        $previousStartDate->modify("-{$days} days");
        
        // Get previous period daily clicks
        $previousDailyClicks = $this->clickRepo->getClicksByDateRange(
            $shortLinkId,
            $previousStartDate->format('Y-m-d'),
            $previousEndDate->format('Y-m-d')
        );
        
        $previousFormatted = $this->formatDailyClicksForRange(
            $previousDailyClicks,
            $previousStartDate,
            $previousEndDate
        );
        
        // Calculate totals for comparison
        $currentTotal = array_sum(array_column($currentAnalytics['daily_clicks'], 'clicks'));
        $previousTotal = array_sum(array_column($previousFormatted, 'clicks'));
        
        // Calculate trends
        $trends = $this->calculateTrends(
            $currentAnalytics['daily_clicks'],
            $previousFormatted,
            $currentTotal,
            $previousTotal
        );
        
        return [
            'current' => $currentAnalytics,
            'previous' => [
                'daily_clicks' => $previousFormatted,
                'total_clicks' => $previousTotal,
            ],
            'trends' => $trends,
        ];
    }

    private function formatDailyClicksForRange(array $dailyClicks, \DateTime $startDate, \DateTime $endDate): array
    {
        $formatted = [];
        $currentDate = clone $startDate;
        
        // Create map of existing clicks
        $clicksMap = [];
        foreach ($dailyClicks as $click) {
            $clicksMap[$click['date']] = (int)$click['clicks'];
        }
        
        // Fill all days in range
        while ($currentDate <= $endDate) {
            $dateStr = $currentDate->format('Y-m-d');
            $formatted[] = [
                'date' => $dateStr,
                'clicks' => $clicksMap[$dateStr] ?? 0
            ];
            $currentDate->modify('+1 day');
        }
        
        return $formatted;
    }

    public function calculateTrends(array $currentDaily, array $previousDaily, int $currentTotal, int $previousTotal): array
    {
        // Calculate percentage change for total clicks
        $totalChange = 0;
        $totalChangePercent = 0;
        if ($previousTotal > 0) {
            $totalChange = $currentTotal - $previousTotal;
            $totalChangePercent = round(($totalChange / $previousTotal) * 100, 1);
        } elseif ($currentTotal > 0) {
            $totalChange = $currentTotal;
            $totalChangePercent = 100;
        }
        
        // Calculate average daily clicks
        $currentAvg = count($currentDaily) > 0 ? round($currentTotal / count($currentDaily), 1) : 0;
        $previousAvg = count($previousDaily) > 0 ? round($previousTotal / count($previousDaily), 1) : 0;
        
        $avgChange = $currentAvg - $previousAvg;
        $avgChangePercent = 0;
        if ($previousAvg > 0) {
            $avgChangePercent = round(($avgChange / $previousAvg) * 100, 1);
        } elseif ($currentAvg > 0) {
            $avgChangePercent = 100;
        }
        
        // Find peak days
        $currentPeak = 0;
        $currentPeakDate = null;
        foreach ($currentDaily as $day) {
            if ($day['clicks'] > $currentPeak) {
                $currentPeak = $day['clicks'];
                $currentPeakDate = $day['date'];
            }
        }
        
        $previousPeak = 0;
        $previousPeakDate = null;
        foreach ($previousDaily as $day) {
            if ($day['clicks'] > $previousPeak) {
                $previousPeak = $day['clicks'];
                $previousPeakDate = $day['date'];
            }
        }
        
        $peakChange = $currentPeak - $previousPeak;
        $peakChangePercent = 0;
        if ($previousPeak > 0) {
            $peakChangePercent = round(($peakChange / $previousPeak) * 100, 1);
        } elseif ($currentPeak > 0) {
            $peakChangePercent = 100;
        }
        
        return [
            'total_clicks' => [
                'current' => $currentTotal,
                'previous' => $previousTotal,
                'change' => $totalChange,
                'change_percent' => $totalChangePercent,
                'is_positive' => $totalChange >= 0,
            ],
            'average_daily' => [
                'current' => $currentAvg,
                'previous' => $previousAvg,
                'change' => $avgChange,
                'change_percent' => $avgChangePercent,
                'is_positive' => $avgChange >= 0,
            ],
            'peak_day' => [
                'current' => $currentPeak,
                'current_date' => $currentPeakDate,
                'previous' => $previousPeak,
                'previous_date' => $previousPeakDate,
                'change' => $peakChange,
                'change_percent' => $peakChangePercent,
                'is_positive' => $peakChange >= 0,
            ],
        ];
    }
}

