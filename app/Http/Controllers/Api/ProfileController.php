<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Role;
use App\Models\ContactImap;
use App\Models\AccountSetting;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    public function get_account_setting() {
        try {
            $account = AccountSetting::find(Auth::user()->id);
            if(!empty($account) && $account->footer_columns){
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
        } catch (\Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = 'Failed.';
            $response['data'] = null;
            return response()->json($response, $e);
        }
    }

    public function save_account(Request $request) {
        $id = Auth::user()->id;
        $validation = Validator::make($request->all(), [
            'name'     => 'required',
            'email'    => 'required',
        ]);

        if($validation->fails()){
          $error=$validation->errors();
          return response()->json(['error' => $error]);
        }

        $validationEmail = Validator::make($request->all(), [
            'email'    => 'nullable|unique:users,email,'.$id,
        ]);

        if($validationEmail->fails()){
            $response['flag'] = false;
            $response['message'] = "This Email address is already used.";
            $response['data'] = null;
            return response()->json($response);
        }

        $param = $request->all();

        if ($request->image) {
            $img_code = explode(',', $request->image);
            $filedata = base64_decode($img_code[1]);
            $filePath = 'public/images/avatars';
            $f = finfo_open();
            $mime_type = finfo_buffer($f, $filedata, FILEINFO_MIME_TYPE);

            @$mime_type = explode('/', $mime_type);
            @$mime_type = $mime_type[1];
            if ($mime_type) {
                \Storage::makeDirectory($filePath);
                $filename = time() . '-' . rand(0000, 9999) . '.' . $mime_type;
                if (\Storage::put($filePath.'/'.$filename, $filedata)) {
                    $img_url = 'storage/images/avatars/'.$filename;
                    unset($param['image']);
                    $param['profile_photo_path'] = $img_url;
                }
            }
        }

        $is_save=User::where('id',$id)->update($param);
        $user = User::with('role')->where('id',$id)->first();
        $account = AccountSetting::find($id);
        $roles = Role::where('IsActive', 1)->get();
        $imap = auth()->user()->imap;
        if($is_save){
            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Account saved successfully.';
            $response['data'] = [   'userData' => $user,
                                    'account' => $account,
                                    'imap' => $imap,
                                    'roles' => $roles ];
            return response()->json($response);
        }else{
            $response = array();
            $response['flag'] = false;
            $response['message'] = 'Failed.';
            $response['data'] = null;
            return response()->json($response);
        }
    }

    public function save_account_setting(Request $request) {
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
                        if (\Storage::put($filePath.'/'.$filename, $filedata)) {
                            $img_url = 'storage/images/logo/'.$filename;
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
                    ['id'=> Auth::user()->id],
                    [
                        'UserId'=>Auth::user()->id,
                        'Account_Name'=>'N\A',
                        'Account_title'=>'N\A',
                        'Account_Number'=>'N\A',
                        'invoice_logo' => $img_url ,
                        'bank_information' => $request->bank_information,
                        'footer_columns' => json_encode($footerColumns),
                        'User_Name'=>$request->User_Name,
                        'Address'=>$request->Address,
                        'Postal_Code'=>$request->Postal_Code,
                        'City'=>$request->City,
                        'Casetype'=>$request->Casetype,
                        'Invoice_text'=>$request->Invoice_text,
                        'language'=>$request->language ?? "English"
                    ]
                );
                $account = AccountSetting::where('id',$data->id)->first();
                $user = User::with('role')->where('id',$data->UserId)->first();
                $imap = auth()->user()->imap;
                $roles = Role::where('IsActive', 1)->get();
                $response = array();
                $response['flag'] = true;
                $response['message'] = 'Success.';
                $response['data'] = [   'userData' => $user,
                                        'account' => $account,
                                        'imap' => $imap,
                                        'roles' => $roles];
                return response()->json($response);
        }
        catch (\Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = 'Failed.';
            $response['data'] = null;
            return response()->json($response);
        }
    }

    public function save_account_imap(Request $request) {
        try {
            $validation = Validator::make($request->all(), [
                'UserID'     => 'required',

            ]);

            if($validation->fails()){
                $response = array();
                $response['flag'] = false;
                $response['message'] = $validation->errors();
                return response()->json(['error' => $response]);
            }

            $ctimap = ContactImap::where('user_id',$request->UserID)->first() ?? new ContactImap;

            $ctimap->user_id = $request->UserID;
            $ctimap->imap_host = $request->imap_host;
            $ctimap->imap_email = $request->imap_email;
            $ctimap->imap_password = $request->imap_password;
            $ctimap->imap_port = $request->imap_port;
            $ctimap->imap_ssl = $request->imap_ssl == true ? 1 : 0;
            $ctimap->save();

            $account = AccountSetting::where('UserID',$request->UserID)->first();
            $user = User::with('role')->where('id',$request->UserID)->first();
            $roles = Role::where('IsActive', 1)->get();
            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Success.';
            $response['data'] = [   'imap' => $ctimap,
                                    'userData' => $user,
                                    'account' => $account,
                                    'roles' => $roles ];


            return response()->json($response);
        }
        catch (\Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = 'Failed.';
            $response['data'] = null;
            return response()->json($response);
        }
    }
}
