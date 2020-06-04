<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
use App\Http\Middleware\ApiAuthMiddleware;

Route::get('/', function () {
    return view('welcome');
});

//Rutas del API para los planes de mejoramiento
//User controller routes
Route::resource('/api/user', 'UserController')->middleware(ApiAuthMiddleware::class);
Route::post('/api/login', 'UserController@login');
Route::put('/api/user/update/password/{id}', 'UserController@updatePassword')->middleware(ApiAuthMiddleware::class);

//Plans controller routes
Route::resource('/api/plans', 'PlansController')->middleware(ApiAuthMiddleware::class);

//Improvement Opportunities routes
Route::resource('/api/opportunities', 'ImprovementOpportunitiesController')->middleware(ApiAuthMiddleware::class);
Route::get('/api/opportunities/plans/{id}/{status}', 'ImprovementOpportunitiesController@showByIdPlan')->middleware(ApiAuthMiddleware::class);
Route::get('/api/opportunities/process/{responsable}/{status}', 'ImprovementOpportunitiesController@showByResponsable')->middleware(ApiAuthMiddleware::class);
Route::get('/api/opportunities/word/{word}/{status}', 'ImprovementOpportunitiesController@showByWord')->middleware(ApiAuthMiddleware::class);
Route::put('/api/opportunities/update-lines/{id}', 'ImprovementOpportunitiesController@updateLines')->middleware(ApiAuthMiddleware::class);
Route::put('/api/opportunities/update-effectiveness-indicator/{id}', 'ImprovementOpportunitiesController@updateEffectivenessIndicator')->middleware(ApiAuthMiddleware::class);
Route::put('/api/opportunities/update-status/{id}/{status}', 'ImprovementOpportunitiesController@updateStatus')->middleware(ApiAuthMiddleware::class);
Route::put('/api/opportunities/homologate/{id}', 'ImprovementOpportunitiesController@updateDataHomologate')->middleware(ApiAuthMiddleware::class);

//Actions routes
Route::resource('/api/actions', 'ActionsController')->middleware(ApiAuthMiddleware::class);
Route::get('/api/actions/show/opportunity/{id}', 'ActionsController@showByOpportunityId')->middleware(ApiAuthMiddleware::class);
Route::put('/api/actions/update/first-line/{id}', 'ActionsController@updateFirstLine')->middleware(ApiAuthMiddleware::class);
Route::put('/api/actions/update/second-line/{id}', 'ActionsController@updateSecondLine')->middleware(ApiAuthMiddleware::class);
Route::put('/api/actions/update/third-line/{id}', 'ActionsController@updateThirdLine')->middleware(ApiAuthMiddleware::class);
Route::put('/api/actions/update/all-lines/{id}', 'ActionsController@updateAllLines')->middleware(ApiAuthMiddleware::class);
Route::post('/api/actions/first-line/upload-file', 'ActionsController@uploadFile')->middleware(ApiAuthMiddleware::class);
Route::get('/api/actions/first-line/get-file/{filename}', 'ActionsController@getFile');
Route::delete('/api/actions/first-line/delete-file/{filename}', 'ActionsController@deleteFile')->middleware(ApiAuthMiddleware::class);
Route::get('/api/actions/export/all', 'ActionsController@showToExport')->middleware(ApiAuthMiddleware::class);