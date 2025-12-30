<?php

namespace App\Console\Commands;

use App\Domains\Blog\Models\Blog;
use App\Models\Role;
use App\Models\Service;
use App\Models\TeamMember;
use Illuminate\Console\Command;
use App\Models\User;
use Carbon\Carbon;

class ForceDeleteOldTrash extends Command
{
    protected $signature = 'trash:clean {days=30}'; // días por defecto
    protected $description = 'Eliminar permanentemente registros borrados hace X días';

    public function handle()
    {
        $days = $this->argument('days');
        $cutoffDate = Carbon::now()->subDays($days);

        $models = [User::class, Blog::class, Role::class, Service::class, TeamMember::class]; // agrega todos los modelos que usen softdelete

        foreach ($models as $model) {
            $deletedItems = $model::onlyTrashed()
                ->where('deleted_at', '<=', $cutoffDate)
                ->get();

            foreach ($deletedItems as $item) {
                $item->forceDelete();
            }

            $this->info("{$model}: {$deletedItems->count()} registros eliminados definitivamente.");
        }

        return 0;
    }
}
