<?php

namespace App\Domains\Dashboard\Controllers;

use Google\Analytics\Data\V1beta\Client\BetaAnalyticsDataClient;
use Google\Analytics\Data\V1beta\BatchRunReportsRequest;
use Google\Analytics\Data\V1beta\RunReportResponse;
use Google\Analytics\Data\V1beta\RunReportRequest;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\Dimension;
use Google\Analytics\Data\V1beta\Metric;
use App\Http\Controllers\Controller;
use Exception;

class AnalyticsController extends Controller
{
    public function index()
    {
        try {
            $config = config('services.google_analytics');
            $client = new BetaAnalyticsDataClient(['credentials' => $config['credentials_file']]);
            $propertyId = 'properties/' . $config['property_id'];

            // --- DEFINICIÓN DE REPORTES ---

            $dateRange = new DateRange(['start_date' => '30daysAgo', 'end_date' => 'today']);

            $overviewReq = $this->getOverviewRequest($dateRange);
            $weeklyReq = $this->getWeeklyRequest($dateRange);
            $activePagesReq = $this->getActivePagesRequest($dateRange);
            $sourcesReq = $this->getSourcesRequest($dateRange);
            $socialReq = $this->getSocialRequest($dateRange);
            $geoReq = $this->getGeoRequest($dateRange);
            $topPagesReq = $this->getTopPagesRequest($dateRange);
            $devicesReq = $this->getDevicesRequest($dateRange);

            // --- EJECUCIÓN EN BATCH ---

            $batch1 = (new BatchRunReportsRequest())
                ->setProperty($propertyId)
                ->setRequests([$overviewReq, $weeklyReq, $activePagesReq, $sourcesReq, $socialReq]);

            $batch2 = (new BatchRunReportsRequest())
                ->setProperty($propertyId)
                ->setRequests([$geoReq, $topPagesReq, $devicesReq]);

            $res1 = $client->batchRunReports($batch1);
            $res2 = $client->batchRunReports($batch2);

            $reports1 = iterator_to_array($res1->getReports());
            $reports2 = iterator_to_array($res2->getReports());

            // --- PROCESAMIENTO Y LIMPIEZA ---

            $overview = $this->processOverview($reports1[0]);

            return response()->json([
                'overview' => $overview,
                'weekly_traffic' => $this->processWeeklyTraffic($reports1[1]),
                'active_users_by_url' => $this->processActivePages($reports1[2]),
                'traffic_sources' => $this->processTrafficSources($reports1[3]),
                'social_sources' => $this->processSocialSources($reports1[4]),
                'geo_distribution' => $this->processGeoDistribution($reports2[0], $overview['active_users']),
                'top_pages' => $this->processTopPages($reports2[1]),
                'devices' => $this->processDevices($reports2[2]),
            ]);

        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    private function getOverviewRequest(DateRange $dateRange): RunReportRequest
    {
        return (new RunReportRequest())
            ->setDateRanges([$dateRange])
            ->setMetrics([
                new Metric(['name' => 'activeUsers']),
                new Metric(['name' => 'sessions']),
                new Metric(['name' => 'bounceRate']),
                new Metric(['name' => 'averageSessionDuration']),
                new Metric(['name' => 'screenPageViews']),
            ]);
    }

    private function getWeeklyRequest(DateRange $dateRange): RunReportRequest
    {
        return (new RunReportRequest())
            ->setDateRanges([$dateRange])
            ->setDimensions([new Dimension(['name' => 'dayOfWeek'])])
            ->setMetrics([
                new Metric(['name' => 'activeUsers']),
                new Metric(['name' => 'sessions'])
            ]);
    }

    private function getActivePagesRequest(DateRange $dateRange): RunReportRequest
    {
        return (new RunReportRequest())
            ->setDateRanges([$dateRange])
            ->setDimensions([new Dimension(['name' => 'pagePath'])])
            ->setMetrics([new Metric(['name' => 'activeUsers'])])
            ->setLimit(25);
    }

    private function getSourcesRequest(DateRange $dateRange): RunReportRequest
    {
        return (new RunReportRequest())
            ->setDateRanges([$dateRange])
            ->setDimensions([new Dimension(['name' => 'sessionDefaultChannelGroup'])])
            ->setMetrics([new Metric(['name' => 'sessions'])]);
    }

    private function getSocialRequest(DateRange $dateRange): RunReportRequest
    {
        return (new RunReportRequest())
            ->setDateRanges([$dateRange])
            ->setDimensions([new Dimension(['name' => 'sessionSource'])])
            ->setMetrics([new Metric(['name' => 'sessions'])])
            ->setLimit(15);
    }

    private function getGeoRequest(DateRange $dateRange): RunReportRequest
    {
        return (new RunReportRequest())
            ->setDateRanges([$dateRange])
            ->setDimensions([new Dimension(['name' => 'country'])])
            ->setMetrics([new Metric(['name' => 'activeUsers'])]);
    }

    private function getTopPagesRequest(DateRange $dateRange): RunReportRequest
    {
        return (new RunReportRequest())
            ->setDateRanges([$dateRange])
            ->setDimensions([
                new Dimension(['name' => 'pageTitle']),
                new Dimension(['name' => 'pagePath'])
            ])
            ->setMetrics([new Metric(['name' => 'screenPageViews'])])
            ->setLimit(15);
    }

    private function getDevicesRequest(DateRange $dateRange): RunReportRequest
    {
        return (new RunReportRequest())
            ->setDateRanges([$dateRange])
            ->setDimensions([new Dimension(['name' => 'deviceCategory'])])
            ->setMetrics([new Metric(['name' => 'activeUsers'])]);
    }

    private function processOverview(RunReportResponse $report): array
    {
        $overview = ['active_users' => 0, 'sessions' => 0, 'bounce_rate' => 0, 'avg_duration' => 0, 'page_views' => 0];

        if ($report->getRows()->count() > 0) {
            $r = $report->getRows()[0];
            $overview = [
                'active_users' => (int) $r->getMetricValues()[0]->getValue(),
                'sessions' => (int) $r->getMetricValues()[1]->getValue(),
                'bounce_rate' => round((float) $r->getMetricValues()[2]->getValue() * 100, 2),
                'avg_duration' => round((float) $r->getMetricValues()[3]->getValue(), 2),
                'page_views' => (int) $r->getMetricValues()[4]->getValue(),
            ];
        }

        return $overview;
    }

    private function processWeeklyTraffic(RunReportResponse $report): array
    {
        $dayNames = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
        $weekly = [];

        foreach ($report->getRows() as $row) {
            $dayIdx = (int) $row->getDimensionValues()[0]->getValue();
            // Ensure dayIdx is within bounds, though Google usually returns 0-6
            $dayName = $dayNames[$dayIdx] ?? 'Desconocido';

            $weekly[] = [
                'day' => $dayName,
                'active_users' => (int) $row->getMetricValues()[0]->getValue(),
                'sessions' => (int) $row->getMetricValues()[1]->getValue(),
            ];
        }

        return $weekly;
    }

    private function processActivePages(RunReportResponse $report): array
    {
        $activeByUrlRaw = [];

        foreach ($report->getRows() as $row) {
            $path = rtrim($row->getDimensionValues()[0]->getValue(), '/') ?: '/';
            $val = (int) $row->getMetricValues()[0]->getValue();
            $activeByUrlRaw[$path] = ($activeByUrlRaw[$path] ?? 0) + $val;
        }

        $activeByUrl = [];
        foreach ($activeByUrlRaw as $path => $users) {
            $activeByUrl[] = ['path' => $path, 'active_users' => $users];
        }

        return $activeByUrl;
    }

    private function processTrafficSources(RunReportResponse $report): array
    {
        $trafficSources = [];

        foreach ($report->getRows() as $row) {
            $channel = $row->getDimensionValues()[0]->getValue();
            $trafficSources[] = [
                'channel' => ($channel === '(unassigned)') ? 'Otros' : $channel,
                'sessions' => (int) $row->getMetricValues()[0]->getValue(),
            ];
        }

        return $trafficSources;
    }

    private function processSocialSources(RunReportResponse $report): array
    {
        $socialSources = [];

        foreach ($report->getRows() as $row) {
            $source = $row->getDimensionValues()[0]->getValue();
            $socialSources[] = [
                'source' => $source,
                'sessions' => (int) $row->getMetricValues()[0]->getValue(),
            ];
        }

        return $socialSources;
    }

    private function processGeoDistribution(RunReportResponse $report, int $baseUsers): array
    {
        $geo = [];
        $baseUsers = $baseUsers ?: 1; // Prevent division by zero

        foreach ($report->getRows() as $row) {
            $country = $row->getDimensionValues()[0]->getValue();
            $users = (int) $row->getMetricValues()[0]->getValue();
            $geo[] = [
                'country' => ($country === '(not set)') ? 'Others' : $country,
                'users' => $users,
                'percentage' => round(($users / $baseUsers) * 100, 2)
            ];
        }

        return $geo;
    }

    private function processTopPages(RunReportResponse $report): array
    {
        $topPages = [];

        foreach ($report->getRows() as $row) {
            $topPages[] = [
                'title' => str_replace(' | Almha Plastic Surgery', '', $row->getDimensionValues()[0]->getValue()),
                'path' => $row->getDimensionValues()[1]->getValue(),
                'views' => (int) $row->getMetricValues()[0]->getValue(),
            ];
        }

        return $topPages;
    }

    private function processDevices(RunReportResponse $report): array
    {
        $devices = [];

        foreach ($report->getRows() as $row) {
            $devices[] = [
                'device' => $row->getDimensionValues()[0]->getValue(),
                'users' => (int) $row->getMetricValues()[0]->getValue(),
            ];
        }

        return $devices;
    }
}
