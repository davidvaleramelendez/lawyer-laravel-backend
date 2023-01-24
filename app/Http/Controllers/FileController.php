<?php

namespace App\Http\Controllers;

use Exception;
use Request;
use Response;
use Storage;

class FileController extends Controller
{
    public function getPreview($path)
    {
        try {
            $fullFilePath = str_replace('*', '/', $path);
            if (!Storage::exists($fullFilePath)) {
                abort(404);
            }

            $filename = "";
            $splitFilePath = explode("/", $fullFilePath);
            if ($splitFilePath && count($splitFilePath) > 0) {
                if ($splitFilePath[count($splitFilePath) - 1]) {
                    $filename = $splitFilePath[count($splitFilePath) - 1];
                }
            }

            $fullStoragePath = storage_path('app/' . $fullFilePath);
            $lifetime = 31556926; // One year in seconds

            $handler = new \Symfony\Component\HttpFoundation\File\File(storage_path('app/' . $fullFilePath));

            $file_time = $handler->getMTime(); // Get the last modified time for the file (Unix timestamp)
            $header_content_type = $handler->getMimeType();
            $header_content_length = $handler->getSize();
            $header_etag = md5($file_time . $fullFilePath);
            $header_last_modified = gmdate('r', $file_time);
            $header_expires = gmdate('r', $file_time + $lifetime);

            $headers = [
                'Content-Disposition' => 'inline; filename="' . $filename . '"',
                'Last-Modified' => $header_last_modified,
                'Cache-Control' => 'must-revalidate',
                'Expires' => $header_expires,
                'Pragma' => 'public',
                'Etag' => $header_etag,
            ];

            /**
             * Is the resource cached?
             */
            $h1 = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && $_SERVER['HTTP_IF_MODIFIED_SINCE'] == $header_last_modified;
            $h2 = isset($_SERVER['HTTP_IF_NONE_MATCH']) && str_replace('"', '', stripslashes($_SERVER['HTTP_IF_NONE_MATCH'])) == $header_etag;

            $headers = array_merge($headers, [
                'Content-Type' => $header_content_type,
                'Content-Length' => $header_content_length,
            ]);

            if (Request::get('download')) {
                return Response::download(storage_path('app/' . $fullFilePath), $filename, $headers);
            } else if ($h1 || $h2) {
                return Response::make('', 304, $headers); // File (image) is cached by the browser, so we don't have to send it again
            } else {
                return Response::file(storage_path('app/' . $fullFilePath), $headers);
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}
