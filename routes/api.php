<?php

use App\Http\Controllers\Api\HeritageEntityApiController;
use App\Http\Controllers\Api\PortalApiController;
use App\Http\Controllers\Api\ArtemisIAApiController;
use Illuminate\Support\Facades\Route;

Route::get('/heritage-entities/search', [HeritageEntityApiController::class, 'search']);
Route::get('/heritage-entities/aggregations', [HeritageEntityApiController::class, 'aggregations']);
Route::get('/heritage-entities/count', [HeritageEntityApiController::class, 'count']);
Route::get('/heritage-entities/{id}', [HeritageEntityApiController::class, 'show']);

Route::get('/getSubject/{id}', [PortalApiController::class, 'getSubject']);
Route::get('/search', [PortalApiController::class, 'search']);
Route::get('/autocomplete', [PortalApiController::class, 'autocomplete']);
Route::get('/autocompleteFilter', [PortalApiController::class, 'autocompleteFilter']);
Route::get('/getMiniMapData', [PortalApiController::class, 'getMiniMapData']);
Route::get('/getSearchAggregationData', [PortalApiController::class, 'getSearchAggregationData']);
Route::get('/getPeriodRegions', [PortalApiController::class, 'getPeriodRegions']);
Route::get('/getPeriodsForCountry', [PortalApiController::class, 'getPeriodsForCountry']);
Route::get('/getTotalRecordsCount', [PortalApiController::class, 'getTotalRecordsCount']);
Route::get('/getAllServicesAndPublishers', [PortalApiController::class, 'getAllServicesAndPublishers']);
Route::get('/getAllNoFormats', [PortalApiController::class, 'getAllNoFormats']);
Route::get('/getRecord/{id}', [PortalApiController::class, 'getRecord']);
Route::get('/getRecord/{id}/xml', [PortalApiController::class, 'getRecordXml']);

Route::post('/artemisia/chat', [ArtemisIAApiController::class, 'chat']);
