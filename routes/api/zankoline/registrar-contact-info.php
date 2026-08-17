<?php

use App\Http\Controllers\StudentContactInfoController;

Route::patch('/zankoline/students/{highSchoolStudent}/contact-info', [StudentContactInfoController::class, 'updateContactInfo']);
