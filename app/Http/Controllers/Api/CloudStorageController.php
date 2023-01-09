<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\CloudStorage;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

class CloudStorageController extends Controller
{
    public $breadcrumbs = array();

    public function treeStructure(Request $request)
    {
        try {
            $userId = $request->user_id ?? auth()->user()->id;

            $data = array(['id' => 0, 'name' => 'My Drive', 'slug' => 'default', 'parent_id' => null, 'user_id' => $userId, 'roll_id' => '', 'type' => 'folder', 'file_name' => null, 'extension' => null, 'path' => 'storage/cloudstorage/' . $userId, 'important_at' => null, 'created_at' => null, 'updated_at' => null, 'deleted_at' => null, 'value' => 0, 'label' => 'My Drive']);

            if ($userId != auth()->user()->id) {
                if (!Helper::get_user_permissions(16)) {
                    $response = array();
                    $response['flag'] = true;
                    $response['message'] = "You do not have permission.";
                    $response['data'] = $data;
                    return response()->json($response);
                }
            }

            $children = CloudStorage::with('children')->select('*', 'id as value', 'name as label')->where('user_id', $userId)->where('parent_id', null)->where('type', 'folder')->where('deleted_at', null)->get();

            $data[0]['children'] = $children;

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success.';
            $response['data'] = $data;
            return response()->json($response);
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = [];
            return response()->json($response);
        }
    }

    public function checkFolderParent($id, $breadcrumbs, $userId = "")
    {
        $this->breadcrumbs = $breadcrumbs;
        $userId = $userId ?? auth()->user()->id;
        if ($id) {
            $parent = CloudStorage::where('id', $id)->where('user_id', $userId)->where('deleted_at', null)->first();
            if ($parent && $parent->id) {
                array_push($this->breadcrumbs, $parent);
            }
            if ($parent && $parent->parent_id) {
                $this->checkFolderParent($parent->parent_id, $this->breadcrumbs, $userId);
            }
        }
    }

    public function cloudFilter($userId, $parentId, $search, $sortBy, $slug)
    {
        $folders = CloudStorage::where('type', 'folder')
            ->where('user_id', $userId);

        $files = CloudStorage::with('parent')
            ->select('*')
            ->where('type', 'file')
            ->where('user_id', $userId);

        if ($search) {
            $folders = $folders->where(function ($query) use ($search) {
                $query->Where('name', 'LIKE', "%{$search}%");
            });
            $files = $files->where(function ($query) use ($search) {
                $query->Where('name', 'LIKE', "%{$search}%");
            });
        }

        if ($slug == 'important') {
            $folders = $folders->whereNotNull('important_at');
            $files = $files->whereNotNull('important_at');
        } else if ($slug == 'recent') {
            $sortBy = "id-desc";
            $files = $files->where('deleted_at', null);
        } else if ($slug == 'trash') {
            $folders = DB::table('cloud_storage as main')->select('main.*')
                ->leftjoin('cloud_storage as parent', 'parent.id', '=', 'main.parent_id')
                ->where('main.user_id', $userId)
                ->where('main.type', 'folder')
                ->where('parent.deleted_at', null)
                ->where('main.deleted_at', '!=', null);

            $files = DB::table('cloud_storage as main')->select('main.*')
                ->leftjoin('cloud_storage as parent', 'parent.id', '=', 'main.parent_id')
                ->where('main.user_id', $userId)
                ->where('main.type', 'file')
                ->where('parent.deleted_at', null)
                ->where('main.deleted_at', '!=', null);

        } else {
            $folders = $folders->where('parent_id', $parentId);
            $files = $files->where('parent_id', $parentId);
        }

        if ($sortBy) {
            $sorting = explode("-", $sortBy);
            if ($sorting && count($sorting) > 1) {
                $folders = $folders->orderBy($sorting[0], $sorting[1]);
                $files = $files->orderBy($sorting[0], $sorting[1]);
            }
        }

        if ($slug == 'recent') {
            $folders = array();
        } else {
            $folders = $folders->get();
        }

        $files = $files->get();

        return ['folders' => $folders, 'files' => $files];
    }

