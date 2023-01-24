<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AccountSetting;
use App\Models\ContactImap;
use App\Models\Permissions;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Storage;

class ProfileController extends Controller
{
    public function get_account_setting()
    {
        try {
            $account = AccountSetting::find(Auth::user()->id);
            if (!empty($account) && $account->footer_columns) {
                $account['footer_columns'] = json_decode($account->footer_columns);
            }
            $user = User::with('role')->find(Auth::user()->id);
            $roles = Role::where('IsActive', 1)->get();
            $imap = auth()->user()->imap;
            $currentText = '<p><strong>Dr. iur. Thomas Schulte </strong></p><p>Rechtsanwalt - </p><p>Bankkaufmann (IHK)</p><p><br></p><p><br></p><p>Sitz der Kanzlei</p><p>Malteserstraße 170-171</p><p>12277 Berlin</p><p><br></p><p><br></p><p><strong>Bearbeiter:</strong></p><p><strong>RA Dr. Thomas Schulte</strong></p><p><br></p><p>dr.schulte@dr-schulte.de</p><p>Telefon:&nbsp;<a href="https://www.drthomasschulte.de/tel+4930221922020" rel="noopener noreferrer" target="_blank">030 – 22 19 220 20</a></p><p> Fax: 030 – 22 19 220 21</p><p><br></p><p>Steuernummer:</p><p>21/523/00356</p><p><br></p><p>Konten</p><p><br></p><p>Postbank Geschäftskunden</p><p>Dr. Thomas Schulte</p><p><br></p><p>Geschäftskonto:</p><p>IBAN DE 12 1001 00100929 4261 09</p><p><br></p><p>Anderkonto:</p><p>IBAN DE 86 1001 0010 0929 7181 05</p><p><br></p>';
            $account['defaultText'] = $currentText;
            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success.';
            $response['data'] = [
                'userData' => $user,
                'roles' => $roles,
                'account' => $account,
                'imap' => $imap,
            ];
            return response()->json($response);
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = 'Failed.';
            $response['data'] = null;
            return response()->json($response, $e);
        }
    }

    public function updateAdminPermission($userId)
    {
        try {
            if ($userId) {
                $user = User::with('role')->where('id', $userId)->first();
                if ($user && $user->role_id == 10) {
                    $permissions = array(
                        ['permission_id' => 1],
                        ['permission_id' => 2],
                        ['permission_id' => 3],
                        ['permission_id' => 4],
                        ['permission_id' => 5],
                        ['permission_id' => 6],
                        ['permission_id' => 7],
                        ['permission_id' => 8],
                        ['permission_id' => 9],
                        ['permission_id' => 10],
                        ['permission_id' => 11],
                        ['permission_id' => 12],
                        ['permission_id' => 13],
                        ['permission_id' => 14],
                        ['permission_id' => 15],
                        ['permission_id' => 16],
                        ['permission_id' => 17],
                    );

                    if ($permissions && count($permissions) > 0) {
                        Permissions::where('user_id', $userId)->delete();
                        foreach ($permissions as $key => $val) {
                            Permissions::insert(['user_id' => $userId, 'permission_id' => $val['permission_id']]);
                        }
                        return true;
                    }
                }
                return false;
            }
            return false;
        } catch (\Exception$error) {
            return false;
        }
    }

    public function save_account(Request $request)
    {
        try {
            $id = Auth::user()->id;
            $preLanguage = Auth::user()->language;

            $validation = Validator::make($request->all(), [
                'first_name' => 'required',
                'email' => 'required',
            ]);

            if ($validation->fails()) {
                $response['flag'] = false;
                $response['message'] = "Validation failed!";
                $response['data'] = null;
                $response['error'] = $validation->errors();
                return response()->json($response);
            }

            $validationEmail = Validator::make($request->all(), [
                'email' => 'nullable|unique:users,email,' . $id,
            ]);

            if ($validationEmail->fails()) {
                $response['flag'] = false;
                $response['message'] = "This Email address is already used.";
                $response['data'] = null;
                return response()->json($response);
            }

            $param = $request->all();

            $fullName = "";
            if ($request && $request->first_name) {
                $fullName = $request->first_name;
            }

            if ($request && $request->last_name) {
                $fullName = $fullName ? $fullName . ' ' . $request->last_name : $request->last_name;
            }
            $param['name'] = $fullName;

            if ($request->image) {
                $img_code = explode(',', $request->image);
                $filedata = base64_decode($img_code[1]);
                $filePath = 'uploads/images/avatars';
                $f = finfo_open();
                $mime_type = finfo_buffer($f, $filedata, FILEINFO_MIME_TYPE);

                @$mime_type = explode('/', $mime_type);
                @$mime_type = $mime_type[1];
                if ($mime_type) {
                    if (Storage::exists($filePath)) {
                        Storage::makeDirectory($filePath);
                    }

                    $filename = time() . '-' . rand(0000, 9999) . '.' . $mime_type;
                    if (Storage::put($filePath . '/' . $filename, $filedata)) {
                        unset($param['image']);
                        $param['profile_photo_path'] = $filePath . '/' . $filename;
                    }
                }
            }

            $is_save = User::where('id', $id)->update($param);
            $user = User::with('role', 'permission')->where('id', $id)->first();
            if ($user && $user->id) {
                if ($user->role_id == 10) {
                    $this->updateAdminPermission($user->id);
                }
            }

            $user = User::with('role', 'permission')->where('id', $id)->first();
            $account = AccountSetting::find($id);
            $roles = Role::where('IsActive', 1)->get();
            $imap = auth()->user()->imap;

            if ($is_save) {
                // default language is changed, set it to all account
                if ($preLanguage != $user->language) {
                    DB::statement("UPDATE users SET language = '{$user->language}'");
                    DB::statement("DELETE FROM personal_access_tokens WHERE tokenable_id <> {$user->id}");
                }

                $response = array();
                $response['flag'] = true;
                $response['message'] = 'Account saved successfully.';
                $response['data'] = ['userData' => $user,
                    'account' => $account,
                    'imap' => $imap,
                    'languageChanged' => $preLanguage != $user->language,
                    'roles' => $roles];
                return response()->json($response);
            } else {
                $response = array();
                $response['flag'] = false;
                $response['message'] = 'Failed.';
                $response['data'] = null;
                return response()->json($response);
            }
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = null;
            return response()->json($response);
        }
    }

