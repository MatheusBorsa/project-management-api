<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ClientInvitationController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ArtController;

//Auth
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function() {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'show']);
    });
});

Route::middleware('auth:sanctum')->group(function() {
    Route::get('dashboard', [DashboardController::class, 'index']);

    Route::prefix('clients')->group(function () {
        Route::get('/', [ClientController::class, 'index']);           
        Route::post('/', [ClientController::class, 'store']);          
        Route::get('{id}', [ClientController::class, 'show']);     
        Route::put('{id}', [ClientController::class, 'update']);   
        Route::delete('{id}', [ClientController::class, 'destroy']); 
        
        Route::get('{id}/users', [ClientController::class, 'users']);              
        Route::put('{clientId}/users/{userId}', [ClientController::class, 'updateUser']);  
        Route::delete('{clientId}/users/{userId}', [ClientController::class, 'removeUser']); 
        
        Route::get('{clientId}/tasks', [TaskController::class, 'index']);               
        Route::post('{clientId}/tasks', [TaskController::class, 'store']);               
        
        Route::get('{clientId}/invitations', [ClientInvitationController::class, 'index']);    
        Route::post('{clientId}/invitations', [ClientInvitationController::class, 'store']);
    });

    Route::prefix('tasks')->group(function () {
        Route::get('{id}', [TaskController::class, 'show']);       
        Route::put('{id}', [TaskController::class, 'update']);          
        Route::delete('{id}', [TaskController::class, 'destroy']);  

        Route::patch('{id}/status', [TaskController::class, 'updateStatus']);  
                         
        Route::get('calendar/week', [TaskController::class, 'weeklyCalendar']);

        Route::post('/{task}/arts', [ArtController::class, 'store']);
        Route::delete('/arts/{id}', [ArtController::class, 'destroy']);
    });

    Route::prefix('invitations')->group(function () {
        Route::post('{invitationId}/resend', [ClientInvitationController::class, 'resend']);  
        Route::delete('{invitationId}', [ClientInvitationController::class, 'destroy']);     
        
        Route::post('{token}/accept', [ClientInvitationController::class, 'accept']); 
    });
});

Route::prefix('invitations')->group(function () {
    Route::get('{token}', [ClientInvitationController::class, 'show']);    
    Route::post('{token}/decline', [ClientInvitationController::class, 'decline']);
});