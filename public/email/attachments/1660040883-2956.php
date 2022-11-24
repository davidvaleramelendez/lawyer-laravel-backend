<?php

use App\Http\Controllers\Api\AppsController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CalendarController;
use App\Http\Controllers\Api\CaseController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EmailController;
use App\Http\Controllers\Api\GoogleController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\LetterController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\TimelineController;
use App\Http\Controllers\Api\TodoController;
use App\Http\Controllers\Api\TestsController;
use App\Http\Controllers\Api\CaseRecordController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group([
    'middleware' => 'api',
    'prefix' => 'auth',
], function ($router) {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::get('/user-profile', [AuthController::class, 'userProfile']);

});

Route::group([
    'middleware' => 'auth:sanctum',
], function ($router) {
    Route::get('/dashboard', [DashboardController::class, 'getData']);
    Route::get('/user/get_users', [AppsController::class, 'get_users']);
    Route::get('/user/test_get_users', [TestsController::class, 'get_users']);
    Route::get('/get_user_view_case/{id}', [AppsController::class, 'get_user_view_case']);
    Route::get('/get_roles', [AppsController::class, 'get_roles']);
    Route::post('/admin/add_user', [AppsController::class, 'add_user']);
    Route::get('/admin/get_user/{id}', [AppsController::class, 'get_user']);
    Route::post('/admin/update_user', [AppsController::class, 'update_user']);
    Route::post('/admin/delete_user', [AppsController::class, 'delete_user']);

    Route::get('/get_user_view_case/{id}', [AppsController::class, 'get_user_view_case']);



    Route::get('/admin/get_contact_list', [ContactController::class,'get_contact_list'])->name('admin-get_contact_list');
    Route::post('/admin/add_contact', [ContactController::class,'add_contact'])->name('admin-add_contact');
    Route::post('/admin/get_contact', [ContactController::class,'get_contact'])->name('admin-get_contact');
    Route::get('/admin/get_contact_detail', [ContactController::class,'get_contact_detail'])->name('admin-get_contact_detail');
    Route::post('/admin/convert_contact_to_case', [ContactController::class,'convert_contact_to_case'])->name('admin-convert_contact_to_case');

    Route::get('/admin/get_case_list/{id}', [CaseController::class,'get_list'])->name('admin-get_case_list');
    Route::get('/admin/case/get/{id}', [CaseController::class,'get_detail'])->name('admin-get_case_detail');
    Route::post('/admin/case/add_letter', [CaseController::class,'letter_add'])->name('admin-case_letter_add');
    Route::post('/admin/case/update_letter', [CaseController::class,'letter_update'])->name('admin-case_letter_update');
    Route::post('/admin/case/delete_letter', [CaseController::class,'letter_delete'])->name('admin-case_letter_delete');
    Route::post('/admin/case/add_fighter', [CaseController::class,'fighter_add'])->name('admin-case_fighter_add');

    Route::post('/admin/case/letter_erledigt', [CaseController::class,'case_letter_erledigt'])->name('admin-case_letter_erledigt');

    Route::get('/admin/case/case_records/{id}', [CaseRecordController::class,'get_case_records'])->name('get_case_record');
    Route::post('/admin/case/case_record/create', [CaseRecordController::class,'add_case_record_record'])->name('add_case_record');
    Route::get('/admin/case/case_record/{id}', [CaseRecordController::class,'get_case_record'])->name('get_case_record');
    Route::post('/admin/case/case_record/update', [CaseRecordController::class,'update_case_record'])->name('update_case_record');
    Route::post('/admin/case/case_record/delete', [CaseRecordController::class,'delete_case_record'])->name('delete_case_record');

    Route::get('/admin/case/case_record/texts/{id}', [CaseRecordController::class,'get_case_record_texts']);
    Route::get('/admin/case/case_record/text/{id}', [CaseRecordController::class,'get_case_record_text']);
    Route::post('/admin/case/case_records/text/create', [CaseRecordController::class,'add_case_record_text']);
    Route::post('/admin/case/case_records/text/update', [CaseRecordController::class,'update_case_record_text']);
    Route::post('/admin/case/case_records/text/delete', [CaseRecordController::class,'delete_case_record_text']);

    Route::get('/admin/case/case_record/files/{id}', [CaseRecordController::class,'get_case_record_files']);
    Route::get('/admin/case/case_record/file/{id}', [CaseRecordController::class,'get_case_record_file']);
    Route::post('/admin/case/case_records/file/create', [CaseRecordController::class,'add_case_record_file']);
    Route::post('/admin/case/case_records/file/update', [CaseRecordController::class,'update_case_record_file']);
    Route::post('/admin/case/case_records/file/delete', [CaseRecordController::class,'delete_case_record_file']);

    Route::get('/admin/case/case_record/times/{id}', [CaseRecordController::class,'get_case_record_times']);
    Route::get('/admin/case/case_record/time/{id}', [CaseRecordController::class,'get_case_record_time']);
    Route::post('/admin/case/case_records/time/create', [CaseRecordController::class,'add_case_record_time']);
    Route::post('/admin/case/case_records/time/update', [CaseRecordController::class,'update_case_record_time']);
    Route::post('/admin/case/case_records/time/delete', [CaseRecordController::class,'delete_case_record_time']);

    Route::post('/admin/case/case_records/email/{id}', [CaseRecordController::class,'case_send_email']);

    Route::get('/admin/get_contact_detail', [ContactController::class,'get_contact_detail'])->name('admin-get_contact_detail');
    Route::get('/email/imap/{folder}', [EmailController::class, 'emailImap'])->name('admin-email-imap');
    Route::post('/email/imap_reply', [EmailController::class,'sendReply_IMAP_Email'])->name('admin-email-imap-reply');
    Route::post('/email/delete', [EmailController::class,'delete'])->name('admin-email-delete');

    Route::get('/admin/get_invoice_list', [InvoiceController::class,'invoice_list'])->name('admin-invoice_list');
    Route::get('/admin/get_invoice_info', [InvoiceController::class,'invoice_info'])->name('admin-invoice_info');
    Route::post('/admin/invoice_save', [InvoiceController::class,'invoice_save'])->name('admin-invoice_save');

    Route::get('/admin/calendar/get_events', [CalendarController::class,'get_events'])->name('admin-get_events');
    Route::get('/admin/calendar/get_users', [CalendarController::class,'get_users'])->name('admin-calendar_get_events');
    Route::post('/admin/calendar/add_event', [CalendarController::class,'addEvent'])->name('admin-calendar_add_event');
    Route::post('/admin/calendar/delete_event', [CalendarController::class,'deleteEvent'])->name('admin-calendar_delete_event');

    Route::get('/admin/profile/get_setting', [ProfileController::class,'get_account_setting'])->name('admin-profile_get_setting');
    Route::post('/admin/profile/save_account', [ProfileController::class,'save_account'])->name('admin-profile_save_account');
    Route::post('/admin/profile/save_account_setting', [ProfileController::class,'save_account_setting'])->name('admin-profile_save_account_setting');
    Route::post('/admin/profile/save_account_imap', [ProfileController::class,'save_account_imap'])->name('admin-profile_save_account_imap');

    Route::get('/admin/google/oauth', [GoogleController::class, 'store'])->name('google.store');
    Route::get('/admin/google/get_accounts', [GoogleController::class, 'get_accounts'])->name('google.get_accounts');
    Route::delete('google/{googleAccount}', [GoogleController::class, 'destroy'])->name('google.destroy');

    Route::get('/admin/letter/get_list', [LetterController::class,'get_letters'])->name('admin-kanban');
    Route::post('/admin/letter/update_archived', [LetterController::class,'case_documents_archived'])->name('admin-letter-archived');


    Route::get('/admin/todo/get/{filter}', [TodoController::class,'get_todo'])->name('admin-todo-completed');
    Route::get('/admin/todo/get_users', [TodoController::class,'get_users'])->name('admin-todo-get_users');
    Route::post('/admin/todo/create', [TodoController::class,'create_todo'])->name('admin-todo-create_todo');
    Route::post('/admin/todo/delete', [TodoController::class,'delete_todo'])->name('admin-todo-delete_todo');

    Route::get('/admin/timeline/get_all', [TimelineController::class,'get_all'])->name('admin-timeline-get_all');
    Route::post('/admin/timeline/save', [TimelineController::class,'save'])->name('admin-timeline-save');

    Route::get('/admin/chat/get_chats', [ChatController::class,'get_chats'])->name('admin-chat-get_chats');
    Route::get('/admin/chat/get_users', [ChatController::class,'get_users'])->name('admin-chat-get_users');

    Route::get('/admin/helper/get_contacts', [AppsController::class, 'get_contacts'])->name('admin-helper-get_contacts');
    Route::get('/admin/helper/get_notifications', [AppsController::class, 'get_notifications'])->name('admin-helper-get_notifications');
    Route::get('/admin/helper/get_chats', [AppsController::class, 'get_chats'])->name('admin-helper-get_chats');
    Route::post('/admin/helper/search_data', [AppsController::class, 'search_data'])->name('admin-helper-search_data');

});
