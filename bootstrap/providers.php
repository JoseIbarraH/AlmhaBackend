<?php

return [
    App\Providers\AppServiceProvider::class,
    OwenIt\Auditing\AuditingServiceProvider::class,
    App\Domains\Procedure\Providers\ProcedureModuleServiceProvider::class,
    App\Domains\Blog\Providers\BlogModuleServiceProvider::class,
    App\Domains\Design\Providers\DesignModuleServiceProvider::class,
    App\Domains\TeamMember\Providers\TeamMemberModuleServiceProvider::class,
];
