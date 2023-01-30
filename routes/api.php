<?php

use App\Http\Controllers\Api\AddEventController;
use App\Http\Controllers\Api\AppsController;
use App\Http\Controllers\Api\AttachmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AuthenticationLogContoller;
use App\Http\Controllers\Api\CaseController;
use App\Http\Controllers\Api\CaseDocumentController;
use App\Http\Controllers\Api\CaseRecordController;
use App\Http\Controllers\Api\CaseTypeController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\CloudStorageController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\ContactImapController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EmailController;
use App\Http\Controllers\Api\EmailTemplateAttachmentController;
use App\Http\Controllers\Api\EmailTemplateController;
use App\Http\Controllers\Api\GoogleController;
use App\Http\Controllers\Api\ImportLetterFileController;
use App\Http\Controllers\Api\InquiryImapController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\LanguageController;
use App\Http\Controllers\Api\LetterController;
use App\Http\Controllers\Api\LetterTemplateController;
use App\Http\Controllers\Api\MusterDocumentController;
use App\Http\Controllers\Api\PdfApiController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\SiteSettingsController;
use App\Http\Controllers\Api\TimelineController;
use App\Http\Controllers\Api\TodoController;
use App\Http\Controllers\Api\TopNotificationController;
use App\Http\Controllers\Api\VoiceRecordingController;
use App\Http\Controllers\GoogleWebhookController;
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
    Route::get('/logout', [AuthController::class, 'logout']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::get('/refresh', [AuthController::class, 'refresh']);
    Route::get('/user-profile', [AuthController::class, 'userProfile']);
    Route::post('/user/change-password', [AuthController::class, 'changeAuthUserPassword']);
});

/* Cron job */
Route::get('/email/email-imap/INBOX/cron', [EmailController::class, 'emailCron'])->name('email-cron');
Route::get('/contact/contact-imap/cron', [ContactController::class, 'contactImapCron'])->name('contact-imap-cron');
Route::get('/import-letter-file/cron/import-pdf', [ImportLetterFileController::class, 'cronImportDropboxFiles']);
/* /Cron job */

