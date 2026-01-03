<?php

namespace App\Domains\Setting\Audit\Controllers;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use OwenIt\Auditing\Models\Audit;

class AuditController extends Controller
{
    public function list_audit(Request $request)
    {
        try {
            $perPage = 10;

            $audit = Audit::select(
                'audits.user_type',
                'audits.user_id',
                'users.name as user_name',
                'audits.event',
                'audits.auditable_type',
                'audits.auditable_id',
                'audits.old_values',
                'audits.new_values',
                'audits.url',
                'audits.ip_address',
                'audits.tags',
                'audits.user_agent',
                'audits.created_at',
                'audits.updated_at'
            )
                ->leftJoin('users', 'audits.user_id', '=', 'users.id')
                ->orderBy('audits.created_at', 'desc');

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
                        ->orWhere('users.name', 'like', "%{$search}%")
                        ->orWhereJsonContains('old_values', $search)
                        ->orWhereJsonContains('new_values', $search);
                });
            }

            /*
            |--------------------------------------------------------------------------
            | 📅 FILTRO DE FECHAS
            |--------------------------------------------------------------------------
            */

            if ($request->filled('date')) {
                $audit->whereDate('audits.created_at', $request->date);
            }

            if ($request->filled('start_date') && $request->filled('end_date')) {
                $audit->whereBetween('audits.created_at', [
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
                $audit->where('event', $request->event);
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
                    'user_name' => $item->user_name, // 👈 Aquí se incluye el nombre
                    'event' => $item->event,
                    'auditable_type' => $item->auditable_type,
                    'auditable_id' => $item->auditable_id,
                    'old_values' => $item->old_values,
                    'new_values' => $item->new_values,
                    'url' => $item->url,
                    'ip_address' => $item->ip_address,
                    'user_agent' => $item->user_agent,
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
