<?php
namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

trait LogsActivity
{
    public static function bootLogsActivity()
    {
        static::created(function ($model) {
            $model->logActivity('created');
        });

        static::updated(function ($model) {
            $model->logActivity('updated', $model->getChanges());
        });

        static::deleted(function ($model) {
            $model->logActivity('deleted');
        });
    }

    protected function logActivity(string $action, array $changes = [])
    {
        try {
            $authId = Auth::id();
            $changeData = null;

            // 🧩 Caso especial: cuando se elimina o desactiva un usuario
            if ($this instanceof \App\Models\User && $action === 'deleted') {
                $authId = null;

                $changeData = json_encode([
                    'deleted_by' => Auth::user()?->name ?? 'Usuario desconocido',
                    'deleted_email' => Auth::user()?->email ?? null,
                ]);
            }

            // 🧩 Caso: cuando se crea un modelo (registrar todos sus atributos iniciales)
            elseif ($action === 'created') {
                $attributes = $this->getAttributes();

                // Evitamos campos sensibles o redundantes
                unset($attributes['password'], $attributes['remember_token']);

                $changeData = json_encode([
                    'created_data' => $attributes,
                ]);
            }

            // 🧩 Caso: cuando se actualiza
            elseif (!empty($changes)) {
                $changeData = json_encode([
                    'updated_fields' => $changes,
                ]);
            }

            // ✅ Registrar log
            ActivityLog::create([
                'user_id' => $authId,
                'model_type' => get_class($this),
                'model_id' => $this->id,
                'action' => $action,
                'changes' => $changeData,
                'ip_address' => Request::ip(),
            ]);
        } catch (\Throwable $e) {
            \Log::error("❌ Error al registrar actividad:", [
                'model' => get_class($this),
                'id' => $this->id ?? null,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }


}
