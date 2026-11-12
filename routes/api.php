<?php

use App\Http\Controllers\AnnualMedicalExamController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DivisionController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\PhilpenAssessmentController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

// TEMPORARY: the routes below are NOT protected yet. Token login (Sanctum) is the next step.
Route::get('/divisions', [DivisionController::class, 'index']);
Route::get('/employees', [EmployeeController::class, 'index']);
Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
Route::get('/philpen-assessments/years', [PhilpenAssessmentController::class, 'years']);
Route::get('/annual-medical-exams/years', [AnnualMedicalExamController::class, 'years']);
Route::apiResource('philpen-assessments', PhilpenAssessmentController::class);
Route::apiResource('annual-medical-exams', AnnualMedicalExamController::class);