    public function get_folders(Request $request)
    {
        try {
            $search = $request->input(key:'search') ?? '';
            $sortBy = $request->input(key:'sortBy') ?? 'name-asc';
            $slug = $request->input(key:'slug') ?? '';
            $userId = $request->user_id ?? auth()->user()->id;
            $parentId = null;
            $this->breadcrumbs = array();

            if ($userId != auth()->user()->id) {
                if (!Helper::get_user_permissions(16)) {
                    $response = array();
                    $response['flag'] = true;
                    $response['message'] = "You do not have permission.";
                    $response['data'] = ['breadcrumbs' => [], 'files' => [], 'folders' => []];
                    return response()->json($response);
                }
            }

            if ($slug && ($slug != 'important' || $slug != 'recent' || $slug != 'trash')) {
                $parentData = CloudStorage::where('slug', $slug)->where('user_id', $userId)->where('deleted_at', null)->first();
                if ($parentData && $parentData->id) {
                    $parentId = $parentData->id;
                    $this->checkFolderParent($parentId, $this->breadcrumbs, $userId);
                }
            }

            $data = $this->cloudFilter($userId, $parentId, $search, $sortBy, $slug);

            $folders = $data['folders'];
            $files = $data['files'];

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success.';
            $response['data'] = ['folders' => $folders, 'files' => $files, 'breadcrumbs' => $this->breadcrumbs];
            return response()->json($response);
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = [];
            return response()->json($response);
        }
    }

    public function get_folder(Request $request, $id)
    {
        try {
            $userId = $request->user_id ?? auth()->user()->id;

            $folder = CloudStorage::where('id', $id)->where('type', 'folder')->where('user_id', $userId)->where('deleted_at', null)->first();
            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success.';
            $response['data'] = $folder;
            return response()->json($response);
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = null;
            return response()->json($response);
        }
    }

    public function folder_create(Request $request)
    {
        try {
            if (!is_dir('storage/cloudstorage')) {
                File::makeDirectory(public_path('storage/cloudstorage'));
            }

            $validation = Validator::make($request->all(), [
                'name' => 'required',
                'slug' => 'nullable|unique:cloud_storage',
                'type' => 'required',
            ]);

            if ($validation->fails()) {
                $response = array();
                $response['flag'] = false;
                $response['message'] = "Failed.";
                $response['data'] = null;
                $response['error'] = $validation->errors();
                return response()->json($response);
            }

            $userId = $request->user_id ?? auth()->user()->id;
            $parent_id = null;
            if ($request->parent_slug) {
                $parent = CloudStorage::where('slug', $request->parent_slug)->first();
                if ($parent && $parent->id) {
                    $parent_id = $parent->id;
                }
            }
            if (!is_dir('storage/cloudstorage/' . $userId)) {
                File::makeDirectory(public_path('storage/cloudstorage/' . $userId));
            }

            $data = new CloudStorage();
            $data->name = $request->name;
            $slug = \Str::slug($request->name, "-");
            $slug = $slug . '-' . $userId;
            if ($parent_id != null) {
                $slug = $slug . '-' . $parent_id;
            }
            $checkSlug = CloudStorage::where('user_id', $userId)->where('slug', $slug)->first();
            if ($checkSlug && $checkSlug->id) {
                $slug = $slug . '-' . rand(0, 9) . '-' . rand(0, 99);
            }
            $data->slug = $slug;
            if ($parent_id != null) {
                $data->parent_id = $parent_id;
            }
            $data->user_id = $userId;
            if ($parent_id != null) {
                $parent_path = CloudStorage::where('id', $parent_id)->first();
                $data->path = $parent_path->path . '/' . $slug;
            } else {
                $data->path = 'storage/cloudstorage/' . $userId . '/' . $slug;
            }
            $data->type = $request->type;

            if ($parent_id) {
                $parentPath = CloudStorage::where('id', $parent_id)->where('user_id', $userId)->first();
                if (!is_dir($parentPath->path . '/' . $slug)) {
                    File::makeDirectory(public_path($parentPath->path . '/' . $slug));
                }
            } else if (!is_dir('storage/cloudstorage/' . $userId) . '/' . $slug) {
                if (!is_dir('storage/cloudstorage/' . $userId . '/' . $slug)) {
                    File::makeDirectory(public_path('storage/cloudstorage/' . $userId . '/' . $slug));
                }
            }
            $data->save();

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success.';
            $response['data'] = $data;
            return response()->json($response, 201);
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = null;
            return response()->json($response);
        }
    }

