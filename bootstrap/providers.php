<?php

return [
    App\Providers\AppServiceProvider::class,
    OwenIt\Auditing\AuditingServiceProvider::class,
    App\Domains\Procedure\Providers\ProcedureModuleServiceProvider::class,
];
