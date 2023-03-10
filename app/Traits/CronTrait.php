<?php

namespace App\Traits;

use App\Models\DropboxApiToken;
use App\Models\ImportLetterFile;
use App\Models\PdfApi;
use Carbon\Carbon;
use CloudConvert\CloudConvert;
use CloudConvert\Models\Job;
use CloudConvert\Models\Task;
use Dcblogdev\Dropbox\Facades\Dropbox;
use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

trait CronTrait
{
    protected $apiKey = "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiIxIiwianRpIjoiYWM3ODU3YjlkODZhNmMzOWQ4NDIyYWZiZWI4YjNlZWYyMWI4MjgyMjJmOTAyZTU4NTI4YmFmNTgxYjk4OTM3M2I4Y2JhNWNjN2I4ZGJhOTkiLCJpYXQiOjE2NTkzNTA0OTguNjkyMTA3LCJuYmYiOjE2NTkzNTA0OTguNjkyMTA5LCJleHAiOjQ4MTUwMjQwOTguNjg3MjY4LCJzdWIiOiI1NDUzNDg2NCIsInNjb3BlcyI6WyJ1c2VyLnJlYWQiLCJ1c2VyLndyaXRlIiwidGFzay5yZWFkIiwidGFzay53cml0ZSIsIndlYmhvb2sucmVhZCIsIndlYmhvb2sud3JpdGUiLCJwcmVzZXQucmVhZCIsInByZXNldC53cml0ZSJdfQ.n3kzFYSiDzSjkvzNduyWe-ZoaSucgTylpclP2O0oI4NKrA9Vd0qU6FAnOYq8MOK25MqbxnR-mkvQAGzNjVHCFv4xnWQq9lGzbs6N_ReEnjZbKi2Dntflr5KhYOu4NRo6EsW3Ko2p6dEBPqOowvh5BC7EGCmFlpHfXBtJ9vtLKXR-unfRdQLM-Zh6noYMf63uN8LvD71WRKUDJBchA4-7rrNIM5Fl0roR7SAZ1tHtBSu5tdnRMOgVomLoq3BGdWC6pWsI90IjeqjrORWQHM-kJez0__uytHo-2ohlTUWdzWB68Fq7To-VGXoiNPQSiPnaE8aPI1f_o8Tu28ngyedDcNKRPLYwkf1XmI5J_aGRsoMqBcqwEofSpPWOkLoJKqCwdpRGHdqckg4ImQ-9ACvQILS_QJYLB4i4_UmDy7sgGQ5Xo6MQl37WZKQOWw1bPkn9mNnoZNVAb_qmnCUHV6btdIxAlMN7JPZ-i1JbSoR6bBkb78RwxsNLqwrvE9EfNb-qyt6yfWyE5l5l6hNlqam06DYCjqrY-2Ev2EiVOr_v4ML3EtaXcPgOw0YFG_gf26D4MxKrlWaiu7KUmop2VtSo3HXSmtk6gXWxZkgcmNhP_Gf3P4Den3opsXbOAK7mWkwswxFsXKk0hh1TTdwrwKtxzlfCt496rQ6HCZKu-tbr4g4";

    public function cron_trait_docs_to_pdf($name)
    {
        $case_docs = DB::table("case_docs")->where("attachment", $name)->first();
        $pdfApi = PdfApi::orderBy('id', 'DESC')->first();
        if ($pdfApi && $pdfApi->key) {
            $this->apiKey = $pdfApi->key;
        }

        if (!$case_docs) {
            return false;
        }
        $fullFilePath = str_replace('/', '*', $case_docs->attachment);

        $cloudconvert = new CloudConvert(['api_key' => $this->apiKey]);
        $job = (new Job())
            ->addTask(
                (new Task('import/url', 'import-2'))
                    ->set('url', url('preview/' . $fullFilePath))
                    ->set('filename', $case_docs->attachment)
            )
            ->addTask(
                (new Task('convert', 'task2'))
                    ->set('input_format', 'docx')
                    ->set('output_format', 'pdf')
                    ->set('engine', 'office')
                    ->set('input', ["import-2"])
                    ->set('optimize_print', true)
                    ->set('pdf_a', false)
                    ->set('engine_version', '2019')
                    ->set('filename', str_replace(".docx", ".pdf", $case_docs->attachment))
            )
            ->addTask(
                (new Task('export/url', 'task3'))
                    ->set('input', ["task2"])
                    ->set('inline', false)
                    ->set('archive_multiple_files', false)
            );
        $tt = $cloudconvert->jobs()->create($job);
        $cloudconvert->jobs()->wait($job); // Wait for job completion

        foreach ($job->getExportUrls() as $file) {
            $source = $cloudconvert->getHttpTransport()->download($file->url)->detach();
            $filePath = config('global.document_path') ? config('global.document_path') : 'uploads/documents';
            if (Storage::put($filePath . '/' . $file->filename, $source)) {
                $path = $filePath . '/' . $file->filename;
                DB::table("case_docs")->where("id", $case_docs->id)->update(["attachment_pdf" => $path]);
            }
        }
    }