Route::group([
    'middleware' => 'auth:sanctum',
], function ($router) {
    Route::get('/dashboard', [DashboardController::class, 'getData']);
    Route::get('/admin/global-search', [DashboardController::class, 'globalSearch']);
    Route::post('/admin/update-bookmark', [DashboardController::class, 'updateBookmark']);
    Route::get('/admin/get-bookmark', [DashboardController::class, 'getBookmark']);

    Route::get('/admin/user/get-user-count', [AppsController::class, 'getUserCounteByRole']);
    Route::get('/admin/users', [AppsController::class, 'get_users'])->name('get_users');
    Route::get('/get_user_view_case/{id}', [AppsController::class, 'get_user_view_case']);
    Route::get('/admin/roles', [AppsController::class, 'get_roles']);
    Route::post('/admin/user/create', [AppsController::class, 'add_user']);
    Route::get('/admin/user/{id}', [AppsController::class, 'get_user']);
    Route::post('/admin/user/update', [AppsController::class, 'update_user']);
    Route::get('/admin/user/delete/{id}', [AppsController::class, 'delete_user']);
    Route::post('againstperson', [AppsController::class, 'againstperson'])->name('admin-add-againstperson');

    Route::get('admin/contact', [ContactController::class, 'contact_list'])->name('admin-contact');
    Route::get('/admin/get_contact_list', [ContactController::class, 'get_contact_list'])->name('admin-get_contact_list');
    Route::post('/admin/add_contact', [ContactController::class, 'add_contact'])->name('admin-add_contact');
    Route::post('/admin/get_contact', [ContactController::class, 'get_contact'])->name('admin-get_contact');
    Route::get('/admin/get_contact_detail', [ContactController::class, 'get_contact_detail'])->name('admin-get_contact_detail');
    Route::post('/admin/convert_contact_to_case', [ContactController::class, 'convert_contact_to_case'])->name('admin-convert_contact_to_case');
    Route::post('/admin/contact/add_note', [ContactController::class, 'contact_add_note'])->name('contact_add_note');
    Route::post('admin/contact/update/{id}', [ContactController::class, 'update_contact'])->name('update-contact');
    Route::get('admin/contact/view/{id}', [ContactController::class, 'contact_view'])->name('admin-contact-view');
    Route::get('admin/contact/delete/{id}', [ContactController::class, 'contact_delete'])->name('admin-contact-delete');
    Route::post('admin/contact/replyemail', [ContactController::class, 'replyemail'])->name('admin-contact-replyemail');

    Route::post('/admin/permissions/update', [AppsController::class, 'user_permissions_update'])->name('user_permissions_update');
    Route::get('/admin/permissions/get/{userID}', [AppsController::class, 'user_permissions_get'])->name('user_permissions_update');

    Route::get('/admin/get_case_list', [CaseController::class, 'get_list'])->name('admin-get_case_list');
    Route::get('/admin/case/get/{id}', [CaseController::class, 'get_detail'])->name('admin-get_case_detail');
    Route::post('/admin/case/update_case', [CaseController::class, 'update_case'])->name('admin_update_case');
    Route::get('/admin/case/close_case/{id}', [CaseController::class, 'close_case'])->name('admin_close_case');
    Route::get('/admin/case/share_case/{id}', [CaseController::class, 'share_case'])->name('admin_share_case');
    Route::get('case/delete/{id}', [AppsController::class, 'delete_case'])->name('delete_case');

    Route::post('/admin/case/add_letter', [CaseController::class, 'letter_add'])->name('admin-case_letter_add');
    Route::post('/admin/case/update_letter', [CaseController::class, 'letter_update'])->name('admin-case_letter_update');
    Route::get('/admin/case/delete_letter/{id}', [CaseController::class, 'letter_delete'])->name('admin-case_letter_delete');
    Route::post('/admin/case/add_fighter', [CaseController::class, 'fighter_add'])->name('admin-case_fighter_add');

    Route::get('/admin/letter/get_list', [LetterController::class, 'get_letters'])->name('admin-kanban');
    Route::get('/admin/letter/{id}', [LetterController::class, 'get_letter']);
    Route::get('/admin/letter/update_archived/{id}', [LetterController::class, 'case_documents_archived'])->name('admin-letter-archived');
    Route::get('/admin/letter/update_status/{id}', [LetterController::class, 'case_letter_update_status'])->name('case_letter_update_status');
    Route::get('/admin/case/letter/list', [LetterController::class, 'get_case_letters']);

    Route::get('/admin/case/letter_erledigt/{id}', [CaseController::class, 'case_letter_erledigt'])->name('admin-case_letter_erledigt');

    /* Case Email */
    Route::get('/admin/case/emails', [CaseController::class, 'getCaseEmails'])->name('admin-case-emails');
    Route::post('/admin/case/email/send', [CaseController::class, 'sendCaseMail'])->name('admin-case-send-email');
    Route::post('/admin/case/email/reply', [CaseController::class, 'sendCaseReplyEmail'])->name('admin-case-reply-email');
    /* Case Email */

    Route::post('/admin/case/case_records/email', [CaseRecordController::class, 'case_send_email'])->name('send-email');
    Route::get('/admin/case/case_records/{id}', [CaseRecordController::class, 'get_case_records'])->name('get_case_record');
    Route::post('/admin/case/case_record/delete', [CaseRecordController::class, 'delete_case_record'])->name('delete_case_record');

    Route::get('/admin/case/note/case_records', [CaseRecordController::class, 'get_case_record_notes']);
    Route::get('/admin/case/note/case_record/{id}', [CaseRecordController::class, 'get_case_record_note']);
    Route::post('/admin/case/note/case_record/create', [CaseRecordController::class, 'add_case_record_note']);
    Route::post('/admin/case/case_records/text/update', [CaseRecordController::class, 'update_case_record_text']);

    Route::post('/admin/case/case_records/file/update', [CaseRecordController::class, 'update_case_record_file']);

    Route::get('/admin/case/case_record/times/{id}', [CaseRecordController::class, 'get_case_record_times']);
    Route::get('/admin/case/case_record/time/{id}', [CaseRecordController::class, 'get_case_record_time']);
    Route::post('/admin/case/case_records/time/create', [CaseRecordController::class, 'add_case_record_time']);
    Route::post('/admin/case/case_records/time/update', [CaseRecordController::class, 'update_case_record_time']);
    Route::post('/admin/case/case_records/time/delete', [CaseRecordController::class, 'delete_case_record']);

    Route::get('/admin/get_contact_detail', [ContactController::class, 'get_contact_detail'])->name('admin-get_contact_detail');

    Route::get('/admin/email/imap/{folder}', [EmailController::class, 'emailImap'])->name('admin-email-imap');
    Route::get('/admin/email/{id}', [EmailController::class, 'getEmail']);
    Route::get('/admin/email/importants', [EmailController::class, 'getImportantEmail'])->name('admin-important');
    Route::get('/admin/email', [EmailController::class, 'emailApp'])->name('admin-email-sent');
    Route::get('/admin/email/delete', [EmailController::class, 'get_email_trash'])->name('admin-deleted-email');

    Route::post('/admin/email/imap_reply', [EmailController::class, 'sendReply_IMAP_Email'])->name('admin-email-imap-reply');
    Route::post('/admin/email/send_mail', [EmailController::class, 'send_mail'])->name('admin-email-send');
    Route::post('/admin/email/delete', [EmailController::class, 'delete'])->name('admin-email-delete');
    Route::post('/admin/email/trash', [EmailController::class, 'emailTrash'])->name('admin-email-trash');
    Route::get('/admin/email/important/create', [EmailController::class, 'emailImportant'])->name('admin-email-important');
    Route::post('/admin/email/reply', [EmailController::class, 'sendReplyEmail'])->name('admin-email-reply');
    Route::get('/admin/email/reply/{id}', [EmailController::class, 'getEmailReply'])->name('get-admin-email-reply');
    Route::get('/admin/email/details-imap/{id}', [EmailController::class, 'showImapEmailDetails'])->name('admin-details-imap');
    Route::get('/admin/new/email', [EmailController::class, 'checkNewEmail'])->name('get-admin-new-email');
    Route::get('/admin/email/email-imap/user/cron', [EmailController::class, 'emailAuthUserCron'])->name('email-user-cron');

    Route::post('/admin/attachment/create', [AttachmentController::class, 'uploadAttachment']);
    Route::get('/admin/attachment/delete/{id}', [AttachmentController::class, 'deleteAttachment']);
    Route::post('/admin/attachment/multiple/delete', [AttachmentController::class, 'deleteMultipleAttachment']);

    Route::any('/admin/email/inbox_count', [EmailController::class, 'inbox_count']);
    Route::any('/admin/email/important_count', [EmailController::class, 'important_count']);
    Route::any('/admin/email/mark_important', [EmailController::class, 'mark_important']);
    Route::post('/admin/email/mark_trash', [EmailController::class, 'mark_trash']);
    Route::post('/admin/email/mark_restore', [EmailController::class, 'mark_restore']);
    Route::post('/admin/email/mark_delete', [EmailController::class, 'mark_delete']);
    Route::get('/admin/email/inbox', [EmailController::class, 'inbox'])->name('admin-inbox');
    Route::get('/admin/email/status', [EmailController::class, 'checkNewEmail'])->name('get-admin-new-email');

    // // Email Draft
    Route::get('/admin/draft', [EmailController::class, 'getDraftList']);
    Route::get('/admin/draft/{id}', [EmailController::class, 'getDraftMail']);
    Route::post('/admin/draft', [EmailController::class, 'saveDraftMail']);
    Route::delete('/admin/draft/{ids}', [EmailController::class, 'deleteDrafts']);

    Route::get('/admin/invoice/list', [InvoiceController::class, 'invoice_list'])->name('admin-invoice_list');
    Route::get('/admin/invoice/info', [InvoiceController::class, 'invoice_info'])->name('admin-invoice_info');
    Route::get('/admin/invoice/{id}', [InvoiceController::class, 'invoice'])->name('admin-invoice');
    Route::post('/admin/invoice/save', [InvoiceController::class, 'invoice_save'])->name('admin-invoice_save');
    Route::post('/admin/invoice/update', [InvoiceController::class, 'invoice_update'])->name('admin-invoice-update');
    Route::get('/admin/invoice/delete/{id}', [InvoiceController::class, 'invoice_delete'])->name('admin-invoice-delete');
    Route::post('/admin/invoice/pay', [InvoiceController::class, 'makePayment'])->name('admin-invoice-pay');
    Route::post('/admin/invoice/send', [InvoiceController::class, 'sendInvoice'])->name('admin-send-invoice');
    Route::get('invoice/search/{id}', [InvoiceController::class, 'search'])->name('admin-search-caseid');
    Route::get('admin/invoice/customer/{id}', [InvoiceController::class, 'invoice_customer'])->name('admin-invoice-customer');

    Route::get('/admin/profile/get_setting', [ProfileController::class, 'get_account_setting'])->name('admin-profile_get_setting');
    Route::post('/admin/profile/save_account', [ProfileController::class, 'save_account'])->name('admin-profile_save_account');
    Route::post('/admin/profile/save_account_setting', [ProfileController::class, 'save_account_setting'])->name('admin-profile_save_account_setting');
    Route::post('/admin/profile/save_account_imap', [ProfileController::class, 'save_account_imap'])->name('admin-profile_save_account_imap');

    Route::get('/admin/language', [LanguageController::class, 'getLanguages'])->name('admin-language-getting');
    Route::get('/admin/language/labels', [LanguageController::class, 'getLanguageLables'])->name('admin-language-labels-getting');
    Route::post('/admin/language/labels', [LanguageController::class, 'setLanguageLabels'])->name('admin-language-labels-setting');

    Route::get('/admin/google/oauth', [GoogleController::class, 'store'])->name('google.store');
    Route::get('/admin/google/get_accounts', [GoogleController::class, 'get_accounts'])->name('google.get_accounts');
    Route::delete('google/{googleAccount}', [GoogleController::class, 'destroy'])->name('google.destroy');

    Route::get('/admin/todo/get_users', [TodoController::class, 'get_users'])->name('admin-todo-get_users');
    Route::get('/admin/todo/get_todos', [TodoController::class, 'get_todos'])->name('admin-todo-gets');
    Route::get('/admin/todo/get_todo/{id}', [TodoController::class, 'get_todo'])->name('admin-todo-get_todo');
    Route::post('/admin/todo/create', [TodoController::class, 'create_todo'])->name('admin-todo-create_todo');
    Route::get('/admin/todo/complete/{id}', [TodoController::class, 'complete_todo'])->name('admin-todo-complete_todo');
    Route::get('/admin/todo/important/{id}', [TodoController::class, 'important_todo'])->name('admin-todo-important_todo');
    Route::get('/admin/todo/trash/{id}', [TodoController::class, 'trash_todo'])->name('admin-todo-trash_todo');
    Route::get('/admin/todo/restore/{id}', [TodoController::class, 'restore_todo'])->name('admin-todo-restore_todo');
    Route::get('/admin/todo/delete/{id}', [TodoController::class, 'delete_todo'])->name('admin-todo-delete_todo');

    Route::get('/admin/event/get_users', [AddEventController::class, 'getUsers'])->name('admin-calendar_get_users');
    Route::get('/admin/event/get_events', [AddEventController::class, 'getEvents'])->name('admin-get-events');
    Route::post('/admin/event/add_event', [AddEventController::class, 'addEvent'])->name('admin-add-event');
    Route::post('/admin/event/update', [AddEventController::class, 'updateEvent'])->name('admin-update-event');
    Route::get('/admin/event/delete/{id}', [AddEventController::class, 'deleteEvent'])->name('admin-delete-event');

    Route::get('account-settings', [PagesController::class, 'account_settings'])->name('admin-account-settings');
    Route::post('account-update', [PagesController::class, 'account_update'])->name('admin-account-update');

    Route::get('/admin/timeline/get_all', [TimelineController::class, 'get_all'])->name('admin-timeline-get_all');
    Route::post('/admin/timeline/save', [TimelineController::class, 'save'])->name('admin-timeline-save');

    Route::get('/admin/chat/get_chat', [ChatController::class, 'get_chat'])->name('admin-chat-get_chats');
    Route::get('/admin/chat/get_users', [ChatController::class, 'get_users'])->name('admin-chat-get_users');
    Route::post('/admin/chat/send_chat', [ChatController::class, 'send_chat'])->name('admin-chat-send');
    Route::get('/admin/chat/history/{id}', [ChatController::class, 'chatHistory'])->name('admin-chat-history');

    Route::get('/admin/helper/get_contacts', [AppsController::class, 'get_contacts'])->name('admin-helper-get_contacts');
    Route::get('/admin/helper/get_notifications', [AppsController::class, 'get_notifications'])->name('admin-helper-get_notifications');
    Route::get('/admin/helper/get_chats', [AppsController::class, 'get_chats'])->name('admin-helper-get_chats');
    Route::post('/admin/helper/search_data', [AppsController::class, 'search_data'])->name('admin-helper-search_data');

    // case-documents
    Route::get('/admin/case/case_documents', [CaseDocumentController::class, 'index'])->name('case_documents');
    Route::get('/admin/case/case_document/{id}', [CaseDocumentController::class, 'view'])->name('case_document');
    Route::post('/admin/case/case_document_add', [CaseDocumentController::class, 'case_document_add'])->name('case_document_add');
    Route::post('/admin/case/case_document_update', [CaseDocumentController::class, 'case_document_update'])->name('case_document_update');
    Route::get('/admin/case/case_document_isErledigt/{id}', [CaseDocumentController::class, 'case_document_isErledigt'])->name('case_document_isErledigt');
    Route::get('/admin/case/case_document_delete/{id}', [CaseDocumentController::class, 'case_document_delete'])->name('case_document_delete');
    Route::post('/admin/case/case_documents_archived', [CaseDocumentController::class, 'case_documents_archived']);

    Route::get('/contact-imap', [ContactImapController::class, 'index']);
    Route::post('/update-imap', [ContactImapController::class, 'updateAccount'])->name('admin-update-imap');

    Route::get('/admin/get-site-setting', [SiteSettingsController::class, 'getnavbarSetting'])->name('admin-get-setting-navbar');
    Route::post('/admin/site-setting', [SiteSettingsController::class, 'navbarSetting'])->name('admin-setting-navbar');

    Route::get('/admin/documents', [MusterDocumentController::class, 'index']);
    Route::get('/admin/document/{id}', [MusterDocumentController::class, 'get_document']);
    Route::post('/admin/document/create', [MusterDocumentController::class, 'create']);
    Route::post('/admin/document/update/{id}', [MusterDocumentController::class, 'update']);
    Route::post('/admin/document/delete/{id}', [MusterDocumentController::class, 'delete']);

    Route::get('/admin/email-templates', [EmailTemplateController::class, 'get_email_templates']);
    Route::get('/admin/email-template/{id}', [EmailTemplateController::class, 'get_email_template']);
    Route::post('/admin/email-template/create', [EmailTemplateController::class, 'email_template_create']);
    Route::post('/admin/email-template/update', [EmailTemplateController::class, 'email_template_update']);
    Route::get('/admin/email-template/delete/{id}', [EmailTemplateController::class, 'email_template_delete']);
    Route::get('/admin/set-email-template/{id}', [EmailTemplateController::class, 'set_email_template']);

    Route::get('/admin/view-email-template/{id}', [EmailTemplateController::class, 'view_email_template']);

    Route::get('/admin/email-template-attachments', [EmailTemplateAttachmentController::class, 'get_email_template_attachments']);
    Route::get('/admin/email-template-attachment/{id}', [EmailTemplateAttachmentController::class, 'get_email_template_attachment']);
    Route::post('/admin/email-template-attachment/create', [EmailTemplateAttachmentController::class, 'email_template_attachment_create']);
    Route::post('/admin/email-template-attachment/update', [EmailTemplateAttachmentController::class, 'email_template_attachment_update']);
    Route::get('/admin/email-template-attachment/delete/{id}', [EmailTemplateAttachmentController::class, 'email_template_attachment_delete']);

    Route::get('/admin/case-types', [CaseTypeController::class, 'get_case_types']);
    Route::get('/admin/case-type/{id}', [CaseTypeController::class, 'get_case_type']);
    Route::post('/admin/case-type/create', [CaseTypeController::class, 'case_type_create']);
    Route::post('/admin/case-type/update', [CaseTypeController::class, 'case_type_update']);
    Route::get('/admin/case-type/delete/{id}', [CaseTypeController::class, 'case_type_delete']);

    Route::get('/admin/folders', [CloudStorageController::class, 'get_folders']);
    Route::get('/admin/folder/{id}', [CloudStorageController::class, 'get_folder']);
    Route::post('/admin/folder/create', [CloudStorageController::class, 'folder_create']);
    Route::post('/admin/folder/update', [CloudStorageController::class, 'folder_update']);
    Route::get('/admin/folder/trash/{id}', [CloudStorageController::class, 'markFolderTrash']);
    Route::get('/admin/folder/delete/{id}', [CloudStorageController::class, 'markFolderDelete']);
    Route::get('/admin/tree/folder', [CloudStorageController::class, 'treeStructure']);

    Route::get('/admin/files', [CloudStorageController::class, 'get_files']);
    Route::get('/admin/file/{id}', [CloudStorageController::class, 'get_file']);
    Route::post('/admin/file/create', [CloudStorageController::class, 'file_create']);
    Route::post('/admin/file/update', [CloudStorageController::class, 'file_update']);
    Route::get('/admin/file/trash/{id}', [CloudStorageController::class, 'markFileTrash']);
    Route::get('/admin/file/delete/{id}', [CloudStorageController::class, 'markFileDelete']);
    Route::get('/admin/cloud/mark-important', [CloudStorageController::class, 'markImportant']);
    Route::get('/admin/cloud/mark-restore', [CloudStorageController::class, 'markRestore']);

    Route::get('/admin/top-notification-email', [TopNotificationController::class, 'getAllUnreadNotification']);
    Route::get('/admin/top-notification-chat', [TopNotificationController::class, 'getAllUnreadChat']);
    Route::get('/admin/top-notification-contacts', [TopNotificationController::class, 'getContacts']);

    Route::get('/get-user-logs', [AuthenticationLogContoller::class, 'getUserLogs']);
    Route::get('/get-user-log/{id}', [AuthenticationLogContoller::class, 'getUserLog']);

    /* Companies */
    Route::post('/admin/companies/create_update', [CompanyController::class, 'createOrUpdate']);
    Route::get('/admin/companies/detail', [CompanyController::class, 'getCompanyDetail']);
    Route::get('/admin/companies/delete/{id}', [CompanyController::class, 'deleteCompany']);
    /* /Companies */

    /* Letter Templates */
    Route::get('/admin/letter-template/list', [LetterTemplateController::class, 'getLetterTemplateList']);
    Route::get('/admin/letter-template/{id}', [LetterTemplateController::class, 'getLetterTemplate']);
    Route::post('/admin/letter-template/create', [LetterTemplateController::class, 'letterTemplateCreate']);
    Route::post('/admin/letter-template/update', [LetterTemplateController::class, 'letterTemplateUpdate']);
    Route::get('/admin/letter-template/delete/{id}', [LetterTemplateController::class, 'letterTemplateDelete']);
    /* /Letter Templates */

    /* InquiryImap */
    Route::post('/admin/inquiry-imap/create_update', [InquiryImapController::class, 'createOrUpdate']);
    Route::get('/admin/inquiry-imap/detail', [InquiryImapController::class, 'getInquiryImapDetail']);
    Route::get('/admin/inquiry-imap/delete/{id}', [InquiryImapController::class, 'deleteInquiryImap']);
    /* /InquiryImap */

    /* PdfApi */
    Route::post('/admin/pdf-api/create_update', [PdfApiController::class, 'createOrUpdate']);
    Route::get('/admin/pdf-api/detail', [PdfApiController::class, 'getPdfApiDetail']);
    Route::get('/admin/pdf-api/delete/{id}', [PdfApiController::class, 'deletePdfApi']);
    /* /PdfApi */

    /* Auth user profile update */
    Route::post('/admin/profile/update-image', [ProfileController::class, 'update_account_profile']);

    /* VoiceRecording */
    Route::get('/admin/voice-recording/list', [VoiceRecordingController::class, 'getVoiceRecordings']);
    Route::get('/admin/voice-recording/{id}', [VoiceRecordingController::class, 'getVoiceRecording']);
    Route::post('/admin/voice-recording/create', [VoiceRecordingController::class, 'createVoiceRecording']);
    Route::post('/admin/voice-recording/update', [VoiceRecordingController::class, 'updateVoiceRecording']);
    Route::get('/admin/voice-recording/delete/{id}', [VoiceRecordingController::class, 'deleteVoiceRecording']);
    Route::get('/admin/voice-recording/is-erledigt/{id}', [VoiceRecordingController::class, 'isErledigtVoiceRecording']);
    /* /VoiceRecording */

    /* ImportLetterFile */
    Route::get('/admin/import-letter-file/list', [ImportLetterFileController::class, 'getImportLetterFiles']);
    Route::get('/admin/import-letter-file/{id}', [ImportLetterFileController::class, 'getImportLetterFile']);
    Route::post('/admin/import-letter-file/create', [ImportLetterFileController::class, 'createImportLetterFile']);
    Route::post('/admin/import-letter-file/update', [ImportLetterFileController::class, 'updateImportLetterFile']);
    Route::get('/admin/import-letter-file/delete/{id}', [ImportLetterFileController::class, 'deleteImportLetterFile']);
    Route::get('/admin/import-letter-file/is-erledigt/{id}', [ImportLetterFileController::class, 'isErledigtImportLetterFile']);
    Route::post('/admin/import-letter-file/create-multiple', [ImportLetterFileController::class, 'createMultipleImportLetterFile']);
    Route::post('/admin/import-letter-file/move-to-letter', [ImportLetterFileController::class, 'moveImportLetterFileToLetter']);
    /* /ImportLetterFile */
});

Route::post('google/webhook', [GoogleWebhookController::class, 'index'])->name('google.webhook');
