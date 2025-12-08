<?php

use App\Http\Controllers\AnswerEvaluatorController;
use App\Http\Controllers\CourseTopicGeneratorController;
use App\Http\Controllers\ImageGeneratorController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\QuestionGeneratorController;
use App\Http\Controllers\SummaryGeneratorController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::get('/courses', [QuestionGeneratorController::class, 'listCourses']);

Route::get('/courses/{course}/topics', [QuestionGeneratorController::class, 'listTopics']);

Route::post('/cuestionarios/generar', [QuestionGeneratorController::class, 'generate']);

Route::post('/resumenes/generar', [SummaryGeneratorController::class, 'generate']);

Route::post('/evaluador/evaluar', [AnswerEvaluatorController::class, 'evaluate']);

Route::post('/materiales/extract-text', [MaterialController::class, 'extractText']);

Route::post('/materiales/upload', [MaterialController::class, 'uploadTxtToSupabase']);

Route::get('/materiales/list-topics', [MaterialController::class, 'listTxtFiles']);

Route::get('/materiales/list-topics-with-content', [MaterialController::class, 'listTxtFilesWithContent']);

Route::delete('/materiales/delete', [MaterialController::class, 'deleteMaterial']);

Route::post('/images/generate', [ImageGeneratorController::class, 'generate']);

Route::post('/courses/generate-topics', [CourseTopicGeneratorController::class, 'generate']);