    public function folder_update(Request $request)
    {
        try {
            if (!is_dir('storage/cloudstorage')) {
                File::makeDirectory(public_path('storage/cloudstorage'));
            }

            $validation = Validator::make($request->all(), [
                'id' => 'required',
                'name' => 'required',
                'type' => 'required',
            ]);

            if ($validation->fails()) {
                $response = array();
                $response['flag'] = false;
                $response['message'] = "Failed.";
                $response['data'] = null;
                $response['error'] = $validation->errors();
                return response()->json($response);
            }

            $id = $request->id;
            $userId = $request->user_id ?? auth()->user()->id;
            $parent_id = null;
            if ($request->parent_id) {
                $parent = CloudStorage::where('id', $request->parent_id)->first();
                if ($parent && $parent->id) {
                    $parent_id = $parent->id;
                }
            }
            $data = CloudStorage::find($id);
            $data->name = $request->name;
            if ($parent_id) {
                $data->parent_id = $parent_id;
            }
            $data->roll_id = '';
            if ($parent_id) {
                $files = CloudStorage::where('parent_id', $data->id)->where('type', 'file')->get();
                $parentPath = CloudStorage::where('id', $parent_id)->first();

                $source = public_path($data->path);
                $destination = public_path($parentPath->path);
                shell_exec('mv ' . $source . ' ' . $destination . ' ');

                $data->path = $parentPath->path . '/' . $data->slug;
                if ($files && count($files) > 0) {
                    foreach ($files as $file) {
                        CloudStorage::where('id', $file->id)->update(['path' => $data->path . '/' . $file->file_name]);
                    }
                }
            } else if ($request->root == true) {
                $data->parent_id = null;
                if (!is_dir('storage/cloudstorage/' . $userId . '/' . $data->slug)) {
                    $files = CloudStorage::where('parent_id', $data->id)->where('type', 'file')->get();

                    $source = public_path($data->path);
                    $destination = public_path('storage/cloudstorage/' . $userId);
                    shell_exec('mv ' . $source . ' ' . $destination . ' ');
                    if ($files && count($files) > 0) {
                        foreach ($files as $file) {
                            CloudStorage::where('id', $file->id)->update(['path' => $destination . '/' . $file->file_name]);
                        }
                    }
                    $data->path = 'storage/cloudstorage/' . $userId . '/' . $data->slug;
                }
            }
            $data->type = $request->type;
            $data->save();

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success.';
            $response['data'] = $data;
            return response()->json($response);
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = null;
            return response()->json($response);
        }
    }

    public function softRecursionDelete($childrens)
    {
        foreach ($childrens as $key => $value) {
            if ($value && $value->id) {
                $data = CloudStorage::where('id', $value->id)->withTrashed()->first();
                if ($data && $data->id) {
                    $children = CloudStorage::where('parent_id', $value->id)->withTrashed()->get();
                    if ($children && count($children) > 0) {
                        $this->softRecursionDelete($children);
                    }
                    $data->delete();
                }
            }
        }
    }

    public function markFolderTrash($id)
    {
        try {
            $data = CloudStorage::where('id', $id)->first();
            if ($data && $data->id) {

                $children = CloudStorage::where('parent_id', $data->id)->withTrashed()->get();
                if ($children && count($children) > 0) {
                    $this->softRecursionDelete($children);
                }
                $data->delete();
            }

            $response = array();
            $response['flag'] = true;
            $response['status'] = 'Success.';
            $response['data'] = null;
            return response()->json($response);
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = null;
            return response()->json($response);
        }
    }

    public function forceRecursionDelete($childrens)
    {
        foreach ($childrens as $key => $value) {
            if ($value && $value->id) {
                $data = CloudStorage::where('id', $value->id)->withTrashed()->first();
                if ($data && $data->id) {
                    $children = CloudStorage::where('parent_id', $value->id)->withTrashed()->get();
                    if ($children && count($children) > 0) {
                        $this->forceRecursionDelete($children);
                    }

                    $folderPath = public_path($data->path);
                    $folderExists = File::deleteDirectory($folderPath);
                    $data->forceDelete();
                }
            }
        }
    }

