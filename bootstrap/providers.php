<?php

return [
    App\Providers\AppServiceProvider::class,
    OwenIt\Auditing\AuditingServiceProvider::class,
    App\Domains\Procedure\Providers\ProcedureModuleServiceProvider::class,
    App\Domains\Blog\Providers\BlogModuleServiceProvider::class,
    App\Domains\Design\Providers\DesignModuleServiceProvider::class,
    App\Domains\TeamMember\Providers\TeamMemberModuleServiceProvider::class,
    App\Domains\Setting\Audit\Providers\AuditModuleServiceProvider::class,
    App\Domains\Setting\User\Providers\UserModuleServiceProvider::class,
    App\Domains\Setting\Trash\Providers\TrashModuleServiceProvider::class,
    App\Domains\Auth\Providers\AuthModuleServiceProvider::class,
    App\Domains\Setting\Setting\Providers\SettingModuleServiceProvider::class,
    App\Domains\Client\Providers\ClientModuleServiceProvider::class
];