    public function update_account_profile(Request $request)
    {
        try {
            $id = auth()->user()->id;

            $validation = Validator::make($request->all(), [
                'image' => 'required',
            ]);

            if ($validation->fails()) {
                $response['flag'] = false;
                $response['message'] = "Image is required!";
                $response['data'] = null;
                $response['error'] = $validation->errors();
                return response()->json($response);
            }

            $user = User::with('role', 'permission')->where('id', $id)->first();

            $param = $request->all();
            if ($request->image) {
                $img_code = explode(',', $request->image);
                $filedata = base64_decode($img_code[1]);
                $filePath = 'uploads/images/avatars';
                $f = finfo_open();
                $mime_type = finfo_buffer($f, $filedata, FILEINFO_MIME_TYPE);

                @$mime_type = explode('/', $mime_type);
                @$mime_type = $mime_type[1];
                if ($mime_type) {
                    if (Storage::exists($filePath)) {
                        Storage::makeDirectory($filePath);
                    }

                    $filename = time() . '-' . rand(0000, 9999) . '.' . $mime_type;
                    if (Storage::put($filePath . '/' . $filename, $filedata)) {
                        if ($user && $user->profile_photo_path) {
                            if (Storage::exists($user->profile_photo_path)) {
                                Storage::delete($user->profile_photo_path);
                            }
                        }
                        unset($param['image']);
                        $param['profile_photo_path'] = $filePath . '/' . $filename;
                    }
                }
            }

            User::where('id', $id)->update($param);
            $user = User::with('role', 'permission')->where('id', $id)->first();

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Profile image updated!';
            $response['data'] = $user;
            return response()->json($response);

        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = null;
            return response()->json($response);
        }
    }

    public function save_account_setting(Request $request)
    {
        try {
            $img_url = '';
            if ($request->invoice_logo) {
                $img_code = explode(',', $request->invoice_logo);
                $filedata = base64_decode($img_code[1]);
                $filePath = 'public/images/logo';
                $f = finfo_open();
                $mime_type = finfo_buffer($f, $filedata, FILEINFO_MIME_TYPE);

                @$mime_type = explode('/', $mime_type);
                @$mime_type = $mime_type[1];
                if ($mime_type) {
                    \Storage::makeDirectory($filePath);
                    $filename = time() . '-' . rand(0000, 9999) . '.' . $mime_type;
                    if (\Storage::put($filePath . '/' . $filename, $filedata)) {
                        $img_url = 'storage/images/logo/' . $filename;
                    }
                }
            }

            $footerColumns = [
                'footer_column_1' => $request->footer_column_1,
                'footer_column_2' => $request->footer_column_2,
                'footer_column_3' => $request->footer_column_3,
                'footer_column_4' => $request->footer_column_4,
            ];

            $data = AccountSetting::updateOrCreate(
                ['id' => Auth::user()->id],
                [
                    'UserId' => Auth::user()->id,
                    'Account_Name' => 'N\A',
                    'Account_title' => 'N\A',
                    'Account_Number' => 'N\A',
                    'invoice_logo' => $img_url,
                    'bank_information' => $request->bank_information,
                    'footer_columns' => json_encode($footerColumns),
                    'User_Name' => $request->User_Name,
                    'Address' => $request->Address,
                    'Postal_Code' => $request->Postal_Code,
                    'City' => $request->City,
                    'Casetype' => $request->Casetype,
                    'Invoice_text' => $request->Invoice_text,
                    'language' => $request->language ?? "English",
                ]
            );
            $account = AccountSetting::where('id', $data->id)->first();
            $user = User::with('role')->where('id', $data->UserId)->first();
            $imap = auth()->user()->imap;
            $roles = Role::where('IsActive', 1)->get();
            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success.';
            $response['data'] = ['userData' => $user,
                'account' => $account,
                'imap' => $imap,
                'roles' => $roles];
            return response()->json($response);
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = null;
            return response()->json($response);
        }
    }

    public function save_account_imap(Request $request)
    {
        try {
            $validation = Validator::make($request->all(), [
                'UserID' => 'required',

            ]);

            if ($validation->fails()) {
                $response = array();
                $response['flag'] = false;
                $response['message'] = $validation->errors();
                return response()->json(['error' => $response]);
            }

            $ctimap = ContactImap::where('user_id', $request->UserID)->first() ?? new ContactImap;

            $ctimap->user_id = $request->UserID;
            $ctimap->imap_host = $request->imap_host;
            $ctimap->imap_email = $request->imap_email;
            $ctimap->imap_password = $request->imap_password;
            $ctimap->imap_port = $request->imap_port;
            $ctimap->imap_ssl = $request->imap_ssl == true ? 1 : 0;
            $ctimap->save();

            $account = AccountSetting::where('UserID', $request->UserID)->first();
            $user = User::with('role')->where('id', $request->UserID)->first();
            $roles = Role::where('IsActive', 1)->get();
            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success.';
            $response['data'] = ['imap' => $ctimap,
                'userData' => $user,
                'account' => $account,
                'roles' => $roles];

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