    public function markFolderDelete($id)
    {
        try {
            $data = CloudStorage::where('id', $id)->onlyTrashed()->first();
            if ($data && $data->id) {
                $folderPath = public_path($data->path);
                $folderExists = File::deleteDirectory($folderPath);

                $children = CloudStorage::where('parent_id', $data->id)->withTrashed()->get();
                if ($children && count($children) > 0) {
                    $this->forceRecursionDelete($children);
                }
                $data->forceDelete();
            }

            $response = array();
            $response['flag'] = true;
            $response['status'] = 'Success.';
            $response['data'] = null;
            return response()->json($response);
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = null;
            return response()->json($response);
        }
    }

    public function get_files(Request $request)
    {
        try {
            $userId = $request->user_id ?? auth()->user()->id;

            $files = CloudStorage::where('type', 'file')->where('user_id', $userId)->get();
            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success.';
            $response['data'] = $files;
            return response()->json($response, 201);
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = null;
            return response()->json($response);
        }
    }

    public function get_file(Request $request, $id)
    {
        try {
            $userId = $request->user_id ?? auth()->user()->id;

            $file = CloudStorage::where('id', $id)->where('type', 'file')->where('user_id', $userId)->first();
            $response = array();
            $response['flag'] = true;
            $response['message'] = 'success';
            $response['data'] = $file;
            return response()->json($response);
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = null;
            return response()->json($response);
        }
    }

    public function file_create(Request $request)
    {
        try {
            if (!is_dir('storage/cloudstorage')) {
                File::makeDirectory(public_path('storage/cloudstorage'));
            }

            $validation = Validator::make($request->all(), [
                'attachments' => 'required',
                'type' => 'required',
            ]);

            if ($validation->fails()) {
                $response = array();
                $response['flag'] = false;
                $response['message'] = "Failed.";
                $response['data'] = null;
                $response['error'] = $validation->errors();
                return response()->json($response);
            }

            $userId = $request->user_id ?? auth()->user()->id;
            $parent_id = null;
            if ($request->parent_slug) {
                $parent = CloudStorage::where('slug', $request->parent_slug)->first();
                if ($parent && $parent->id) {
                    $parent_id = $parent->id;
                }
            }

            if (!is_dir('storage/cloudstorage/' . $userId)) {
                File::makeDirectory(public_path('storage/cloudstorage/' . $userId));
            }

            $files = [];
            if ($request->attachments) {
                foreach ($request->attachments as $key => $file) {
                    $filePath = 'storage/cloudstorage/' . $userId;
                    $attachment = $file['file'];
                    $extension = $file['extension'];
                    $img_code = explode(',', $attachment);
                    $filedata = base64_decode($img_code[1]);
                    if ($parent_id != null) {
                        $parentPath = CloudStorage::where('id', $parent_id)->first();
                        $filePath = $parentPath->path;
                    }
                    $f = finfo_open();
                    $mime_type = finfo_buffer($f, $filedata, FILEINFO_MIME_TYPE);

                    @$mime_type = explode('/', $mime_type);
                    @$mime_type = $extension ?? $mime_type[1];
                    if ($mime_type) {
                        $name = time() . '-' . rand(0000, 9999) . '.' . $mime_type;
                        if (file_put_contents(public_path() . '/' . $filePath . '/' . $name, $filedata)) {
                            $img_url = $filePath . '/' . $name;
                        }
                    }

                    $data = new CloudStorage;
                    $data->name = $file['name'];
                    $data->user_id = $userId;
                    $data->parent_id = $parent_id;
                    $data->roll_id = '';
                    $data->file_name = $name;
                    $data->extension = $file['extension'];
                    $data->path = $img_url;
                    $data->type = $request->type;
                    $data->save();

                    $allFiles = CloudStorage::where('id', $data->id)->first();

                    array_push($files, $allFiles);
                }
            }
            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success.';
            $response['data'] = $files;
            return response()->json($response);
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = null;
            return response()->json($response);
        }
    }

