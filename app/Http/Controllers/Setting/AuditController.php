<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\Request;
use OwenIt\Auditing\Models\Audit;

class AuditController extends Controller
{
    public function list_audit(Request $request)
    {
        try {
            $perPage = 10;

            $audit = Audit::select(
                'user_type',
                'user_id',
                'event',
                'auditable_type',
                'auditable_id',
                'old_values',
                'new_values',
                'url',
                'ip_address',
                'tags',
                'created_at',
                'updated_at'
            )->orderBy('created_at', 'desc');

            /*
            |--------------------------------------------------------------------------
            | 🔍 FILTRO GLOBAL (search)
            |--------------------------------------------------------------------------
            */
            if ($request->filled('search')) {
                $search = $request->search;

                $audit->where(function ($query) use ($search) {
                    $query->where('auditable_type', 'like', "%{$search}%")
                        ->orWhere('event', 'like', "%{$search}%")
                        ->orWhere('user_type', 'like', "%{$search}%")
                        ->orWhere('url', 'like', "%{$search}%")
                        ->orWhere('ip_address', 'like', "%{$search}%")
                        ->orWhere('auditable_id', 'like', "%{$search}%")
                        ->orWhereJsonContains('old_values', $search)
                        ->orWhereJsonContains('new_values', $search);
                });
            }

            /*
            |--------------------------------------------------------------------------
            | 📅 FILTRO DE FECHAS (fecha exacta o rango)
            |--------------------------------------------------------------------------
            */

            // 1) Fecha exacta → created_at = YYYY-MM-DD
            if ($request->filled('date')) {
                $audit->whereDate('created_at', $request->date);
            }

            // 2) Rango de fechas → created_at BETWEEN start_date AND end_date
            if ($request->filled('start_date') && $request->filled('end_date')) {
                $audit->whereBetween('created_at', [
                    $request->start_date . ' 00:00:00',
                    $request->end_date . ' 23:59:59'
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | 🏷️ FILTROS INDIVIDUALES
            |--------------------------------------------------------------------------
            */

            if ($request->filled('auditable_type')) {
                $audit->where('auditable_type', $request->auditable_type);
            }

            if ($request->filled('event')) {
                $audit->where('event', $request->event); // created, updated, deleted
            }

            if ($request->filled('user_type')) {
                $audit->where('user_type', $request->user_type);
            }

            /*
            |--------------------------------------------------------------------------
            | PAGINACIÓN Y TRANSFORMACIÓN
            |--------------------------------------------------------------------------
            */

            $paginate = $audit->paginate($perPage)->appends($request->all());

            $paginate->getCollection()->transform(function ($item) {
                return [
                    'user_type' => $item->user_type,
                    'user_id' => $item->user_id,
                    'event' => $item->event,
                    'auditable_type' => $item->auditable_type,
                    'auditable_id' => $item->auditable_id,
                    'old_values' => $item->old_values,
                    'new_values' => $item->new_values,
                    'url' => $item->url,
                    'ip_address' => $item->ip_address,
                    'tags' => $item->tags,
                    'created_at' => $item->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $item->updated_at->format('Y-m-d H:i:s'),
                ];
            });

            return ApiResponse::success(
                __('messages.audit.success.listAudit'),
                [
                    'pagination' => $paginate,
                    'filters' => $request->all(),
                ]
            );

        } catch (\Throwable $th) {
            return ApiResponse::error(
                __('messages.audit.error.listAudit'),
                ['exception' => $th->getMessage()],
                500
            );
        }
    }

}
