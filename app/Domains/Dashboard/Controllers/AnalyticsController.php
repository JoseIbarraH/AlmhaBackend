<?php

namespace App\Domains\Dashboard\Controllers;

use Google\Analytics\Data\V1beta\Client\BetaAnalyticsDataClient;
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

            $client = new BetaAnalyticsDataClient([
                'credentials' => $config['credentials_file']
            ]);

            // He añadido Dimensiones y más Métricas para que el JSON sea rico en información
            $request = (new RunReportRequest())
                ->setProperty('properties/' . $config['property_id'])
                ->setDateRanges([
                    new DateRange(['start_date' => '30daysAgo', 'end_date' => 'today']),
                ])
                ->setDimensions([
                    new Dimension(['name' => 'date']),           // Para ver datos por día
                    new Dimension(['name' => 'country']),        // Para ver de dónde vienen
                    new Dimension(['name' => 'browser']),        // Para ver el navegador
                ])
                ->setMetrics([
                    new Metric(['name' => 'activeUsers']),
                    new Metric(['name' => 'screenPageViews']),
                    new Metric(['name' => 'sessions']),
                    new Metric(['name' => 'bounceRate']),        // Porcentaje de rebote
                    new Metric(['name' => 'averageSessionDuration']), // Tiempo en el sitio
                ]);

            $response = $client->runReport($request);

            // Retornamos el JSON puro de Google para inspeccionar la estructura
            return response($response->serializeToJsonString())
                ->header('Content-Type', 'application/json');

        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
