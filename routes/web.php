<?php

use Modules\XmlFormGenerator\Http\Controllers\XmlFormGeneratorController;

Route::get('/xml-form-generator', [XmlFormGeneratorController::class, 'index']);
Route::post('/xml-form-generator/generate', [XmlFormGeneratorController::class, 'generate']);