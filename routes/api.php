<?php

use App\Http\Controllers\NewsletterController;

require __DIR__ . '/auth.php';


Route::get('/test-mail', [NewsletterController::class, 'sendTestEmail']);
