<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LanguageLabel;
use App\Models\Permissions;
use App\Models\PersonalAccessToken;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Validator;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public $CRYPTO_KEY = "";
    public $CRYPTO_IV = "";

    public function __construct()
    {
        $this->middleware('auth:sanctum', ['except' => ['login', 'register']]);
        $this->CRYPTO_KEY = hex2bin(env('CRYPTO_KEY'));
        $this->CRYPTO_IV = hex2bin(env('CRYPTO_IV'));
    }
    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        try {
            $UserArray = $request->all();
            if ($request->password) {
                $UserArray['password'] = $this->decryptData($request->password);
            }

            $validator = Validator::make($UserArray, [
                'email' => 'required|email',
                'password' => 'required|string|min:6',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 422);
            }

            if (!$token = auth()->attempt($validator->validated())) {
                return response()->json(['flag' => false, 'message' => 'Invalid username or password'], 201);
            }
            return $this->createNewToken('token');
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = null;
            return response()->json($response);
        }
    }
    /**
     * Register a User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|between:2,100',
                'email' => 'required|string|email|max:100|unique:users',
                'password' => 'required|string|confirmed|min:6',
            ]);
            if ($validator->fails()) {
                return response()->json($validator->errors()->toJson(), 400);
            }
            $user = User::create(array_merge(
                $validator->validated(),
                ['password' => bcrypt($request->password)]
            ));
            return response()->json([
                'message' => 'User successfully registered!',
                'user' => $user,
            ], 201);
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = null;
            return response()->json($response);
        }
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        // Get user who requested the logout
        $user = Auth::user(); //or Auth::user()
        // Revoke current user token
        $user->tokens()->where('id', $user->currentAccessToken()->id)->delete();
        return response()->json(['flag' => true, 'message' => 'User successfully signed out!'], 201);
    }
    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh(Request $request)
    {
        $bearerToken = $request->header('Authorization');
        $bearerToken = str_replace("Bearer ", "", $bearerToken);
        [$id, $token] = explode('|', $bearerToken, 2);
        $accessToken = PersonalAccessToken::find($id);

        if (hash_equals($accessToken->token, hash('sha256', $token))) {
            $user = user::find($accessToken->tokenable_id);
            $request->user()->currentAccessToken()->delete();
            $token = $user->createToken('am-backend')->plainTextToken;

            foreach ($user->getAttributes() as $key => $value) {
                $success['user'][$key] = $value;
            }

            $success['token'] = $token;

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Refresh token generated';
            $response['data'] = ['userData' => $user, 'accessToken' => $token, 'tokenType' => 'bearer', 'expiresIn' => 86400 * 7];
            return response()->json($response, 201);
        } else {
            return response()->json(['flag' => false, "message" => "Unauthenticated!"]);
        }
    }
    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function userProfile()
    {
        return response()->json(auth()->user());
    }
    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function createNewToken($token)
    {
        $user = Auth::user();
        $user['role'] = Role::where('role_id', Auth::user()->role_id)->first();
        $user['permission'] = Permissions::where('user_id', Auth::user()->id)->get();
        \DB::table('personal_access_tokens')->where('tokenable_id', $user->id)->delete();
        $access_token = $user->createToken('MyApp')->plainTextToken;
        [$id, $token] = explode('|', $access_token, 2);
        \DB::table('personal_access_tokens')->where('id', $id)->update(array('expires_at' => Carbon::now()->addDays()));

        $languageLabels = array();

        $labels = LanguageLabel::select('translation', 'language')->get();
        foreach ($labels as $label) {
            $languageLabels[$label->language] = json_decode($label->translation);
        }

        $response = array();
        $response['flag'] = true;
        $response['message'] = 'User logged successfully!';
        $response['data'] = ['userData' => $user, 'accessToken' => $access_token, 'tokenType' => 'bearer', 'languageLabels' => $languageLabels, 'expiresIn' => 86400];
        return response()->json($response);
    }

    public function changeAuthUserPassword(Request $request)
    {
        try {
            $validation = Validator::make($request->all(), [
                'password' => 'required|string|min:6',
                'newPassword' => 'required|string|min:6',
            ]);

            if ($validation->fails()) {
                $response = array();
                $response['flag'] = false;
                $response['message'] = "Failed!";
                $response['data'] = null;
                $response['error'] = $validation->errors();
                return response()->json($response);
            }

            $id = auth()->user()->id;
            $user = User::where('id', $id)->first();

            $passwordData = $request->all();
            if ($request->password) {
                $passwordData['password'] = $this->decryptData($request->password);
            }

            if ($request->newPassword) {
                $passwordData['newPassword'] = $this->decryptData($request->newPassword);
            }

            if (!Hash::check($passwordData['password'], $user->password)) {
                $response = array();
                $response['flag'] = false;
                $response['message'] = 'Current password not matched!';
                $response['data'] = null;
                return response()->json($response);
            }

            $newPassword = Hash::make($passwordData['newPassword']);
            $user->update(['password' => $newPassword]);

            $response = array();
            $response['flag'] = true;
            $response['message'] = 'Password updated successfully!';
            $response['data'] = [];
            return response()->json($response);
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = null;
            return response()->json($response);
        }
    }

    public function decryptData($string = "")
    {
        try {
            $decrypted = openssl_decrypt($string, 'AES-128-CBC', $this->CRYPTO_KEY, OPENSSL_ZERO_PADDING, $this->CRYPTO_IV);
            return trim($decrypted);
        } catch (\Exception$e) {
            return $string;
        }
    }
}