    public function file_update(Request $request)
    {
        try {
            if (!is_dir('storage/cloudstorage')) {
                File::makeDirectory(public_path('storage/cloudstorage'));
            }

            $validation = Validator::make($request->all(), [
                'id' => 'required',
                'name' => 'required',
                'type' => 'required',
            ]);

            if ($validation->fails()) {
                $response = array();
                $response['flag'] = false;
                $response['message'] = "Failed";
                $response['data'] = null;
                $response['error'] = $validation->errors();
                return response()->json($response);
            }

            $id = $request->id;
            $userId = $request->user_id ?? auth()->user()->id;
            $parent_id = null;
            if (!is_dir('storage/cloudstorage/' . $userId)) {
                File::makeDirectory(public_path('storage/cloudstorage/' . $userId));
            }

            if ($request->parent_id) {
                $parent = CloudStorage::where('id', $request->parent_id)->first();
                if ($parent && $parent->id) {
                    $parent_id = $parent->id;
                }
            }

            $data = CloudStorage::find($id);
            $data->name = $request->name;
            $data->roll_id = '';

            if ($data->file_name) {
                if ($parent_id != null) {
                    $parent_path = CloudStorage::where('id', $parent_id)->where('type', 'folder')->first();
                    File::move(public_path($data->path), public_path($parent_path->path . '/' . $data->file_name));
                    $data->parent_id = $parent_id;
                    $data->path = $parent_path->path . '/' . $data->file_name;
                } else if ($request->root == true) {
                    $data->parent_id = null;
                    $parent_path = public_path('storage/cloudstorage/' . $userId . '/' . $data->file_name);
                    File::move(public_path($data->path), $parent_path);
                    $data->path = 'storage/cloudstorage/' . $userId . '/' . $data->file_name;
                }
            }

            $data->type = $request->type;
            $data->save();

            $response['flag'] = true;
            $response['message'] = 'Success.';
            $response['data'] = $data;
            return response()->json($response);
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = null;
            return response()->json($response);
        }
    }

    public function markFileTrash($id)
    {
        try {
            CloudStorage::where('id', $id)->delete();
            $response = array();
            $response['flag'] = true;
            $response['message'] = 'success';
            $response['data'] = null;
            return response()->json($response);
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = null;
            return response()->json($response);
        }
    }

    public function markFileDelete($id)
    {
        try {
            $data = CloudStorage::where('id', $id)->onlyTrashed()->first();
            if ($data && $data->id) {
                $filePath = $data->path;
                $fileExists = file_exists($filePath);
                if ($fileExists) {
                    unlink($filePath);
                }
                $data->forceDelete();
            }

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success.';
            $response['data'] = null;
            return response()->json($response);
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = null;
            return response()->json($response);
        }
    }

    public function forceRecursionRestore($childrens)
    {
        foreach ($childrens as $key => $value) {
            if ($value && $value->id) {
                $data = CloudStorage::where('id', $value->id)->withTrashed()->first();
                if ($data && $data->id) {
                    if ($data && $data->type == "folder") {
                        $children = CloudStorage::where('parent_id', $value->id)->withTrashed()->get();
                        if ($children && count($children) > 0) {
                            $this->forceRecursionRestore($children);
                        }
                    }
                    $data->restore();
                }
            }
        }
    }

    public function markRestore(Request $request)
    {
        try {
            $validation = Validator::make($request->all(), [
                'id' => 'required',
            ]);

            if ($validation->fails()) {
                $response = array();
                $response['flag'] = false;
                $response['message'] = "Id is required.";
                $response['data'] = null;
                return response()->json($response);
            }

            $data = CloudStorage::where('id', $request->id)->onlyTrashed()->first();
            if ($data && $data->id) {
                if ($data && $data->type == "folder") {

                    $children = CloudStorage::where('parent_id', $data->id)->withTrashed()->get();
                    if ($children && count($children) > 0) {
                        $this->forceRecursionRestore($children);
                    }
                }
                $data->restore();
            }

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success.';
            $response['data'] = null;
            return response()->json($response);
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = null;
            return response()->json($response);
        }
    }

    public function markImportant(Request $request)
    {
        try {
            $validation = Validator::make($request->all(), [
                'id' => 'required',
            ]);

            if ($validation->fails()) {
                $response = array();
                $response['flag'] = false;
                $response['message'] = "Id is required.";
                $response['data'] = null;
                return response()->json($response);
            }

            $data = CloudStorage::where('id', $request->id)->first();
            if ($data->important_at == null) {
                $important = Carbon::now();
            } else {
                $important = null;
            }
            CloudStorage::where('id', $request->id)->update(['important_at' => $important]);

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success.';
            $response['data'] = null;
            return response()->json($response);
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = null;
            return response()->json($response);
        }
    }
}