    public function cron_trait_letter_to_pdf($attachment)
    {
        try {
            $letter_docs = DB::table("letters")->where("word_file", $attachment)->first();
            if (!$letter_docs) {
                return false;
            }

            $pdfApi = PdfApi::orderBy('id', 'DESC')->first();
            if ($pdfApi && $pdfApi->key) {
                $this->apiKey = $pdfApi->key;
            }

            $fullFilePath = str_replace('/', '*', $letter_docs->word_path);

            $cloudconvert = new CloudConvert(['api_key' => $this->apiKey]);
            $job = (new Job())
                ->addTask(
                    (new Task('import/url', 'import-2'))
                        ->set('url', url('preview/' . $fullFilePath))
                        ->set('filename', $letter_docs->word_file)
                )
                ->addTask(
                    (new Task('convert', 'task2'))
                        ->set('input_format', 'docx')
                        ->set('output_format', 'pdf')
                        ->set('engine', 'office')
                        ->set('input', ["import-2"])
                        ->set('optimize_print', true)
                        ->set('pdf_a', false)
                        ->set('engine_version', '2019')
                        ->set('filename', str_replace(".docx", ".pdf", $letter_docs->word_file))
                )
                ->addTask(
                    (new Task('export/url', 'task3'))
                        ->set('input', ["task2"])
                        ->set('inline', false)
                        ->set('archive_multiple_files', false)
                );

            $tt = $cloudconvert->jobs()->create($job);
            $cloudconvert->jobs()->wait($job); // Wait for job completion

            foreach ($job->getExportUrls() as $file) {
                $source = $cloudconvert->getHttpTransport()->download($file->url)->detach();
                $filePath = config('global.document_path') ? config('global.document_path') : 'uploads/documents';
                if (Storage::put($filePath . '/' . $file->filename, $source)) {
                    $path = $filePath . '/' . $file->filename;
                    DB::table("letters")->where("id", $letter_docs->id)->update(["pdf_file" => $file->filename, "pdf_path" => $path]);
                }
            }
            return false;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function cron_trait_invoice_to_pdf($attachment)
    {
        try {
            $invoice_docs = DB::table("invoices")->where("word_file", $attachment)->first();
            if (!$invoice_docs) {
                return false;
            }

            $pdfApi = PdfApi::orderBy('id', 'DESC')->first();
            if ($pdfApi && $pdfApi->key) {
                $this->apiKey = $pdfApi->key;
            }

            $fullFilePath = str_replace('/', '*', $invoice_docs->word_path);

            $cloudconvert = new CloudConvert(['api_key' => $this->apiKey]);
            $job = (new Job())
                ->addTask(
                    (new Task('import/url', 'import-2'))
                        ->set('url', url('preview/' . $fullFilePath))
                        ->set('filename', $invoice_docs->word_file)
                )
                ->addTask(
                    (new Task('convert', 'task2'))
                        ->set('input_format', 'docx')
                        ->set('output_format', 'pdf')
                        ->set('engine', 'office')
                        ->set('input', ["import-2"])
                        ->set('optimize_print', true)
                        ->set('pdf_a', false)
                        ->set('engine_version', '2019')
                        ->set('filename', str_replace(".docx", ".pdf", $invoice_docs->word_file))
                )
                ->addTask(
                    (new Task('export/url', 'task3'))
                        ->set('input', ["task2"])
                        ->set('inline', false)
                        ->set('archive_multiple_files', false)
                );

            $cloudconvert->jobs()->create($job);
            $cloudconvert->jobs()->wait($job); // Wait for job completion

            foreach ($job->getExportUrls() as $file) {
                $source = $cloudconvert->getHttpTransport()->download($file->url)->detach();
                $filePath = config('global.document_path') ? config('global.document_path') : 'uploads/documents';
                if (Storage::put($filePath . '/' . $file->filename, $source)) {
                    $path = $filePath . '/' . $file->filename;
                    DB::table("invoices")->where("id", $invoice_docs->id)->update(["pdf_file" => $file->filename, "pdf_path" => $path]);
                    // $result['fff'] = $file->filename;
                    // $result['data'] = $invoice_docs->id;
                }
            }

            return false;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function cronTraitImportDropboxLetterPdfFiles()
    {
        try {
            $folderName = "My Folder";
            $filePath = config('global.import_letter_file_path') ? config('global.import_letter_file_path') : 'uploads/importletterfiles';

            $dropboxApiToken = DropboxApiToken::orderBy('id', 'DESC')->first();
            if ($dropboxApiToken && $dropboxApiToken->id) {
                Config::set('dropbox.clientId', $dropboxApiToken->client_id ?? "");
                Config::set('dropbox.clientSecret', $dropboxApiToken->secret ?? "");
                Config::set('dropbox.accessToken', $dropboxApiToken->token ?? "");
                Config::set('dropbox.accessType', $dropboxApiToken->access_type ?? "offline");
            }

            // dd(Config::get('dropbox.clientId'));
            $dropbox = Dropbox::connect();
            $fileList = Dropbox::files()->listContents($path = $folderName);
            if ($fileList && @$fileList['entries']) {
                $files = $fileList['entries'];
                if ($files && count($files) > 0) {
                    if (!Storage::exists($filePath)) {
                        Storage::makeDirectory($filePath);
                    }

                    foreach ($files as $file) {
                        $name = $this->removeAfterPositionValue($file['name'], ".");
                        $name = $this->removeAfterPositionValue($name, "^");
                        $format = explode("-", $name);
                        $caseId = null;
                        $subject = null;
                        $fristDate = null;

                        if ($format && count($format) > 0) {
                            foreach ($format as $key => $value) {
                                if ($key == 2) {
                                    $value = str_replace("_", "-", $value);
                                    if (new Carbon($value)) {
                                        $fristDate = new Carbon($value);
                                    }
                                } else if ($key == 1) {
                                    $subject = str_replace("_", " ", $value);
                                } else {
                                    $caseId = $value;
                                }
                            }
                        }

                        if ($file['path_lower']) {
                            $extension = null;
                            $oldFileName = explode(".", $file['name']);
                            if ($oldFileName && count($oldFileName) > 0) {
                                $extension = $oldFileName[count($oldFileName) - 1];
                            }

                            $extension = $extension ?? "pdf";

                            if ($extension == "pdf") {
                                $file_name = time() . '-' . rand(0000, 9999) . '.' . $extension;
                                if (Dropbox::files()->download($file['path_lower'], storage_path("app/" . $filePath . '/'), $autoRename = false, $allowOwnershipTransfer = false)) {
                                    if (Storage::exists($filePath . "/" . $file['name'])) {
                                        $oldPath = storage_path('app/' . $filePath . "/" . $file['name']);
                                        $newPath = storage_path('app/' . $filePath . '/' . $file_name);
                                        File::move($oldPath, $newPath);

                                        $importedLetter = new ImportLetterFile();
                                        $importedLetter->name = $name ?? null;
                                        $importedLetter->case_id = $caseId ?? null;
                                        $importedLetter->subject = $subject ?? null;
                                        $importedLetter->frist_date = $fristDate ?? null;
                                        $importedLetter->file_name = $file_name;
                                        $importedLetter->file_path = $filePath . '/' . $file_name;
                                        $importedLetter->save();

                                        if ($importedLetter && $importedLetter->id) {
                                            Dropbox::files()->delete($file['path_lower']);
                                        }
                                    }
                                }
                            } else {
                                Dropbox::files()->delete($file['path_lower']);
                            }
                        }
                    }
                }
            }

            return false;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function removeAfterPositionValue($string = "", $key = "")
    {
        try {
            $position = strpos($string, $key);
            if ($position !== false) {
                $newString = substr($string, 0, $position);
                return $newString;
            }

            return $string;
        } catch (Exception $e) {
            return $string;
        }
    }
}
