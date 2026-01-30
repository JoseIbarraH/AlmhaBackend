<?php

namespace App\Domains\Dashboard\Controllers;

use Google\Analytics\Data\V1beta\Client\BetaAnalyticsDataClient;
use Google\Analytics\Data\V1beta\RunReportRequest;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\Dimension;
use Google\Analytics\Data\V1beta\Metric;
use Google\Analytics\Data\V1beta\BatchRunReportsRequest;
use Google\Analytics\Data\V1beta\OrderBy;
use Google\Analytics\Data\V1beta\OrderBy\DimensionOrderBy;
use Google\Analytics\Data\V1beta\OrderBy\MetricOrderBy;
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

            $overviewReq = (new RunReportRequest())->setDateRanges([$dateRange])
                ->setMetrics([
                    new Metric(['name' => 'activeUsers']),
                    new Metric(['name' => 'sessions']),
                    new Metric(['name' => 'bounceRate']),
                    new Metric(['name' => 'averageSessionDuration']),
                    new Metric(['name' => 'screenPageViews']),
                ]);

            $weeklyReq = (new RunReportRequest())->setDateRanges([$dateRange])
                ->setDimensions([new Dimension(['name' => 'dayOfWeek'])])
                ->setMetrics([new Metric(['name' => 'activeUsers']), new Metric(['name' => 'sessions'])]);

            $activePagesReq = (new RunReportRequest())->setDateRanges([$dateRange])
                ->setDimensions([new Dimension(['name' => 'pagePath'])])
                ->setMetrics([new Metric(['name' => 'activeUsers'])])->setLimit(25);

            $sourcesReq = (new RunReportRequest())->setDateRanges([$dateRange])
                ->setDimensions([new Dimension(['name' => 'sessionDefaultChannelGroup'])])
                ->setMetrics([new Metric(['name' => 'sessions'])]);

            $socialReq = (new RunReportRequest())->setDateRanges([$dateRange])
                ->setDimensions([new Dimension(['name' => 'sessionSource'])])
                ->setMetrics([new Metric(['name' => 'sessions'])])->setLimit(15);

            $geoReq = (new RunReportRequest())->setDateRanges([$dateRange])
                ->setDimensions([new Dimension(['name' => 'country'])])
                ->setMetrics([new Metric(['name' => 'activeUsers'])]);

            $topPagesReq = (new RunReportRequest())->setDateRanges([$dateRange])
                ->setDimensions([new Dimension(['name' => 'pageTitle']), new Dimension(['name' => 'pagePath'])])
                ->setMetrics([new Metric(['name' => 'screenPageViews'])])->setLimit(15);

            $devicesReq = (new RunReportRequest())->setDateRanges([$dateRange])
                ->setDimensions([new Dimension(['name' => 'deviceCategory'])])
                ->setMetrics([new Metric(['name' => 'activeUsers'])]);

            // --- EJECUCIÓN EN BATCH ---

            $batch1 = (new BatchRunReportsRequest())->setProperty($propertyId)->setRequests([$overviewReq, $weeklyReq, $activePagesReq, $sourcesReq, $socialReq]);
            $batch2 = (new BatchRunReportsRequest())->setProperty($propertyId)->setRequests([$geoReq, $topPagesReq, $devicesReq]);

            $res1 = $client->batchRunReports($batch1);
            $res2 = $client->batchRunReports($batch2);

            $reports = array_merge(iterator_to_array($res1->getReports()), iterator_to_array($res2->getReports()));

            // --- PROCESAMIENTO Y LIMPIEZA ---

            // 1. Overview
            $overview = ['active_users' => 0, 'sessions' => 0, 'bounce_rate' => 0, 'avg_duration' => 0, 'page_views' => 0];
            if ($reports[0]->getRows()->count() > 0) {
                $r = $reports[0]->getRows()[0];
                $overview = [
                    'active_users' => (int) $r->getMetricValues()[0]->getValue(),
                    'sessions' => (int) $r->getMetricValues()[1]->getValue(),
                    'bounce_rate' => round((float) $r->getMetricValues()[2]->getValue() * 100, 2),
                    'avg_duration' => round((float) $r->getMetricValues()[3]->getValue(), 2),
                    'page_views' => (int) $r->getMetricValues()[4]->getValue(),
                ];
            }

            // 2. Weekly Traffic con nombres de días
            $dayNames = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
            $weekly = [];
            foreach ($reports[1]->getRows() as $row) {
                $dayIdx = (int) $row->getDimensionValues()[0]->getValue();
                $weekly[] = [
                    'day' => $dayNames[$dayIdx],
                    'active_users' => (int) $row->getMetricValues()[0]->getValue(),
                    'sessions' => (int) $row->getMetricValues()[1]->getValue(),
                ];
            }

            // 3. Active by URL con Normalización (quita barra final)
            $activeByUrlRaw = [];
            foreach ($reports[2]->getRows() as $row) {
                $path = rtrim($row->getDimensionValues()[0]->getValue(), '/') ?: '/';
                $val = (int) $row->getMetricValues()[0]->getValue();
                $activeByUrlRaw[$path] = ($activeByUrlRaw[$path] ?? 0) + $val;
            }
            $activeByUrl = [];
            foreach ($activeByUrlRaw as $path => $users) {
                $activeByUrl[] = ['path' => $path, 'active_users' => $users];
            }

            // 4. Sources (Limpieza de Unassigned)
            $trafficSources = [];
            foreach ($reports[3]->getRows() as $row) {
                $channel = $row->getDimensionValues()[0]->getValue();
                $trafficSources[] = [
                    'channel' => ($channel === '(unassigned)') ? 'Otros' : $channel,
                    'sessions' => (int) $row->getMetricValues()[0]->getValue(),
                ];
            }

            // 5. Geo con Porcentajes
            $geo = [];
            $baseUsers = $overview['active_users'] ?: 1;
            foreach ($reports[5]->getRows() as $row) {
                $country = $row->getDimensionValues()[0]->getValue();
                $users = (int) $row->getMetricValues()[0]->getValue();
                $geo[] = [
                    'country' => ($country === '(not set)') ? 'Desconocido' : $country,
                    'users' => $users,
                    'percentage' => round(($users / $baseUsers) * 100, 2)
                ];
            }

            // 6. Top Pages (Limpieza de títulos y paths)
            $topPages = [];
            foreach ($reports[6]->getRows() as $row) {
                $topPages[] = [
                    'title' => str_replace(' | Almha Plastic Surgery', '', $row->getDimensionValues()[0]->getValue()),
                    'path' => $row->getDimensionValues()[1]->getValue(),
                    'views' => (int) $row->getMetricValues()[0]->getValue(),
                ];
            }

            // 7. Devices
            $devices = [];
            foreach ($reports[7]->getRows() as $row) {
                $devices[] = [
                    'device' => $row->getDimensionValues()[0]->getValue(),
                    'users' => (int) $row->getMetricValues()[0]->getValue(),
                ];
            }

            return response()->json([
                'overview' => $overview,
                'weekly_traffic' => $weekly,
                'active_users_by_url' => $activeByUrl,
                'traffic_sources' => $trafficSources,
                'geo_distribution' => $geo,
                'top_pages' => $topPages,
                'devices' => $devices,
            ]);

        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
