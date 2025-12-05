<?php

use App\Http\Controllers\QuestionGeneratorController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::get('/courses', [QuestionGeneratorController::class, 'listCourses']);

Route::get('/courses/{course}/topics', [QuestionGeneratorController::class, 'listTopics']);

Route::post('/cuestionarios/generar', [QuestionGeneratorController::class, 'generate']);
