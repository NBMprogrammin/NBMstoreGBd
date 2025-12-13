<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Cookie;
use App\Models\userPasswordStting;
use App\Models\UserCategory;
use Carbon\Carbon;
use App\Models\User;
use App\Models\ProdectUser;
use App\Models\ZebouneForUser;
use App\Models\ProfileUserBss;
use App\Models\ProfileUser;
use App\Models\PaymentMethodUserBss;
use App\Models\CurrentPaymentForUseBss;
use App\Models\MessageEghar;
use App\Models\PaymentProdectUserBss;
use App\Models\MyOrdersPayBss;
use App\Models\EdaretMewevin;
use App\Models\EdartManey;
use App\Models\EdartPaymentRwatibeMeweves;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Notifications\PasswordResetCodeNotification;
use App\Services\NewAccounteUserConfirmed;
use App\Services\MessageToShangeEmailProfile;
use App\Services\HandleConfirmedShangeEmailProfile;
use App\Models\PasswordResetCode;
use App\Notifications\NewPasswordNotification;
use Illuminate\Support\Facades\Mail;

class FrountEndController extends Controller
{

    // Start Actions Login And Register New User //    
    // // Start function Login User //
    public function loginUser(Request $request) 
    {
        try {
            $request->validate([
                'email' => 'required|string',
                'password' => 'required|string',
            ]);
            // $address

            // تنظيف البيانات
            $SmplDataLogin = strip_tags($request->email);
            $SmplPassword = strip_tags($request->password);
            
            // تحديد نوع البيانات (بريد إلكتروني أو هاتف)
            $loginType = filter_var($SmplDataLogin, FILTER_VALIDATE_EMAIL) ? 'email' : 'NumberPhone';

            // البحث عن المستخدم مع العلاقات المطلوبة مسبقاً
            $user = User::with(['ProfileUser', 'ZebouneForUser', 'MyOrdersPayBss'])
                ->where($loginType, $SmplDataLogin)
                ->first();

            // التحقق من وجود المستخدم وكلمة المرور
            if (!$user || !Hash::check($SmplPassword, $user->password)) {
                return response()->json([
                    'message' => 'Email Or Password Is Not Found',
                    'data' => [],
                    'typAction' => 2,
                ], 200);
            }

            // حذف أي tokens موجودة للمستخدم
            $user->tokens()->delete();

            // إنشاء token جديد
            $token = $user->createToken('user_token')->plainTextToken;

            // تحديث بيانات المستخدم
            $user->update([
                'curret_profile_id' => $user->id,
                'curret_profile_id_Bss' => '',
                'current_my_travel' => ''
            ]);

            // جلب جميع بيانات المستخدم باستخدام الدالة المحسنة
            $AllsDataProfileNow = $this->StartGetAllsDataProfileSmpl($user)->getData();

            return response()->json([
                'message' => 'successfuly Login For Your Accounte',
                'data' => $AllsDataProfileNow, // الحصول على البيانات من ال response
                // 'data' => $AllsDataProfileNow, // الحصول على البيانات من ال response
                'typAction' => 1,
                'token' => $token
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'حدث خطأ في النظام',
                'error' => $e->getMessage(),
                'typAction' => 2,
            ], 500);
        }
    } //=== Start function Login User  ===//
    
    // Start function register User
    public function registerUser(Request $request) {
        $request->validate([
            'email' => 'required|email',
        ]);
        $SmplEmail = strip_tags($request->email);
        $user = User::where('email', $SmplEmail)->exists();
        
        if ($user) {
            return response()->json([
                'message' => 'البريد الإلكتروني صحيح و غير مسجل',
                'data' => [],
                'typAction' => 2,
            ], 200);
        }

        $request->validate([
            'phone' => 'required|string',
        ]);
        
        $Smplphone = strip_tags($request->phone);
        $SheckPhone = User::where('NumberPhone', $Smplphone)->exists();
        if ($SheckPhone) {
            return response()->json([
                'message' => 'البريد الإلكتروني صحيح و غير مسجل',
                'data' => [],
                'typAction' => 3,
            ], 200);
        }
        $request->validate([
            'firstName' => 'required|string',
            'city' => 'required|string',
            'country' => 'required|string',
            'confirmPassword' => 'required|string',
            'dialCode' => 'required|string',
            'typeGender' => 'required|string',
            'datatime' => 'required|string',
            'code' => 'required|string',
            'profileImage' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:3050',
        ]);
        $SmpldialCode = strip_tags($request->dialCode);
        $Smplcode = strip_tags($request->code);
        $spmlGender = strip_tags($request->typeGender);
        $SkeclCodeConfirmedEmail = PasswordResetCode::where('email', $SmplEmail)
        ->where('code', $Smplcode)
        ->where('created_at', '>=', now()->subMinutes(30))
        ->exists();
        if (!$SkeclCodeConfirmedEmail) {
            return response()->json([
                'message' => 'البريد الإلكتروني صحيح و غير مسجل',
                'data' => [],
                'typAction' => 4,
            ], 200);
        }

        $username = strip_tags($request->firstName);
        $Smpladdress = strip_tags($request->address);
        $Smplpassword = strip_tags($request->confirmPassword);
        $Smplcity = strip_tags($request->city);
        $Smplcountry = strip_tags($request->country);
        $SmplDaOfBrith = strip_tags($request->datatime);


        $user = User::create([
            'username' => $username,
            'email' => $SmplEmail,
            'NumberPhone' => $Smplphone,
            'country_code' => $SmpldialCode,
            'password' => Hash::make($Smplpassword),
            'email_verified_at' => true,
        ]);

        $dataupdatProfile = [
            'email' => $SmplEmail,
            'cantry' => $Smplcountry,
            'NumberPhone' => $Smplphone,
            'address' => $Smpladdress,
            'user_id' => $user->id,
            'name' => $username,
            'city' => $Smplcity,
            'Gender' => $spmlGender,
            'data_of_birth' => $SmplDaOfBrith,
        ];

        if($request->hasFile('profileImage')) {
            $image = $request->file('profileImage');
            $gen = hexdec(uniqid());
            $ext = strtolower($image->getClientOriginalExtension());
            $namePod = $gen . '' . $ext;
            $location = 'user-profile/';
            $source = $location.$namePod;
            $name = $gen. '.' .$ext;
            $source = $location.$name;
            $image->move($location,$name);
            $dataupdatProfile['image'] = $source;
        }

        $ConfmedProfile = ProfileUser::create($dataupdatProfile);
        $user->update(['curret_profile_id' => $user->id]);
        
        PasswordResetCode::where('email', $SmplEmail)->delete();
        if($ConfmedProfile && $user) {
            $token = $user->createToken('user_token')->plainTextToken;
            // تحديث بيانات المستخدم
            $user->update([
                'curret_profile_id' => $user->id,
                'curret_profile_id_Bss' => '',
                'current_my_travel' => ''
            ]);

            // جلب جميع بيانات المستخدم باستخدام الدالة المحسنة
            $AllsDataProfileNow = $this->StartGetAllsDataProfileSmpl($user);
            return response()->json([
                'message' => 'البريد الإلكتروني صحيح و غير مسجل',
                'data' => $AllsDataProfileNow->getData(),
                'token' => $token,
                'typAction' => 1,
            ], 200);
        } else {
            return response()->json([
                'message' => 'Sorry Samthing Has One Error After Create Nwe Acounte',
                'data' => [],
                'typAction' => 6,
            ], 200);
        }

    } //=== Start function register User ===//
    //=== Start Actions Login And Register New User ===//

    // Start Show Date Profile User
    function ProfileUserBess(Request $request) {
        $MyProfileNow = Auth::user();
        $idProfileBssNow = $MyProfileNow->curret_profile_id_Bss;
        $ProfileBssLoginNow = Auth::user()->ProfileUserBss()->where('id', $idProfileBssNow)->first();
        
        if($ProfileBssLoginNow) {
            $request->validate([
                'usernameBss' => 'nullable|string',
                'megaleBss' => 'nullable|string',
                'gbsbss' => 'nullable|string',
                'cantryBss' => 'nullable|string',

            ]);

            $user_id = $MyProfileNow->id;
            $facebookLinck = strip_tags($request->facebookLinck);
            $tiktokeLinck = strip_tags($request->tiktokeLinck);
            $snabeshateLinck = strip_tags($request->snabeshateLinck);
            $youtubeLinck = strip_tags($request->youtubeLinck);
            $tewayteXLinck = strip_tags($request->tewayteXLinck);
            $instagramLinck = strip_tags($request->instagramLinck);
            $gbsbssLinck = strip_tags($request->gbsbssLinck);
            $DatToUpdateProf = [];
            $usernameBss = strip_tags($request->usernameBss);
            $megaleBss = strip_tags($request->megaleBss);
            $gbsbss = strip_tags($request->gbsbss);
            $cantryBss = strip_tags($request->cantryBss);
            // $SheckNameBss = ProfileUserBss::where('usernameBss', $usernameBss)->first();
            // if($SheckNameBss) {
            //     return response()->json([
            //         'message' => 'This Name Besness Has One Releay Created For One User',
            //         'data' => 7
            //     ]);
            // }
            if($usernameBss != '') {
                $DatToUpdateProf['usernameBss'] = $usernameBss;
            } 
            if($megaleBss != '') {
                $DatToUpdateProf['megaleBss'] = $megaleBss;
            } 
            if($gbsbss != '') {
                $DatToUpdateProf['gbsbss'] = $gbsbss;
            }
            if($cantryBss != '') {
                $DatToUpdateProf['cantryBss'] = $cantryBss;
            }
            
            $DAtaProfileUpd = $ProfileBssLoginNow->update($DatToUpdateProf);
            if($DAtaProfileUpd) {
                return response()->json([
                    'message' => 'Data To UPDATE Profile User Bss ',
                    'data' => 1,
                ]);
            } else {
                return response()->json([
                    'message' => 'Data To UPDATE Profile User Bss ',
                    'data' => 2,
                ]);
            }

        }

    } //=== End Show Date Profile User ===//


    protected $NewAccounteUserConfirmed;

    public function __construct(NewAccounteUserConfirmed $NewAccounteUserConfirmed)
    {
        $this->NewAccounteUserConfirmed = $NewAccounteUserConfirmed;
    }

    // إرسال رمز التأكيد
    public function sendcodtocreatenewaccounte(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|string',
                'phone' => 'required|string',
            ]);

            $SmplEmail = strip_tags($request->email);
            $user = User::where('email', $SmplEmail)->first();
            
            if ($user) {
                return response()->json([
                    'message' => 'البريد الإلكتروني صحيح و غير مسجل',
                    'data' => [],
                    'typAction' => 2,
                ], 200);
            }
            
            $Smplphone = strip_tags($request->phone);
            $SheckPhone = User::where('NumberPhone', $Smplphone)->select('id')->first();
            if ($SheckPhone) {
                return response()->json([
                    'message' => 'البريد الإلكتروني صحيح و غير مسجل',
                    'data' => [],
                    'typAction' => 3,
                ], 200);
            }
            PasswordResetCode::where('email', $SmplEmail)->delete();
            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            PasswordResetCode::create([
                'email' => $SmplEmail,
                'code' => $code,
                'created_at' => now()
            ]);
            $result = $this->NewAccounteUserConfirmed->sendVerificationCode($SmplEmail, $code);

            if($result) {
                return response()->json([
                    'message' => 'تم إرسال رمز التأكيد إلى بريدك الإلكتروني',
                    'data' => [],
                    'typAction' => 1,
                ], 200);
            } else {
                return response()->json([
                    'message' => 'تم إرسال رمز التأكيد إلى بريدك الإلكتروني',
                    'data' => [],
                    'typAction' => 5,
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error Send Message',
                'error' => $e->getMessage(),
                'data' => [],
                'typAction' => 7,
            ], 200);
        }
    }
    
    // إرسال رمز التأكيد
    public function sendResetCode(Request $request)
    {
        $request->validate([
            'email' => 'required|string',
        ]);
        try {

            $SmplEmail = strip_tags($request->email);
            $user = User::where('email', $SmplEmail)->first();

            if (!$user) {
                return response()->json([
                    'message' => 'البريد الإلكتروني غير صحيح أو غير مسجل',
                    'data' => [],
                    'typAction' => 2,
                    'user' => $user
                ], 200);
            }

            // إنشاء وإرسال رمز التأكيد
            $code = PasswordResetCode::generateCode($SmplEmail);
            
            // إرسال البريد الإلكتروني
            $user->notify(new PasswordResetCodeNotification($code));

            return response()->json([
                'message' => 'تم إرسال رمز التأكيد إلى بريدك الإلكتروني',
                'data' => [],
                'typAction' => 1,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error Send Message',
                'error' => $e->getMessage(),
                'data' => [],
                'typAction' => 7,
            ], 200);
        }
    }

    // التحقق من رمز التأكيد وإنشاء كلمة مرور جديدة
    public function verifyResetCode(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|string',
                'code' => 'required|digits:6'
            ]);

            $SmplEmail = strip_tags($request->email);
            $Smplcode = strip_tags($request->code);
            $user = User::where('email', $SmplEmail)->first();

            if (!$user) {
                return response()->json([
                    'message' => 'البريد الإلكتروني غير صحيح أو غير مسجل',
                    'data' => [],
                    'typAction' => 2,
                ], 200);
            }

            // التحقق من صحة الرمز
            if (!PasswordResetCode::isValidCode($SmplEmail, $Smplcode)) {
                return response()->json([
                    'message' => 'الرمز غير صحيح أو منتهي الصلاحية',
                    'data' => [],
                    'typAction' => 3,
                ], 200);
            }

            // إنشاء كلمة مرور قوية
            $newPassword = $this->generateStrongPassword();
            
            // البحث عن المستخدم وتحديث كلمة المرور
            $user = User::where('email', $SmplEmail)->first();
            $user->password = Hash::make($newPassword);
            $user->save();

            // إرسال كلمة المرور الجديدة بالبريد الإلكتروني
            $user->notify(new NewPasswordNotification($newPassword));

            // حذف الرمز بعد استخدامه
            PasswordResetCode::deleteCode($SmplEmail, $Smplcode);

            // تحديث بيانات المستخدم
            $user->update([
                'curret_profile_id' => $user->id,
                'curret_profile_id_Bss' => '',
                'current_my_travel' => ''
            ]);

            // جلب جميع بيانات المستخدم باستخدام الدالة المحسنة
            $AllsDataProfileNow = $this->StartGetAllsDataProfileSmpl($user);
            $token = $user->createToken('user_token')->plainTextToken;

            return response()->json([
                'message' => 'Succefuly Login Accounte',
                'data' => $AllsDataProfileNow->getData(),
                'token' => $token,
                'typAction' => 1,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error Send Message',
                'error' => $e->getMessage(),
                'data' => [],
                'typAction' => 7,
            ], 200);
        }
    }

    // دالة مساعدة لإنشاء كلمة مرور قوية
    private function generateStrongPassword($length = 12)
    {
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $symbols = '!@#$%^&*()_+-=[]{}|;:,.<>?';
        
        $password = '';
        $password .= $uppercase[rand(0, strlen($uppercase) - 1)];
        $password .= $lowercase[rand(0, strlen($lowercase) - 1)];
        $password .= $numbers[rand(0, strlen($numbers) - 1)];
        $password .= $symbols[rand(0, strlen($symbols) - 1)];
        
        $allCharacters = $uppercase . $lowercase . $numbers . $symbols;
        
        for ($i = 0; $i < $length - 4; $i++) {
            $password .= $allCharacters[rand(0, strlen($allCharacters) - 1)];
        }
        
        return str_shuffle($password);
    }

    // \\\\\\\\\\\\\\\==============\\\\\\\\\\\\\\\\\\\

    
    // Start Alls Action For Shange Email Profile
    // إرسال رمز التأكيد        
    public function starttoshangeemailprofile(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|string',
            ]);

            $MyProfileNow = Auth::user();
            $idProfileBssNow = $MyProfileNow->curret_profile_id_Bss;
            $SheckMyProfIDUser = $MyProfileNow->curret_profile_id;

            $MyProfileUser = $MyProfileNow->ProfileUser()->where('user_id', $SheckMyProfIDUser)->exists();

            $ProfileBssLoginNow = $MyProfileNow->ProfileUserBss()->where('id', $idProfileBssNow)->exists();


            $SmplEmail = strip_tags($request->email);
            $Smplcode = strip_tags($request->code);

            if($MyProfileUser) {
                $user = User::where('email', $SmplEmail)->exists();

                if ($user) {
                    return response()->json([
                        'message' => 'البريد الإلكتروني غير صحيح أو غير مسجل',
                        'data' => [],
                        'typAction' => 2,
                    ], 200);
                    return;
                }
            } else if($ProfileBssLoginNow) {
                $userBss = ProfileUserBss::where('email', $SmplEmail)->exists();
                if ($userBss) {
                    return response()->json([
                        'message' => 'البريد الإلكتروني غير صحيح أو غير مسجل',
                        'data' => [],
                        'typAction' => 2,
                    ], 200);
                    return;
                }
            } else {
                return response()->json([
                    'message' => 'تم إرسال رمز التأكيد إلى بريدك الإلكتروني',
                    'data' => [],
                    'typAction' => 5,
                ], 200);
                return;
            }

            PasswordResetCode::where('email', $SmplEmail)->delete();
            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            PasswordResetCode::create([
                'email' => $SmplEmail,
                'code' => $code,
                'created_at' => now()
            ]);
            $this->MessageToShangeEmailProfile = new MessageToShangeEmailProfile();
            $result = $this->MessageToShangeEmailProfile->sendVerificationCode($SmplEmail, $code);

            if($result) {
                return response()->json([
                    'message' => 'تم إرسال رمز التأكيد إلى بريدك الإلكتروني',
                    'data' => [],
                    'typAction' => 1,
                ], 200);
                return;
            } else {
                return response()->json([
                    'message' => 'تم إرسال رمز التأكيد إلى بريدك الإلكتروني',
                    'data' => [],
                    'typAction' => 5,
                ], 200);
                return;
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 19,
            ], 200);
        }
    }

    public function startconfirmedcodtoshangeemailprofile(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|string',
                'code' => 'required|digits:6'
            ]);
            
            $MyProfileNow = Auth::user();
            $idProfileBssNow = $MyProfileNow->curret_profile_id_Bss;
            $SheckMyProfIDUser = $MyProfileNow->curret_profile_id;

            $MyProfileUser = $MyProfileNow->ProfileUser()->where('user_id', $SheckMyProfIDUser)->select('id', 'email')->first();

            $ProfileBssLoginNow = $MyProfileNow->ProfileUserBss()->where('id', $idProfileBssNow)->select('id', 'email')->first();

            $SmplEmail = strip_tags($request->email);
            $Smplcode = strip_tags($request->code);

            if($MyProfileUser) {
                $user = User::where('email', $SmplEmail)->first();
                if ($user) {
                    return response()->json([
                        'message' => 'البريد الإلكتروني غير صحيح أو غير مسجل',
                        'data' => [],
                        'typAction' => 2,
                    ], 200);
                    return;
                }
            } else if($ProfileBssLoginNow) {
                $userBss = ProfileUserBss::where('email', $SmplEmail)->exists();
                if ($userBss) {
                    return response()->json([
                        'message' => 'البريد الإلكتروني غير صحيح أو غير مسجل',
                        'data' => [],
                        'typAction' => 2,
                    ], 200);
                    return;
                }
            }

            // التحقق من صحة الرمز
            $SheckCodFir = PasswordResetCode::where('email', $SmplEmail)
                    ->where('code', $Smplcode)
                    ->where('created_at', '>=', now()->subMinutes(30))
                    ->exists();
            if (!$SheckCodFir) {
                return response()->json([
                    'message' => 'الرمز غير صحيح أو منتهي الصلاحية',
                    'data' => [],
                    'typAction' => 3,
                ], 200);
                return;
            }
            $typactionhange = '';
            
            $this->HandleConfirmedShangeEmailProfile = new HandleConfirmedShangeEmailProfile();

            if($MyProfileUser) {
                $MyProfileUser->update([
                    'email' => $SmplEmail,
                ]);
                $MyProfileNow->update([
                    'email' => $SmplEmail,
                ]);
                $typactionhange = 'usersmpl';
            } else if($ProfileBssLoginNow) {
                $ProfileBssLoginNow->update([
                    'email' => $SmplEmail,
                ]);
                $typactionhange = 'bss';
            }

            $result = $this->HandleConfirmedShangeEmailProfile->sendVerificationCode($SmplEmail, $typactionhange);
            
            if($result) {
                $datamoreUserNow = [];
                if($MyProfileUser) {
                    $datamoreUserNow = $this->StartGetAllsDataProfileSmpl($user);
                } else if($ProfileBssLoginNow) {
                    $datamoreUserNow = $this->GetAllsDataMyProfileBssNow();
                }
                // حذف الرمز بعد استخدامه
                PasswordResetCode::deleteCode($SmplEmail, $Smplcode);
                return response()->json([
                    'message' => 'تم إرسال رمز التأكيد إلى بريدك الإلكتروني',
                    'data' => $datamoreUserNow->getData(),
                    'typAction' => 1,
                ], 200);
            } else {
                $ProfileBssLoginNow->update([
                    'email' => $SmplEmail,
                ]);
                return response()->json([
                    'message' => 'تم إرسال رمز التأكيد إلى بريدك الإلكتروني',
                    'data' => [],
                    'typAction' => 9,
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 9,
            ], 200);
        }
    }
    //== Start Alls Action For Shange Email Profile ==//

    // Start Show Date Profile User
    function ProfileUserBessSocailMd(Request $request) {
        try {
            $MyProfileNow = Auth::user();
            $idProfileBssNow = $MyProfileNow->curret_profile_id_Bss;
            $ProfileBssLoginNow = $MyProfileNow->ProfileUserBss()->where('id', $idProfileBssNow)->first();
            
            if($ProfileBssLoginNow) {
                $request->validate([
                    'facebouckeLinck' => 'nullable|string',
                    'TikTokeLinckSpm' => 'nullable|string',
                    'InstagrameLinck' => 'nullable|string',
                    'YoutubeLinckSpm' => 'nullable|string',
                    'TewaterXlinck' => 'nullable|string',
                    'instagramLinck' => 'nullable|string',
                    'SnabeShateLinckSpm' => 'nullable|string',
                ]);
                $user_id = $MyProfileNow->id;
                $facebookLinck = strip_tags($request->facebouckeLinck);
                $tiktokeLinck = strip_tags($request->TikTokeLinckSpm);
                $snabeshateLinck = strip_tags($request->SnabeShateLinckSpm);
                $youtubeLinck = strip_tags($request->YoutubeLinckSpm);
                $tewayteXLinck = strip_tags($request->TewaterXlinck);
                $instagramLinck = strip_tags($request->InstagrameLinck); //SnabeShateLinck
                
                $DatToUpdateProf = [];
                if($facebookLinck != '') {
                    $DatToUpdateProf['facebookLinck'] = $facebookLinck;
                } 
                if($tiktokeLinck != '') {
                    $DatToUpdateProf['tiktokeLinck'] = $tiktokeLinck;
                } 
                if($snabeshateLinck != '') {
                    $DatToUpdateProf['snabeshateLinck'] = $snabeshateLinck;
                }
                if($youtubeLinck != '') {
                    $DatToUpdateProf['youtubeLinck'] = $youtubeLinck;
                }
                if($tewayteXLinck != '') {
                    $DatToUpdateProf['tewayteXLinck'] = $tewayteXLinck;
                }
                if($instagramLinck != '') {
                    $DatToUpdateProf['instagramLinck'] = $instagramLinck;
                }
                
                $DAtaProfileUpd = $ProfileBssLoginNow->update($DatToUpdateProf);
                if($DAtaProfileUpd) {
                    return response()->json([
                        'message' => 'SucesseFuly Update Profile To SouCail Media ',
                        'data' => 1
                    ]);
                } else {
                    return response()->json([
                        'message' => 'This Name Bessnese Hase One Error ',
                        'data' => 2
                    ]);
                }
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 9,
            ], 200);
        }

    } //=== End Show Date Profile User ===//

    // Start Show Date Profile User
    function StartUpdateProfileUser(Request $request) {
        try {
            $MyProfileNow = Auth::user();
            $SmplProfileLoginNow = $MyProfileNow->curret_profile_id;
            if($SmplProfileLoginNow) {
                $request->validate([
                    'nameUser' => 'nullable|string',
                    'emailUser' => 'nullable|string',
                    'numberPhoneUser' => 'nullable|string',
                    'cantryUser' => 'nullable|string',
                ]);
                $nameUser = strip_tags($request->nameUser);
                $emailUser = strip_tags($request->emailUser);
                $numberPhoneUser = strip_tags($request->numberPhoneUser);
                $cantryUser = strip_tags($request->cantryUser);
                if($nameUser != '') {
                    $DatToUpdateProf['name'] = $nameUser;
                } 
                if($emailUser != '') {
                    $DatToUpdateProf['email'] = $emailUser;
                } 
                if($numberPhoneUser != '') {
                    $DatToUpdateProf['NumberPhone'] = $numberPhoneUser;
                }
                if($cantryUser != '') {
                    $DatToUpdateProf['cantry'] = $cantryUser;
                }
                
                $DAtaProfileUpd = ProfileUser::where('user_id', $SmplProfileLoginNow)->update($DatToUpdateProf);
                if($DAtaProfileUpd) {
                    return response()->json([
                        'message' => 'Data To UPDATE Profile User Bss ',
                        'data' => 1,
                    ]);
                } else {
                    return response()->json([
                        'message' => 'Data To UPDATE Profile User Bss ',
                        'data' => 2,
                    ]);
                }
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 9,
            ], 200);
        }

    } //=== End Show Date Profile User ===//

    // Start Show All Date User And Profile
    function ShowDateUserAndProfile() {
        $userData = Auth::user();

        $checkUser = Auth::User();
        $SeckeProfileUser = ProfileUser::where('user_id', $checkUser->id)->first();
        if(!$SeckeProfileUser) {
            ProfileUser::create([
                'user_id' => $checkUser->id,
                'name' => $checkUser->name,
                'email' => $checkUser->email,
                'NumberPhone' => '22992299',
            ]);
        }
        return response()->json([
            'messahge' => 'From Show All Date User And Profile',
            'data' => $userData
        ]);
    }
    //=== End Show All Date User And Profile ===//

    // Start Create Password Setting And Update
    function PsswordSettingAction(Request $request) {
        try {
            $UserDate = Auth::user();
            $SmplProfileLoginNow = $UserDate->curret_profile_id;
            $SheckMyProfIDBss = $UserDate->curret_profile_id_Bss;
            $MyProfileUser = $UserDate->ProfileUser()->where('user_id', $SmplProfileLoginNow)->exists();
            $MyProfileBss = $UserDate->ProfileUserBss()->where('id', $SheckMyProfIDBss)->exists();
            
            if($MyProfileUser) {
                $request->validate([
                    'passwordUpd' => 'required|string|min:6|max:100',
                ]);
                $passwordVlai = strip_tags($request->passwordUpd);
                $GetDataUser = User::where('id', $UserDate->id)->select('id', 'password')->first();
                $dataUpd = $GetDataUser->update(['password' => Hash::make($passwordVlai)]);
                if($dataUpd) {
                    return response()->json([
                        'message' => 'Suuceefuly Update Password',
                        'typAction' => 1,
                        'data' => [],
                    ], 201);
                } else {
                    return response()->json([
                        'message' => 'Your Are Create Your Password Setting',
                        'typAction' => 2,
                        'data' => [],
                    ], 201);
                }
            } else if($MyProfileBss) {
                $request->validate([
                    'passwordSetting' => 'required|string|max:10',
                ]);
                $passwordVlai = strip_tags($request->passwordSetting);
                $user_id = $UserDate->id;
                $sheckpassword = $UserDate->UserPasswordStting()->where('idbss', $SheckMyProfIDBss)->select('id', 'password')->first();
                if($sheckpassword) {
                    $data = $sheckpassword->update(['password' => Hash::make($passwordVlai)]);
                    return response()->json([
                        'message' => 'Your Are Update Your Password Setting',
                        'data' => [],
                        'typAction' => 2,
                    ], 201);
                } else {
                    $date = UserPasswordStting::create([
                        'user_id' => strip_tags($user_id),
                        'idbss' => $SheckMyProfIDBss,
                        'password' => Hash::make($passwordVlai),
                    ]);
                    return response()->json([
                        'message' => 'Your Are Create Your Password Setting',
                        'data' => [],
                        'typAction' => 1,
                    ], 201);
                }
            } else {
                return response()->json([
                    'message' => 'Your Are Create Your Password Setting',
                    'data' => [],
                    'typAction' => 3,
                ], 201);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 9,
            ], 200);
        }
    } //=== Start Create Password Setting And Update ===//

    // Start Create Password Setting And Update
    function StartUpdatePasswordUserProf(Request $request) {
        try {
            $UserDate = Auth::user();
            $DatProfNow = $UserDate->curret_profile_id;
            if($DatProfNow) {
                $request->validate([
                    'passwordUpd' => 'required|string',
                ]);
                $passwordVlai = strip_tags($request->passwordUpd);
                $sheckpassword = User::where('id', $UserDate->id)->first();
                if($sheckpassword) {
                    $data = $sheckpassword->update(['password' => Hash::make($passwordVlai)]);
                    return response()->json([
                        'message' => 'Your Are Update Your Password Setting',
                        'data' => 1
                    ], 201);
                } else {
                    return response()->json([
                        'message' => 'Your Are Create Your Password Setting',
                        'data' => 2
                    ], 201);
                }
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 9,
            ], 200);
        }
    } //=== Start Create Password Setting And Update ===//

    //Start Alls Actions For Edart Category Bss
    // Start Show Category For User Bss
    public function ShowAllCategoryUser() {
        try {
            if(Auth::check()){
                $ProfileData = Auth::user();
                $SheckMyProfID = $ProfileData->curret_profile_id_Bss;
                $MyProfile = $ProfileData->ProfileUserBss('id', $SheckMyProfID)->select('id')->first();
                if($MyProfile) {
                    $MyCategoryAll = $ProfileData->userCategory('IdBss', $SheckMyProfID)->select('id', 'category')->latest()->paginate(10);
                        return response()->json([
                        'merssage' => 'Success Get All My Category',
                        'typAction' => 1,
                        'data' => $MyCategoryAll,
                    ]);
                } {
                    return response()->json([
                        'merssage' => 'Sore Semthing Has In Error',
                        'data' => 6,
                    ]);
                }
            } else {
                    return response()->json([
                    'merssage' => 'Sore Semthing Has In Error',
                    'data' => 9,
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 9,
            ], 200);
        }
    } //=== End Show Category For User Bss===//

    // sereach All Category User
    function sereachCategoryUser($categoryToSereach) {
        try {
            $datuser = Auth::user();
            $SheckMyProfID = $datuser->curret_profile_id_Bss;
            $MyProfile = $datuser->ProfileUserBss('id', $SheckMyProfID)->first();
            
            if($MyProfile) {
                $categoryToSereach = strip_tags($categoryToSereach);
                $SheckCategory = $datuser->userCategory()->where(
                    // 'idbss', $SheckMyProfID,
                    'category', 'LIKE', $categoryToSereach. '%',
                    // 'category' => 'LIKE' => $categoryToSereach. '%',
                )->select('id', 'category')->latest()->paginate(10);
                return response()->json([
                    'message' => 'Are You Search To',
                    'data' => $SheckCategory,
                    'typAction' => 1,
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 9,
            ], 200);
        }
    } //=== sereach All Category User ===//

    // Start Create Now Category For User Bss 
    function storeCategoryUser(Request $request) {
        try {
            $ProfileData = Auth::user();
            $SheckMyProfID = $ProfileData->curret_profile_id_Bss;
            $MyProfile = $ProfileData->ProfileUserBss('id', $SheckMyProfID)->select('id')->first();
            if($MyProfile) {
                $request->validate([
                    'category' => 'required|max:100',
                    'passwordSetting' => 'required|string|unique:user_password_sttings,password'
                ]);
                $user_id = $ProfileData->id;
                $passwordStingHa = strip_tags($request->passwordSetting);
                $shekpas = $ProfileData->UserPasswordStting()->where('idbss', $SheckMyProfID)->select('id', 'password')->first();
                if($shekpas === null) {
                    return response()->json([
                        'message' => 'Your`e Not Have Eny Password Setting Son Plz Go To Create',
                        'typAction' => 5,
                        'data' => [],
                    ]);
                }
        
                $passwordStingHaahing = Hash::make($passwordStingHa);
                $passwordHash = $shekpas->password;
                $categorName = strip_tags($request->category);
                $redPassword = request('passwordSetting');
                if($passwordHash) {
                    if(Hash::check($redPassword, $passwordHash)) {
                        $SheckThisName = $ProfileData->userCategory()->where([
                            'IdBss' => $SheckMyProfID,
                            'category' => $categorName,
                        ])->select('id')->first();
                        if($SheckThisName) {
                            $dataUpdate = $ProfileData->userCategory()->where('IdBss', $SheckMyProfID)->select('id', 'category')->latest()->paginate(10);
                            return response()->json([
                                'message' => 'This Category is On leary Created',
                                'typAction' => 4,
                                'data' => $dataUpdate,
                            ]);
                        }
                        $categorydate = $ProfileData->userCategory()->create([
                            'category' => $categorName,
                            'IdBss' => $SheckMyProfID,
                        ]);
                        if($categorydate) {
                            $dataUpdate = $ProfileData->userCategory()->where('IdBss', $SheckMyProfID)->select('id', 'category')->latest()->paginate(10);
                            return response()->json([
                                'message' => 'seccuess Create Category',
                                'typAction' => 1,
                                'data' => $dataUpdate,
                            ],201);
                        } else {
                            $dataUpdate = $ProfileData->userCategory()->where('IdBss', $SheckMyProfID)->select('id', 'category')->latest()->paginate(10);
                            return response()->json([
                                'message' => 'seccuess Create Category',
                                'typAction' => 9,
                                'data' => $dataUpdate,
                            ],201);
                        }
                    } else {
                        return response()->json([
                            'message' => 'Password Sting Is Not Corect',
                            'typAction' => 2,
                            'data' => [],
                        ],200);
                    }
                } else {
                    return response()->json([
                        'message' => 'Your Password Seting Is Not Correct',
                        'typAction' => 5,
                        'data' => [],
                    ]);
                }
            } else {
                return response()->json([
                    'message' => 'Error Semthing Not Found',
                    'data' => 3
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 9,
            ], 200);
        }
    }//=== End Create Now Category For User Bss ===//

    // Start Update Semthing Category For User Bss
    function updateCategoryUser(Request $request, $CategoryID) {
        try {
            $ProfileData = Auth::user();
            $SheckMyProfID = $ProfileData->curret_profile_id_Bss;
            $MyProfileBss = $ProfileData->ProfileUserBss('id', $SheckMyProfID)->first();
            if($MyProfileBss) {
                $request->validate([
                    'category' => 'required|max:100',
                    'passwordSetting' => 'required|string|unique:user_password_sttings,password'
                ]);

                $user_id = $ProfileData->id;
                $passwordStingNow = strip_tags($request->passwordSetting);
                $shekpas = $ProfileData->UserPasswordStting()->where('idbss', $SheckMyProfID)->select('id', 'password')->first();
                $passwordHash = $shekpas->password;
                
                if(!$passwordHash) {
                    return response()->json([
                        'message' => 'Your`e Not Have Eny Password Setting Son Plz Go To Create',
                        'data' => 5,
                        'ds' => $shekpas
                    ]);
                }
                $passwordStingHaahing = Hash::make($passwordStingNow);
                $redPassword = request('passwordSetting');

                $CategoryUpdate = strip_tags($request->category);
                $CategoryID = strip_tags($CategoryID);
                if($passwordHash) {
                    if(Hash::check($redPassword, $passwordHash)){
                        $SheckMyCt = userCategory::where([
                            'user_id' => $user_id,
                            'category' => $CategoryUpdate,
                        ])->first();
                        if($SheckMyCt) {
                            return response()->json([
                                'message' => 'Sorry This Category Has One Realy Created',
                                'typAction' => 7,
                                'data' => []
                            ],201);
                        }
                        $MyCategory = $ProfileData->userCategory()->where('id', $CategoryID)->select('id', 'category')->first();
                        if($MyCategory) {
                            $UpdatCatego = $MyCategory->update([
                                'category' => $CategoryUpdate
                            ]);
                            
                            if($UpdatCatego) {
                                $MyCategoryAll = $ProfileData->userCategory(['IdBss', $SheckMyProfID])->latest()->select('id', 'category')->paginate(10);
                                return response()->json([
                                    'message' => 'Success Update This Category',
                                    'typAction' => 1,
                                    'data' => $MyCategoryAll,
                                ],201);
                            }
                        } else {
                            return response()->json([
                                'message' => 'Password Sting Is Not Corect',
                                'typAction' => 2,
                                'data' => [],
                            ],200);
                        }
                    } else {
                        return response()->json([
                            'message' => 'Your Password Seting Is Not Correct',
                            'typAction' => 3,
                            'data' => [],
                        ]);
                    }
                } else {
                    return response()->json([
                        'message' => 'Your Password Seting Is Not Correct',
                        'typAction' => 5,
                        'data' => $MayCategory,
                    ]);
                }
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 9,
            ], 200);
        }
    }//=== End Update Semthing Category For User Bss===//
    //=== End Alls Actions For Edart Category Bss ===//

    // Start Alls Actions For Prodects Bss Now
    // Start Create Prodect Bss 
    function StartstoreProdectForBss(Request $request) {
        try {
            $ProfileData = Auth::user();
            $SheckMyProfID = $ProfileData->curret_profile_id_Bss;
            $MyProfileBssNow = $ProfileData->ProfileUserBss('id', $SheckMyProfID)->select('id')->first();
            if($MyProfileBssNow) {
                $request->validate([
                    'name' => 'required|string|max:100',
                    'descprice' => 'nullable|numeric',
                    'categoryID' => 'required|array',
                    'categoryID.*' => 'integer|exists:user_categories,id',
                    'priceprodect' => 'required|numeric',
                    'totaleinstorage' => 'required|numeric',
                    'discreption' => 'nullable|string|max:150',
                    'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10048',
                ]);
                $MyCurrentPaymentPay = CurrentPaymentForUseBss::where('usernameBss', $SheckMyProfID)->latest()->select('currentCantry')->first();
                $categoryID = $request->categoryID;
                $descprice = strip_tags($request->descprice);
                $discreption = strip_tags($request->discreption);
                $priceprodect = strip_tags($request->priceprodect);
                $namePd = strip_tags($request->name);
                $totaleinstorage = strip_tags($request->totaleinstorage);
                $searchMProdect = $ProfileData->prodectUser()->where('name', 'LIKE', $namePd. '%')->first();
                $user_id = strip_tags($ProfileData->id);
                $SheckNameProd = ProdectUser::where([
                    'idBss' => $SheckMyProfID,
                    'name' => $namePd,
                ])->select('id', 'name')->first();

                if($SheckNameProd) {
                    return response()->json([
                        'message' => 'Sorry This Name Has One Realy Create In Prodect',
                        'typAction' => 6,
                        'data' => [],
                    ]);
                }

                $ProdectUser = new ProdectUser;

                $ProdectUser->user_id = $ProfileData->id;
                $ProdectUser->idBss = $SheckMyProfID;
                $ProdectUser->categoryID = json_encode($categoryID);
                $ProdectUser->name = $namePd;
                $ProdectUser->price = $priceprodect;
                $ProdectUser->descprice = $descprice;
                $ProdectUser->totaleinstorage = $totaleinstorage;
                $ProdectUser->discreption = $discreption;
                $ProdectUser->currentPay = $MyCurrentPaymentPay->currentCantry;
                $ProdectUser->TypePayprd = 1;

                if($request->hasFile('image')) {
                    $image = $request->file('image');
                    $gen = hexdec(uniqid());
                    $ext = strtolower($image->getClientOriginalExtension());
                    $namePod = $gen . '' . $ext;
                    $location = 'user-prodect/';
                    $source = $location.$namePod;
                    $name = $gen. '.' .$ext;
                    $source = $location.$name;
                    $image->move($location,$name);
                    $ProdectUser->img = $source;
                    
                }

                $SavDat = $ProdectUser->save();

                if($SavDat) {
                    return response()->json([
                        'message' => 'all My Date Is The Next Date',
                        'typAction' => 1,
                        'data' => [],
                    ],201);
                } else {
                    return response()->json([
                        'message' => 'all My Date Is The Next Date',
                        'typAction' => 9,
                        'data' => [],
                    ],204);
                }
            } else {
                return response()->json([
                    'message' => 'Plz Enter Your min one Category For Your Prodect',
                    'data' => 11,
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 9,
            ], 200);
        }
    } //=== Start Create Prodect Bss ===//

    // Start Update Prodect For Bss In ID
    function UpdateProdectBssForId(Request $request ,$ProdectID) {
        try {
            $SheckMyProfIDBss = Auth::user()->curret_profile_id_Bss;
            $MyProfileBss = Auth::user()->ProfileUserBss()->where('id', $SheckMyProfIDBss)->select('id')->first();
            if(Auth::check()){
                if($MyProfileBss) {
                    $request->validate([ 
                        'name' => 'required|string|max:200',
                        'descprice' => 'nullable|numeric',
                        'categoryID' => 'required|array',
                        'categoryID.*' => 'integer|exists:user_categories,id',
                        'priceprodect' => 'nullable|numeric',
                        'totaleinstorage' => 'nullable|numeric',
                        'discreption' => 'nullable|string|max:200',
                        'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10048',
                    ]);

                    $categoryID = $request->categoryID;
                    $descprice = strip_tags($request->descprice);
                    $ProdectID = strip_tags($ProdectID);
                    $discreption = strip_tags($request->discreption);
                    $priceprodect = strip_tags($request->priceprodect);
                    $namePd = strip_tags($request->name);
                    $totaleinstorage = strip_tags($request->totaleinstorage);
                    $SheckNameProd = Auth::user()->prodectUser()->where([
                        'idBss' => $SheckMyProfIDBss,
                        'name' => $namePd,
                    ])->select('id', 'name')->first();
                    if($SheckNameProd) {
                        return response()->json([
                            'message' => 'Soory Name Prodect Is One Realy Create For Anoter Prodect',
                            'typAction' => 6,
                            'data' => [],
                        ], 201);
                    }
                    $DateProdectUpdated = [];
                    $user_id = Auth::user()->id;
                    
                    $MyProdect = Auth::user()->prodectUser()->where([
                        'idBss' => $SheckMyProfIDBss,
                        'id' => $ProdectID,
                    ])->select('id', 'name', 'price', 'totaleinstorage', 'descprice', 'discreption', 'img', 'categoryID')->first();
                    
                    if($MyProdect) {
                        $sheckUpdateType = false;

                        if($MyProdect->categoryID != json_encode($categoryID) && count($categoryID) != 0) {
                            $MyProdect->categoryID = json_encode($categoryID);
                            $DateProdectUpdated['categoryID'] = 'update';
                            $sheckUpdateType = true;
                        }

                        if($MyProdect->name != $namePd && $namePd != '') {
                            $MyProdect->name = $namePd;
                            $DateProdectUpdated['name'] = $namePd;
                            $sheckUpdateType = true;
                        }

                        if($MyProdect->price != $priceprodect && $priceprodect != '' && $priceprodect != 0) {
                            $MyProdect->price = $priceprodect;
                            $DateProdectUpdated['price'] = $priceprodect;
                            $sheckUpdateType = true;
                        }

                        if($MyProdect->totaleinstorage != $totaleinstorage && $totaleinstorage != '' && $totaleinstorage != 0) {
                            $MyProdect->totaleinstorage = $totaleinstorage;
                            $DateProdectUpdated['totaleinstorage'] = $totaleinstorage;
                            $sheckUpdateType = true;
                        }

                        if($MyProdect->descprice != $descprice && $descprice != '' && $descprice != 0) {
                            $MyProdect->descprice = $descprice;
                            $DateProdectUpdated['descprice'] = $descprice;
                            $sheckUpdateType = true;
                        }

                        if($MyProdect->discreption != $discreption && $discreption != '') {
                            $MyProdect->discreption = $discreption;
                            $DateProdectUpdated['discreption'] = $discreption;
                            $sheckUpdateType = true;
                        }

                        if($request->hasFile('image')) {
                            if($MyProdect->img) {
                                unlink($MyProdect->img);
                            }

                            $image = $request->file('image');
                            $gen = hexdec(uniqid());
                            $ext = strtolower($image->getClientOriginalExtension());
                            $location = "user-prodect/";
                            $namePod = $gen . '' . $ext;
                            $source = $location.$namePod;
                            $name = $gen. '.' .$ext;
                            $source = $location.$name;
                            $image->move($location,$name);
                            $MyProdect->img = $source;
                            $DateProdectUpdated['img'] = 'update';
                            $sheckUpdateType = true;
                            
                        }

                        if($sheckUpdateType) {
                            $DateProdectUpdated['update_All'] = 'update';
                        }

                        $UpdatProd = $MyProdect->update();

                        if($UpdatProd) {
                            return response()->json([
                                'message' => 'Your Are Update Prodect Is Success',
                                'typAction' => 1,
                                'data' => $DateProdectUpdated,
                            ], 201);
                        } else {

                            return response()->json([
                                'message' => 'Sor On Get Semthigb Error Plz Return Agen Later',
                                'typAction' => 3,
                                'data' => [],
                            ], 200);

                        }
                        
                    } else {
                        return response()->json([
                            'message' => 'Sor On Get Semthigb Error Plz Return Agen Later',
                            'data' => 4,
                        ], 200);
                    }
                } else {
                    return response()->json([
                        'message' => 'Sor On Get Semthigb Error Plz Return Agen Later',
                        'data' => 5,
                    ], 200);
                }
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 9,
            ], 200);
        }
    } //=== End Update Prodect For Bss In ID ===//

    // Start Show Prodect For Bss
    function ShowAllProdectProfileBss() {
        try {
            $ProfilData = Auth::user();
            $SheckMyProfIDBss = $ProfilData->curret_profile_id_Bss;
            $MyProfileBss = $ProfilData->ProfileUserBss('id', $SheckMyProfIDBss)->select('id')->first();
            if(Auth::check()){
                if($MyProfileBss) {
                    $MyCategoryAll = ProdectUser::where([
                        'IdBss' => $SheckMyProfIDBss,
                        'user_id' => $ProfilData->id,
                        ])->select('id', 'img', 'name', 'TypePayprd', 'totaleinstorage',  'currentPay', 'TypePayprd', 'price')->latest()->paginate(10);
                        return response()->json([
                        'merssage' => 'Success Get All My Category',
                        'typAction' => 1,
                        'data' => $MyCategoryAll,
                    ]);
                } else {
                    return response()->json([
                        'merssage' => 'Sore Semthing Has In Error',
                        'data' => 3,
                    ]);
                }
            } else {
                    return response()->json([
                        'merssage' => 'Sore Semthing Has In Error',
                        'data' => 9,
                    ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 9,
            ], 200);
        }
    } //=== End Show Prodect For Bss ===//

    // Start Sereach Prodect For Contect Id Category To Do Semthing Action
    function SereachProdectForCategoryNameToGetAll($categoId) {
        try {
            $ProfileData = Auth::user();
            $SheckMyProfIDBss = $ProfileData->curret_profile_id_Bss;
            $MyProfileBss = $ProfileData->ProfileUserBss('id', $SheckMyProfIDBss)->select('id')->first();
            if($MyProfileBss) {
                $IdProd = strip_tags($categoId);
                $user_id = $ProfileData->id;
                $categoryDate = $ProfileData->userCategory()->where('id', $IdProd)->select('id')->first();
                $categoryDataThisID = json_encode($categoryDate->id);
                $DateProdecCate = prodectUser::where([
                    'user_id' => $user_id,
                    'idBss' => $SheckMyProfIDBss
                ])->whereJsonContains('categoryID', $categoryDataThisID)->latest()->select('id', 'img', 'name', 'TypePayprd', 'totaleinstorage',  'currentPay', 'TypePayprd', 'price')->paginate(1);
                if($DateProdecCate) {
                    return response()->json([
                        'message' => 'Wellcom From Sereach Prodect For Category Name',
                        'data' => $DateProdecCate,
                        'typAction' => 1,
                    ], 200);
                } else {
                    $dataUpdt = prodectUser::where([
                    'user_id' => $user_id,
                    'idBss' => $SheckMyProfIDBss
                    ])->select('id', 'img', 'name', 'TypePayprd', 'totaleinstorage',  'currentPay', 'TypePayprd', 'price')->latest()->paginate(10);
                    return response()->json([
                        'message' => 'Wellcom From Sereach Prodect For Category Name',
                        'typAction' => 2,
                        'data' => $dataUpdt,
                ], 200);}
            } else {
                return response()->json([
                    'message' => 'Wellcom From Sereach Prodect For Category Name',
                    'data' => 4,
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 9,
            ], 200);
        }        
    } //=== End Sereach Prodect For Contect Id Category To Do Semthing Action ===//
    
    // Start Sereach Prodect For Id To Do Semthing Action
    function sereachProdectForName($IdProd) {
        try {
            $ProfileData = Auth::user();
            $SmpIdProd = strip_tags($IdProd);
            $idProfileBssNow = $ProfileData->curret_profile_id_Bss;
            $ProfileBssLoginNow = $ProfileData->ProfileUserBss('id', $idProfileBssNow)->select('id')->first();
            if($ProfileBssLoginNow) {
                $SheckProdect = prodectUser::where([
                    'IdBss' => $idProfileBssNow,
                    'user_id' => $ProfileData->id,
                    'id' => $SmpIdProd,
                ])->latest()->select('id', 'img', 'name', 'TypePayprd', 'totaleinstorage',  'currentPay', 'TypePayprd', 'price')->paginate(10);
                return response()->json([
                    'message' => 'Are You Search To',
                    'data' => $SheckProdect,
                    'typAction' => 1,
                ]);
            } else {
                return response()->json([
                    'message' => 'Are You Search To',
                    'data' => 21,
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 9,
            ], 200);
        }
    }  //=== End Sereach Prodect For Id To Do Semthing Action ===//

    // Start To Active Pay Prodect Bss
    function ActivePayProdectForId(Request $request, $ProdectId) {
        try {
            $MyProfileNow= Auth::user();
            $idProfileBssNow = $MyProfileNow->curret_profile_id_Bss;
            $ProfileBssLoginNow = $MyProfileNow->ProfileUserBss('id', $idProfileBssNow)->select('id')->first();
            if($ProfileBssLoginNow) {
                $request->validate([
                    'passwordSetting' => 'required|string'
                ]);
                $passwordStingHa = strip_tags($request->passwordSetting);
                $shekpas = $MyProfileNow->UserPasswordStting()->where('idbss', $idProfileBssNow)->select('id', 'password')->first();
                $passwordStingHaahing = Hash::make($passwordStingHa);
                $passwordHash = $shekpas->password;
                $redPassword = request('passwordSetting');
                if($passwordHash) {
                    if(Hash::check($redPassword, $passwordHash)) {
                        $datar = $MyProfileNow->prodectUser()->where([
                            'id' => $ProdectId,
                            'idBss' => $idProfileBssNow,
                        ])->select('id', 'TypePayprd', 'totaleinstorage')->first();
                        if($datar) {
                            if($datar->totaleinstorage == 0) {
                                $datarUpdate = ProdectUser::where([
                                    'IdBss' => $idProfileBssNow,
                                    'user_id' => $MyProfileNow->id,
                                ])->select('id', 'img', 'name', 'TypePayprd', 'totaleinstorage',  'currentPay', 'TypePayprd', 'price')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "Sorry Plz Agoute More Storage Prodect After Active Pay Prodect",
                                    'typAction' => 4,
                                    'data' => $datarUpdate,
                                ], 200);
                            } else if($datar->TypePayprd == 1) {
                                $datarUpdate = ProdectUser::where([
                                    'IdBss' => $idProfileBssNow,
                                    'user_id' => $MyProfileNow->id,
                                ])->select('id', 'img', 'name', 'TypePayprd', 'totaleinstorage',  'currentPay', 'TypePayprd', 'price')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "Sorry Your Are On Leary Confirmed Pay Prodect",
                                    'typAction' => 3,
                                    'data' => $datarUpdate,
                                ], 200);
                            }
                            
                            $ConfUpdaProd = $datar->update([
                                'TypePayprd' => 1,
                            ]);

                            if($ConfUpdaProd) {
                                $datarUpdate = ProdectUser::where([
                                    'IdBss' => $idProfileBssNow,
                                    'user_id' => $MyProfileNow->id,
                                ])->select('id', 'img', 'name', 'TypePayprd', 'totaleinstorage',  'currentPay', 'TypePayprd', 'price')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "SuccesFuly Active Pay Prodect",
                                    'typAction' => 1,
                                    'data' => $datarUpdate
                                ], 200);
                            }

                        } else {
                            $datarUpdate = ProdectUser::where([
                                'IdBss' => $idProfileBssNow,
                                'user_id' => $MyProfileNow->id,
                            ])->select('id', 'img', 'name', 'TypePayprd', 'totaleinstorage',  'currentPay', 'TypePayprd', 'price')->latest()->paginate(10);
                            return response()->json([
                                'message' => "Sorry Zeboune Has On Delete This Order Or This Order Not Found",
                                'typAction' => 2,
                                'data' => $datarUpdate,
                            ], 200);
                        }
                    } else {
                        return response()->json([
                            'message' => "Sorry Your Password Setting Is Not Correct",
                            'typAction' => 7,
                            'data' => [],
                        ], 200);
                    }
                } else {
                    return response()->json([
                        'message' => "Sorry Your Are Not Have Password Setting Plz Created",
                        'typAction' => 8,
                        'data' => [],
                    ], 200);
                }
            }else {
                return response()->json([
                    'message' => "Sorry Error  In Server Or Semthing Error Not Found",
                    'typAction' => 5,
                    'data' => [],
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 9,
            ], 200);
        }
    } // End To Active Pay Prodect Bss ===//

    // Start To Dsc Active Pay Prodect Bss
    function DscActivePayProdectForId(Request $request, $ProdectId) {
        try {
            $MyProfileNow= Auth::user();
            $idProfileBssNow = $MyProfileNow->curret_profile_id_Bss;
            $ProfileBssLoginNow = $MyProfileNow->ProfileUserBss('id', $idProfileBssNow)->select('id')->first();
            if($ProfileBssLoginNow) {
                $request->validate([
                    'passwordSetting' => 'required|string'
                ]);
                $passwordStingHa = strip_tags($request->passwordSetting);
                $shekpas = $MyProfileNow->UserPasswordStting()->where('idbss', $idProfileBssNow)->select('id', 'password')->first();
                $passwordStingHaahing = Hash::make($passwordStingHa);
                $passwordHash = $shekpas->password;
                $redPassword = request('passwordSetting');
                if($passwordHash) {
                    if(Hash::check($redPassword, $passwordHash)) {
                        $datar = $MyProfileNow->prodectUser()->where([
                            'id' => $ProdectId,
                            'idBss' => $idProfileBssNow,
                        ])->select('id', 'TypePayprd')->first();
                        if($datar) {
                            if($datar->TypePayprd == 2) {
                                $datarUpdate = ProdectUser::where([
                                    'IdBss' => $idProfileBssNow,
                                    'user_id' => $MyProfileNow->id,
                                ])->select('id', 'img', 'name', 'TypePayprd', 'totaleinstorage',  'currentPay', 'TypePayprd', 'price')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "Sorry Your Are On Leary Confirmed Pay Prodect",
                                    'typAction' => 3,
                                    'data' => $datarUpdate,
                                ], 200);
                            }
                            
                            $ConfUpdaProd = $datar->update([
                                'TypePayprd' => 2,
                            ]);

                            if($ConfUpdaProd) {
                                $datarUpdate = ProdectUser::where([
                                    'IdBss' => $idProfileBssNow,
                                    'user_id' => $MyProfileNow->id,
                                ])->select('id', 'img', 'name', 'TypePayprd', 'totaleinstorage',  'currentPay', 'TypePayprd', 'price')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "SuccesFuly Active Pay Prodect",
                                    'typAction' => 1,
                                    'data' => $datarUpdate
                                ], 200);
                            }

                        } else {
                            $datarUpdate = ProdectUser::where([
                                'IdBss' => $idProfileBssNow,
                                'user_id' => $MyProfileNow->id,
                            ])->select('id', 'img', 'name', 'TypePayprd', 'totaleinstorage',  'currentPay', 'TypePayprd', 'price')->latest()->paginate(10);
                            return response()->json([
                                'message' => "Sorry Zeboune Has On Delete This Order Or This Order Not Found",
                                'typAction' => 2,
                                'data' => $datarUpdate,
                            ], 200);
                        }
                    } else {
                        return response()->json([
                            'message' => "Sorry Your Password Setting Is Not Correct",
                            'typAction' => 7,
                            'data' => [],
                        ], 200);
                    }
                } else {
                    return response()->json([
                        'message' => "Sorry Your Are Not Have Password Setting Plz Created",
                        'typAction' => 8,
                        'data' => [],
                    ], 200);
                }
            } else {
                return response()->json([
                    'message' => "Sorry Error  In Server Or Semthing Error Not Found",
                    'typAction' => 5,
                    'data' => [],
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 9,
            ], 200);
        }
    } // End To Dsc Active Pay Prodect Bss ===//

    // Start Show Prodect For Bss In ID
    function ShowAllsProdectDataBss($ProdectID) {
        try {
            if(Auth::check()){
                $SheckMyProfIDBss = Auth::user()->curret_profile_id_Bss;
                $MyProfileBss = Auth::user()->ProfileUserBss()->where('id', $SheckMyProfIDBss)->select('id')->first();
                if($MyProfileBss) {
                    $MyProdect = Auth::user()->prodectUser()->where([
                        'id' => $ProdectID,
                        'idBss' => $SheckMyProfIDBss,
                    ])->select('id', 'categoryID', 'name', 'img', 'created_at', 'totaleinstorage', 'price', 'descprice', 'currentPay', 'TypePayprd')->first();
                    if(isset($MyProdect)) {
                        $categoryIDVip = json_decode($MyProdect->categoryID);
                        $categIDPas = [];
                        if($MyProdect->categoryID) {
                            foreach($categoryIDVip as $prodectid) {
                                $datas = Auth::user()->userCategory()->where('id', $prodectid)->select('id', 'category')->first();
                                $categIDPas[] = [
                                    "id" => $datas->id,
                                    "name" => $datas->category
                                ];
                            }
                        }
                        return response()->json([
                            'message' => 'my data Prodect',
                            'typAction' => 1,
                            'data' => $MyProdect,
                            'dataForEftProd' => $categIDPas,
                        ], 200);
                    } else {
                        return response()->json([
                            'message' => 'Sory THis Prodect DOnt Four You Or On Get Semthing Error So aling Later And ThanK You',
                            'typAction' => 3,
                            'data' => [],
                        ], 200);
                    }
                } else {
                    return response()->json([
                        'message' => 'Sory THis Prodect DOnt Four You Or On Get Semthing Error So aling Later And ThanK You',
                        'data' => 3,
                    ], 200);
                }
            } else {
                return response()->json([
                    'message' => 'Sory THis Prodect DOnt Four You Or On Get Semthing Error So aling Later And ThanK You',
                    'data' => 9,
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 9,
            ], 200);
        }
    }//=== End Show Prodect For Bss In ID ===//    

    // Start To Update Storage Prodect Bss
    function UpdateStorageProdectForId(Request $request, $ProdectId) {
        try {
            $MyProfileNow = Auth::user();
            $idProfileBssNow = $MyProfileNow->curret_profile_id_Bss;
            $ProfileBssLoginNow = $MyProfileNow->ProfileUserBss()->where('id', $idProfileBssNow)->select('id')->first();
            if($ProfileBssLoginNow) {
                $request->validate([
                    'passwordSetting' => 'required|string',
                    'StorageUp' => 'required|string',
                ]);
                $passwordStingHa = strip_tags($request->passwordSetting);
                $TotalStoragUp = strip_tags($request->StorageUp);
                $shekpas = $MyProfileNow->UserPasswordStting()->where('idbss', $idProfileBssNow)->select('id', 'password')->first();
                $passwordStingHaahing = Hash::make($passwordStingHa);
                $passwordHash = $shekpas->password;
                $redPassword = request('passwordSetting');
                if($passwordHash) {
                    if(Hash::check($redPassword, $passwordHash)) {
                        $datarProd = ProdectUser::where([
                            'id' => $ProdectId,
                            'idBss' => $idProfileBssNow,
                        ])->select('id', 'totaleinstorage')->first();
                            
                        if($datarProd) {
                            $ConfUpdaProd = $datarProd->update([
                                'totaleinstorage' => $TotalStoragUp,
                            ]);

                            if($ConfUpdaProd) {
                                $datarUpdate = ProdectUser::where([
                                    'idBss' => $idProfileBssNow,
                                    'user_id' => $MyProfileNow->id,
                                ])->select('id', 'img', 'name', 'TypePayprd', 'totaleinstorage',  'currentPay', 'TypePayprd', 'price')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "SuccesFuly Update Total Storage In Your Prodect",
                                    'typAction' => 1,
                                    'data' => $datarUpdate,
                                ], 200);
                            }

                        } else {
                            $datarUpdate = ProdectUser::where([
                                'idBss' => $idProfileBssNow,
                                'user_id' => $MyProfileNow->id,
                            ])->select('id', 'img', 'name', 'TypePayprd', 'totaleinstorage',  'currentPay', 'TypePayprd', 'price')->latest()->paginate(10);
                            return response()->json([
                                'message' => "Sorry Zeboune Has On Delete This Order Or This Order Not Found",
                                'typAction' => 2,
                                    'data' => $datarUpdate,
                            ], 200);
                        }
                    } else {
                        return response()->json([
                            'message' => "Sorry Your Password Setting Is Not Correct",
                            'typAction' => 7,
                            'data' => [],
                        ], 200);
                    }
                } else {
                    return response()->json([
                        'message' => "Sorry Your Are Not Have Password Setting Plz Created",
                        'data' => [],
                        'typAction' => 8,
                    ], 200);
                }
            } else {
                return response()->json([
                    'message' => "Sorry Error  In Server Or Semthing Error Not Found",
                    'typAction' => 5,
                    'data' => [],
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 9,
            ], 200);
        }
    } //== End To Update Storage Prodect Bss ==//
    //== End Alls Actions For Prodects Bss Now ==//

    // Start Alls Actions For Message User And Bss
    // Start Show All My Message
    function ShowMyAllMessage(Request $request) {
        try {
            $MyProfileNow = Auth::user();
            if($MyProfileNow->curret_profile_id_Bss) {
                $Data = MessageEghar::where('idbss', $MyProfileNow->curret_profile_id_Bss)->select('id', 'user_id', 'image', 'titel', 'message', 'NameUserSendMessage', 'sheckMessage', 'TypeMessage', 'CloceMessage', 'created_at')->latest()->paginate(10);
                return response()->json([
                    'message' => 'From Show All My Message',
                    'data' => $Data
                ]);
            } else if($MyProfileNow->curret_profile_id || $MyProfileNow->current_my_travel) {
                $Data = $MyProfileNow->MessageEghar()->select('id', 'user_id', 'image', 'titel', 'message', 'NameUserSendMessage', 'sheckMessage', 'TypeMessage', 'CloceMessage', 'created_at')->latest()->paginate(10);
                return response()->json([
                    'message' => 'From Show All My Message',
                    'data' => $Data,
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 9,
            ], 200);
        }
    } // End Show All My Message 

    // End Show All My Message
    
    // Start Response For Confirmed My Message To Add In Zeboune Bss
    function StoreConfirmedMyMessageForaddZeboune(Request $request, $MessageID) {
        try {
            $MyProfileNow = Auth::user();
            if($MyProfileNow->curret_profile_id ) {
                $request->validate([
                    'currentpagenone' => 'required|integer'
                ]);
                $currentpage = strip_tags($request->currentpagenone) * 10;
                $MessageID = strip_tags($MessageID);
                $MyId = $MyProfileNow->id;
                $DataMyMessage = $MyProfileNow->MessageEghar()->where('id', $MessageID)->select('user_id', 'id', 'ConfirmedRelactionUserZeboune', 'TypeMessage', 'ConfirmedRelation', 'TypeRelationMessageUser', 'idbss')->first();
                
                if($DataMyMessage) {
                    if($DataMyMessage->ConfirmedRelactionUserZeboune == 1) {
                        $DataUpd = $MyProfileNow->MessageEghar()->where('id', $MessageID)->select('id', 'titel', 'message', 'NameUserSendMessage', 'sheckMessage', 'TypeMessage', 'CloceMessage', 'created_at')->latest()->paginate($currentpage);
                        return response()->json([
                            'message' => 'Sorry Your Are One Realy Confirmed Your Message For Add Zeboune',
                            'data' => $DataUpd,
                            'typAction' => 3,
                        ]);
                    } else if($DataMyMessage->ConfirmedRelactionUserZeboune == 2) {
                        $DataUpd = $MyProfileNow->MessageEghar()->where('id', $MessageID)->select('id', 'titel', 'message', 'NameUserSendMessage', 'sheckMessage', 'TypeMessage', 'CloceMessage', 'created_at')->latest()->paginate($currentpage);
                        return response()->json([
                            'message' => 'Sorry Your Are One Realy Dsc Confirmed Your Message For Add Zeboune',
                            'data' => $DataUpd,
                            'typAction' => 4,
                        ]);
                    }

                    $EditDataMyMessage = [
                        'TypeRelationMessageUser' => 1,
                        'TypeMessage' => 'Confirmed',
                        'ConfirmedRelation' => 1
                    ];

                    $dataBssUsID = ProfileUserBss::where('id', $DataMyMessage->idbss)->first()->id;
                    
                    $DatZebayn = ZebouneForUser::where([
                        'usernameBss' => $DataMyMessage->idbss,
                        'user_id' => $DataMyMessage->user_id,
                    ])->select('id', 'ConfirmedRelactionUserZeboune', 'ConfirmedRelation')->first();
                    
                    $EditDataMyMessageZeboune = [
                        'ConfirmedRelactionUserZeboune' => 1,
                        'ConfirmedRelation' => 1,
                    ];

                    $datSendConf = $DataMyMessage->update($EditDataMyMessage);
                    $responseForUserBss = $DatZebayn->update($EditDataMyMessageZeboune);
                    
                    if($responseForUserBss || $datSendConf) {
                        $DataUpd = $MyProfileNow->MessageEghar()->where('id', $MessageID)->select('id', 'titel', 'message', 'NameUserSendMessage', 'sheckMessage', 'TypeMessage', 'CloceMessage', 'created_at')->latest()->paginate($currentpage);
                        return response()->json([
                            'message' => 'from Send My Response For Message',
                            'data' => $DataUpd,
                            'typAction' => 1
                        ]);
                    } else {
                        $DataUpd = $MyProfileNow->MessageEghar()->where('id', $MessageID)->select('id', 'titel', 'message', 'NameUserSendMessage', 'sheckMessage', 'TypeMessage', 'CloceMessage', 'created_at')->latest()->paginate($currentpage);
                        return response()->json([
                            'message' => 'from Send My Response For Message',
                            'data' => $DataUpd,
                            'typAction' => 2,
                        ]);
                    }
                    
                } else {
                    $DataUpd = $MyProfileNow->MessageEghar()->where('id', $MessageID)->select('id', 'titel', 'message', 'NameUserSendMessage', 'sheckMessage', 'TypeMessage', 'CloceMessage', 'created_at')->latest()->paginate($currentpage);
                    return response()->json([
                        'message' => 'Error On Sore Dont Not What Is This Error Later',
                        'data' => $DataUpd,
                        'typAction' => 2,
                    ]);
                }
            } else {
                return response()->json([
                    'message' => 'Error Semthing Not Found',
                    'data' => [],
                    'typAction' => 6,
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 99,
            ], 200);
        }
    }// End Response For Confirmed My Message To Add In Zeboune Bss

    // Start Response For Confirmed My Message To Add In Zeboune
    function StoreColseMyMessageForaddZeboune(Request $request, $MessageID) {
        try  {
            $MyProfileNow = Auth::user();
            if($MyProfileNow->curret_profile_id ) {
                $request->validate([
                    'currentpagenone' => 'required|integer'
                ]);
                $currentpage = strip_tags($request->currentpagenone) * 10;
                $MessageID = strip_tags($MessageID);
                $MyId = Auth::user()->id;
                $MyName = Auth::user()->username;
                $DataMyMessage = Auth::user()->MessageEghar()->where('id', $MessageID)->select('user_id', 'id', 'ConfirmedRelactionUserZeboune', 'TypeMessage', 'ConfirmedRelation', 'TypeRelationMessageUser', 'idbss')->first();
                
                if($DataMyMessage) {
                    
                    if($DataMyMessage->ConfirmedRelactionUserZeboune == 1) {
                        $DataUpd = $MyProfileNow->MessageEghar()->where('id', $MessageID)->select('id', 'titel', 'message', 'NameUserSendMessage', 'sheckMessage', 'TypeMessage', 'CloceMessage', 'created_at')->latest()->paginate($currentpage);
                        return response()->json([
                            'message' => 'Sorry Your Are One Realy Confirmed Your Message For Add Zeboune',
                            'data' => $DataUpd,
                            'typAction' => 3,
                        ]);
                    } else if($DataMyMessage->ConfirmedRelactionUserZeboune == 2) {
                        $DataUpd = $MyProfileNow->MessageEghar()->where('id', $MessageID)->select('id', 'titel', 'message', 'NameUserSendMessage', 'sheckMessage', 'TypeMessage', 'CloceMessage', 'created_at')->latest()->paginate($currentpage);
                        return response()->json([
                            'message' => 'Sorry Your Are One Realy Dsc Confirmed Your Message For Add Zeboune',
                            'data' => $DataUpd,
                            'typAction' => 4,
                        ]);
                    }

                    $EditDataMyMessage = [
                        'TypeRelationMessageUser' => 2,
                        'TypeMessage' => 'Close',
                        'ConfirmedRelation' => 2
                    ];

                    $dataBssUsID = ProfileUserBss::where('id', $DataMyMessage->idbss)->first()->id;
                    
                    $MyTrave = ZebouneForUser::where([
                        'usernameBss' => $DataMyMessage->idbss,
                        'user_id' => $DataMyMessage->user_id,
                    ])->first();

                    $datSendConf = $DataMyMessage->update($EditDataMyMessage);
                    $responseForUserBss = $MyTrave->delete();
                    
                    if($responseForUserBss || $datSendConf) {
                        $DataUpd = $MyProfileNow->MessageEghar()->where('id', $MessageID)->select('id', 'titel', 'message', 'NameUserSendMessage', 'sheckMessage', 'TypeMessage', 'CloceMessage', 'created_at')->latest()->paginate($currentpage);
                        return response()->json([
                            'message' => 'from Send My Response For Message',
                            'data' => $DataUpd,
                            'typAction' => 1,
                        ]);
                    } else {
                        $DataUpd = $MyProfileNow->MessageEghar()->where('id', $MessageID)->select('id', 'titel', 'message', 'NameUserSendMessage', 'sheckMessage', 'TypeMessage', 'CloceMessage', 'created_at')->latest()->paginate($currentpage);
                        return response()->json([
                            'message' => 'from Send My Response For Message',
                            'data' => $DataUpd,
                            'typAction' => 2,
                        ]);
                    }
                    
                } else {
                    $DataUpd = $MyProfileNow->MessageEghar()->where('id', $MessageID)->select('id', 'titel', 'message', 'NameUserSendMessage', 'sheckMessage', 'TypeMessage', 'CloceMessage', 'created_at')->latest()->paginate($currentpage);
                    return response()->json([
                        'message' => 'Error On Sore Dont Not What Is This Error Later',
                        'data' => $DataUpd,
                        'typAction' => 2,
                    ]);
                }
            } else {
                return response()->json([
                    'message' => 'Error Semthing Not Found',
                    'data' => [],
                    'typAction' => 6,
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 9,
            ], 200);
        }
    }// End Response For Confirmed My Message To Add In Zeboune

    // Start Response For Confirmed My Message To Add In Zeboune
    function StoreDscConfirmedMyRatebeTrave(Request $request, $MessageID) {
        try {
            $MyProfileNow = Auth::user();
            if($MyProfileNow->curret_profile_id ) {
                $MessageID = strip_tags($MessageID);
                $DataMyMessage = $MyProfileNow->MessageEghar()->where('id', $MessageID)->select('id', 'idbss', 'sheckMessage', 'TypeRelationMessageUser', 'TypeMessage')->first();
                
                $MyTrafaye = $MyProfileNow->EdaretMewevin()->where('idbss', $DataMyMessage->idbss)->select('id', 'typerelation', 'typRatibe')->first();
                if($MyTrafaye && $DataMyMessage && $DataMyMessage->sheckMessage == "Ratibe" ) {
                    if($MyTrafaye->typerelation != 1) {
                        $DataUpd = $MyProfileNow->MessageEghar()->select('id', 'titel', 'image', 'message', 'NameUserSendMessage', 'sheckMessage', 'TypeMessage', 'CloceMessage', 'created_at')->latest()->paginate(10);
                        return response()->json([
                            'message' => 'Sorry Your Are One Realy Confirmed Your Message For Add mewve',
                            'typAction' => 12,
                            'data' => $DataUpd,
                        ]);
                    } else if($DataMyMessage->TypeMessage === 'Confirmed') {
                        $DataUpd = $MyProfileNow->MessageEghar()->select('id', 'titel', 'image', 'message', 'NameUserSendMessage', 'sheckMessage', 'TypeMessage', 'CloceMessage', 'created_at')->latest()->paginate(10);
                        return response()->json([
                            'message' => 'Sorry Your Are One Realy Confirmed Your Message For Add mewve',
                            'data' => $DataUpd,
                            'typAction' => 3,
                        ]);
                    } else if($DataMyMessage->TypeMessage === 'Close') {
                        $DataUpd = $MyProfileNow->MessageEghar()->select('id', 'titel', 'image', 'message', 'NameUserSendMessage', 'sheckMessage', 'TypeMessage', 'CloceMessage', 'created_at')->latest()->paginate(10);
                        return response()->json([
                            'message' => 'Sorry Your Are One Realy Dsc Confirmed Your Message For Add mewve',
                            'data' => $DataUpd,
                            'typAction' => 9,
                        ]);
                    }

                    $EditDataMyMessage = [
                        'TypeRelationMessageUser' => 2,
                        'TypeMessage' => "Close"
                    ];
                    
                    $datSendConf = $DataMyMessage->update($EditDataMyMessage);
                    $UpdateMyTrave = $MyTrafaye->update([
                        'typRatibe' => 3
                    ]);
                    
                    if($UpdateMyTrave || $datSendConf) {
                        $DataUpd = $MyProfileNow->MessageEghar()->select('id', 'titel', 'image', 'message', 'NameUserSendMessage', 'sheckMessage', 'TypeMessage', 'CloceMessage', 'created_at')->latest()->paginate(10);
                        return response()->json([
                            'message' => 'SuccessFuly Dsc Confirmed Get Ratibe Mereve',
                            'data' => $DataUpd,
                            'typAction' => 1,
                        ]);
                    } else {
                        $DataUpd = $MyProfileNow->MessageEghar()->select('id', 'titel', 'image', 'message', 'NameUserSendMessage', 'sheckMessage', 'TypeMessage', 'CloceMessage', 'created_at')->latest()->paginate(10);
                        return response()->json([
                            'message' => 'Sorry Semthing Error',
                            'typAction' => 2,
                            'data' => $DataUpd,
                        ]);
                    }
                    
                } else {
                    $DataUpd = $MyProfileNow->MessageEghar()->select('id', 'titel', 'image', 'message', 'NameUserSendMessage', 'sheckMessage', 'TypeMessage', 'CloceMessage', 'created_at')->latest()->paginate(10);
                    return response()->json([
                        'message' => 'Error On Sore Dont Not What Is This Error Later',
                        'data' => $DataUpd,
                        'typAction' => 8,
                    ]);
                }
            } else {
                return response()->json([
                    'message' => 'Error Semthing Not Found',
                    'data' => 6,
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 9,
            ], 200);
        }
    }// End Response For Confirmed My Message To Add In Zeboune

    // Start Response For Confirmed My Message To Add In Zeboune
    function StoreConfirmedMyRatebeTrave($MessageID) {
        try {
            $MyProfileNow = Auth::user();
            if($MyProfileNow->curret_profile_id ) {
                $MessageID = strip_tags($MessageID);
                $DataMyMessage = $MyProfileNow->MessageEghar()->where('id', $MessageID)->select('id', 'idbss', 'sheckMessage', 'TypeRelationMessageUser', 'TypeMessage')->first();
                
                $MyTrafaye = $MyProfileNow->EdaretMewevin()->where('idbss', $DataMyMessage->idbss)->first();
                
                if($MyTrafaye && $DataMyMessage && $DataMyMessage->sheckMessage == "Ratibe" ) {
                    if($MyTrafaye->typerelation != 1) {
                        $DataUpd = $MyProfileNow->MessageEghar()->select('id', 'image', 'titel', 'message', 'NameUserSendMessage', 'sheckMessage', 'TypeMessage', 'CloceMessage', 'created_at')->latest()->paginate(10);
                        return response()->json([
                            'message' => 'Sorry Your Are One Realy Confirmed Your Message For Add mewve',
                            'data' => $DataUpd,
                            'typAction' => 12,
                        ]);
                    } else if($DataMyMessage->TypeMessage === 'Confirmed') {
                        $DataUpd = $MyProfileNow->MessageEghar()->select('id', 'titel', 'image', 'message', 'NameUserSendMessage', 'sheckMessage', 'TypeMessage', 'CloceMessage', 'created_at')->latest()->paginate(10);
                        return response()->json([
                            'message' => 'Sorry Your Are One Realy Confirmed Your Message For Add mewve',
                            'data' => $DataUpd,
                            'typAction' => 3,
                        ]);
                    } else if($DataMyMessage->TypeMessage === 'Close') {
                        $DataUpd = $MyProfileNow->MessageEghar()->select('id', 'titel', 'image', 'message', 'NameUserSendMessage', 'sheckMessage', 'TypeMessage', 'CloceMessage', 'created_at')->latest()->paginate(10);
                        return response()->json([
                            'message' => 'Sorry Your Are One Realy Dsc Confirmed Your Message For Add mewve',
                            'data' => $DataUpd,
                            'typAction' => 9,
                        ]);
                    }

                    $EditDataMyMessage = [
                        'TypeRelationMessageUser' => 1,
                        'TypeMessage' => "Confirmed"
                    ];
                    
                    $datSendConf = $DataMyMessage->update($EditDataMyMessage);
                    $TotelMentNow = $MyTrafaye->totalMenths;
                    $UpdateMyTrave = $MyTrafaye->update([
                        'typRatibe' => 1,
                        'totaledartPayProds' => 0,
                        'totalorders' => 0,
                        'totaledartmaney' => 0,
                        "totalMenths" => $TotelMentNow+1,
                        'totaledartPayEct' => 0,
                        'totaledartPayProds' => 0,
                        'totaledartPayProds' => 0,
                    ]);

                    $CreateSeselMenthPay = EdartPaymentRwatibeMeweves::create([
                        'user_id' => $MyTrafaye->user_id,
                        'idbss' => $MyTrafaye->idbss,
                        "Ratibe" => $MyTrafaye->Ratibe,
                        "curent" => $MyTrafaye->curent,
                        "MentheNow" => $TotelMentNow+1,
                    ]);
                    
                    if($CreateSeselMenthPay && $UpdateMyTrave && $datSendConf) {
                        $DataUpd = $MyProfileNow->MessageEghar()->select('id', 'titel', 'image', 'message', 'NameUserSendMessage', 'sheckMessage', 'TypeMessage', 'CloceMessage', 'created_at')->latest()->paginate(10);
                        return response()->json([
                            'message' => 'SuccessFuly Confirmed Get Ratibe Meweve',
                            'data' => $DataUpd,
                            'typAction' => 1,
                        ]);
                    } else {
                        $DataUpd = $MyProfileNow->MessageEghar()->select('id', 'titel', 'image', 'message', 'NameUserSendMessage', 'sheckMessage', 'TypeMessage', 'CloceMessage', 'created_at')->latest()->paginate(10);
                        return response()->json([
                            'message' => 'Sorry Semthing Error',
                            'data' => $DataUpd,
                            'typAction' => 2,
                        ]);
                    }
                    
                } else {
                    $DataUpd = $MyProfileNow->MessageEghar()->select('id', 'titel', 'image', 'message', 'NameUserSendMessage', 'sheckMessage', 'TypeMessage', 'CloceMessage', 'created_at')->latest()->paginate(10);
                    return response()->json([
                        'message' => 'Error On Sore Dont Not What Is This Error Later',
                        'data' => $DataUpd,
                        'typAction' => 8,
                    ]);
                }
            } else {
                return response()->json([
                    'message' => 'Error Semthing Not Found',
                    'data' => 6,
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 9,
            ], 200);
        }
    }// End Response For Confirmed My Message To Add In Zeboune

    // Start Response For Confirmed My Message To Add In Zeboune
    function StoreConfirmedMyMessageForaddTewve($MessageID) {
        try {
            $MyProfileNow= Auth::user();
            if($MyProfileNow->curret_profile_id ) {
                $MessageID = strip_tags($MessageID);
                $MyId = Auth::user()->id;
                $DataMyMessage = Auth::user()->MessageEghar()->where('id', $MessageID)->first();
                $MyProfile = Auth::user()->ProfileUser()->where('user_id', $MyId)->first();
                $MyName = Auth::user()->username;
                
                if($DataMyMessage) {
                    if($DataMyMessage->sheckMessage == 'tewve') {
                        if($DataMyMessage->TypeMessage === 'Confirmed') {
                            $DataUpd = $MyProfileNow->MessageEghar()->select('id', 'image', 'titel', 'message', 'NameUserSendMessage', 'sheckMessage', 'TypeMessage', 'CloceMessage', 'created_at')->latest()->paginate(10);
                            return response()->json([
                                'message' => 'Sorry Your Are One Realy Confirmed Your Message For Add mewve',
                                'data' => $DataUpd,
                                'typAction' => 3,
                            ]);
                        } else if($DataMyMessage->TypeMessage === 'Close') {
                            $DataUpd = $MyProfileNow->MessageEghar()->select('id', 'image', 'titel', 'message', 'NameUserSendMessage', 'sheckMessage', 'TypeMessage', 'CloceMessage', 'created_at')->latest()->paginate(10);
                            return response()->json([
                                'message' => 'Sorry Your Are One Realy Dsc Confirmed Your Message For Add mewve',
                                'data' => $DataUpd,
                                'typAction' => 4,
                            ]);
                        }

                        $EditDataMyMessage = [
                            'TypeRelationMessageUser' => 1,
                            'TypeMessage' => 'Confirmed',
                            'ConfirmedRelation' => 1
                        ];

                        $MyTrave = Auth::user()->EdaretMewevin()->where('idbss', $DataMyMessage->idbss)->first();

                        $ConfirmedTrave = $MyTrave->update([
                            'typerelation' => 1,
                            'confirmUser' => 1,
                            'img' => $MyProfile->image,
                            'nameMewve' => $MyProfile->name,
                            'numberMewve' => $MyProfile->NumberPhone,
                        ]);
                        
                        $datSendConf = $DataMyMessage->update($EditDataMyMessage);
                        
                        if($ConfirmedTrave && $datSendConf) {
                            $DataUpd = $MyProfileNow->MessageEghar()->select('id', 'image', 'titel', 'message', 'NameUserSendMessage', 'sheckMessage', 'TypeMessage', 'CloceMessage', 'created_at')->latest()->paginate(10);
                            return response()->json([
                                'message' => 'from Send My Response For Message',
                                'data' => $DataUpd,
                                'typAction' => 1,
                            ]);
                        } else {
                            $DataUpd = $MyProfileNow->MessageEghar()->select('id', 'image', 'titel', 'message', 'NameUserSendMessage', 'sheckMessage', 'TypeMessage', 'CloceMessage', 'created_at')->latest()->paginate(10);
                            return response()->json([
                                'message' => 'from Send My Response For Message',
                                'data' => $DataUpd,
                                'typAction' => 2,
                            ]);
                        }
                    } else {
                            $DataUpd = $MyProfileNow->MessageEghar()->select('id', 'image', 'titel', 'message', 'NameUserSendMessage', 'sheckMessage', 'TypeMessage', 'CloceMessage', 'created_at')->latest()->paginate(10);
                            return response()->json([
                                'message' => 'from Send My Response For Message',
                                'data' => $DataUpd,
                                'typAction' => 2,
                            ]);
                        }
                    
                } else {
                    $DataUpd = $MyProfileNow->MessageEghar()->select('id', 'image', 'titel', 'message', 'NameUserSendMessage', 'sheckMessage', 'TypeMessage', 'CloceMessage', 'created_at')->latest()->paginate(10);
                    return response()->json([
                        'message' => 'Error On Sore Dont Not What Is This Error Later',
                        'data' => $DataUpd,
                        'typAction' => 8,
                    ]);
                }
            } else {
                return response()->json([
                    'message' => 'Error Semthing Not Found',
                    'data' => 6,
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 9,
            ], 200);
        }
    }// End Response For Confirmed My Message To Add In Zeboune

    // Start Response For Confirmed My Message To Add In Zeboune
    function StoreColseMyMessageForaddTewve($MessageID) {
        try {
            $MyProfileNow = Auth::user();
            if($MyProfileNow->curret_profile_id ) {
                $MessageID = strip_tags($MessageID);
                $DataMyMessage = $MyProfileNow->MessageEghar()->where('id', $MessageID)->select('id', 'idbss', 'sheckMessage', 'TypeRelationMessageUser', 'TypeMessage')->first();
                
                if($DataMyMessage || $DataMyMessage->sheckMessage == 'tewve' ) {
                    if($DataMyMessage->TypeMessage === 'Confirmed') {
                        $DataUpd = $MyProfileNow->MessageEghar()->select('id', 'image', 'titel', 'message', 'NameUserSendMessage', 'sheckMessage', 'TypeMessage', 'CloceMessage', 'created_at')->latest()->paginate(10);
                        return response()->json([
                            'message' => 'Sorry Your Are One Realy Confirmed Your Message For Add mewve',
                            'data' => $DataUpd,
                            'typAction' => 3,
                        ]);
                    } else if($DataMyMessage->TypeMessage === 'Close') {
                        $DataUpd = $MyProfileNow->MessageEghar()->select('id', 'image', 'titel', 'message', 'NameUserSendMessage', 'sheckMessage', 'TypeMessage', 'CloceMessage', 'created_at')->latest()->paginate(10);
                        return response()->json([
                            'message' => 'Sorry Your Are One Realy Dsc Confirmed Your Message For Add mewve',
                            'data' => $DataUpd,
                            'typAction' => 4,
                        ]);
                    }

                    $EditDataMyMessage = [
                        'TypeRelationMessageUser' => 2,
                        'TypeMessage' => "Close"
                    ];
                    $MyTrafaye = $MyProfileNow->EdaretMewevin()->where('idbss', $DataMyMessage->idbss)->select('id', 'typerelation', 'confirmUser')->first();
                    
                    $datSendConf = $DataMyMessage->update($EditDataMyMessage);
                    $UpdateMyTrave = $MyTrafaye->update([
                        'typerelation' => 3,
                        'confirmUser' => 2
                    ]);
                    
                    if($UpdateMyTrave || $datSendConf) {
                        $DataUpd = $MyProfileNow->MessageEghar()->select('id', 'image', 'titel', 'message', 'NameUserSendMessage', 'sheckMessage', 'TypeMessage', 'CloceMessage', 'created_at')->latest()->paginate(10);
                        return response()->json([
                            'message' => 'from Send My Response For Message',
                            'data' => $DataUpd,
                            'typAction' => 1,
                        ]);
                    } else {
                        $DataUpd = $MyProfileNow->MessageEghar()->select('id', 'image', 'titel', 'message', 'NameUserSendMessage', 'sheckMessage', 'TypeMessage', 'CloceMessage', 'created_at')->latest()->paginate(10);
                        return response()->json([
                            'message' => 'from Send My Response For Message',
                            'data' => $DataUpd,
                            'typAction' => 2,
                        ]);
                    }
                    
                } else {
                    $DataUpd = $MyProfileNow->MessageEghar()->select('id', 'image', 'titel', 'message', 'NameUserSendMessage', 'sheckMessage', 'TypeMessage', 'CloceMessage', 'created_at')->latest()->paginate(10);
                    return response()->json([
                        'message' => 'Error On Sore Dont Not What Is This Error Later',
                        'data' => $DataUpd,
                        'typAction' => 8,
                    ]);
                }
            } else {
                return response()->json([
                    'message' => 'Error Semthing Not Found',
                    'data' => 6,
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 9,
            ], 200);
        }
    }// End Response For Confirmed My Message To Add In Zeboune

    // Start Get Date User In Type Sereach Uset
    function StartSereachForUserData($usernameSereach) {
        try {
            $usernameSereach = strip_tags($usernameSereach);
            $idProfileBssNow = Auth::user()->curret_profile_id_Bss;

            $ProfileBssLoginNow = Auth::user()->ProfileUserBss()->where('id', $idProfileBssNow)->select('id')->first();
            if($ProfileBssLoginNow) {
                $data = ProfileUser::where('name', 'LIKE', $usernameSereach. '%')->select('id', 'user_id', 'name', 'image', 'NumberPhone')->latest()->get();
                if($data){
                    $datSheckUser = [];
                    $nameBssNowTrav = "";
                    $idMyTrave = "";
                    foreach ($data as $index => $OnUser) {
                        $nameBss = '';
                        $DatProftrv = EdaretMewevin::where([
                            'user_id' => $OnUser->user_id,
                            'confirmUser' => 1,
                            'confirmBss' => 1,
                            'typerelation' => 1,
                        ])->select('id', 'idbss')->first();
                        if($DatProftrv !== null && $DatProftrv->idbss !== null) {
                            $DatProfBssT = ProfileUserBss::where('id', $DatProftrv->idbss)->first();
                            $nameBssNowTrav = $DatProfBssT->usernameBss;
                            $idMyTrave = $DatProfBssT->id;
                        }
                        $SeckUserBss = ProfileUserBss::where('user_id', $OnUser->user_id)->select('id', 'user_id')->first();
                        $datSheckUser[] = [
                            'TypTewevBss' => $SeckUserBss != null && $SeckUserBss->user_id ==  $OnUser->user_id ? 1 : 0,
                            'firstBssNow' => $nameBssNowTrav,
                            'SeckUserBss' => $SeckUserBss ? 1 : 0,
                            'id' => $index+1,
                            'user_id' => $OnUser->user_id,
                            'image' => $OnUser->image,
                            'iDMyprodBss' => $idProfileBssNow,
                            'SheckTravForMy' => $idMyTrave,
                            'name' => $OnUser->name,
                            'NumberPhone' => $OnUser->NumberPhone,
                        ];
                    }
                    return response()->json([
                        'message' => 'from Sereach User Name',
                        'data' => $datSheckUser,
                        'typAction' => 1,
                    ]);
                } else {
                    return response()->json([
                        'message' => 'No Data For This Name User',
                        'typAction' => 91,
                        'data' => [],
                    ]);
                }
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 9,
            ], 200);
        }
    } // End Get Date User In Type Sereach Uset

    function SendMyMessageToEnthUser(Request $request) {
        try {
            $request->validate([
                'TypeSendMessage' => 'required|string',
            ]);
            $TypeSendMessage = strip_tags($request->TypeSendMessage);

            if($TypeSendMessage == 'AddZeboune') {
                return response()->json([
                    'message' => 'from Send Get Message'
                ]);
            } else if($TypeSendMessage == 'AddFreind') {
                return response()->json([
                    'message' => 'from Send Get Message'
                ]);
            } else if($TypeSendMessage == 'AddSerake') {
                return response()->json([
                    'message' => 'from Send Get Message'
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 9,
            ], 200);
        }
    }

    // Start Alls Action For Edart Zebaynes Bss
     // Start Add Zeboune For User Bss
    function AddZebouneForUseBss(Request $request) {
        try {
            $MyProfileNow = Auth::user();
            $idProfileBssNow = $MyProfileNow->curret_profile_id_Bss;
            $ProfileBssLoginNow = $MyProfileNow->ProfileUserBss()->where('id', $idProfileBssNow)->first();
            if($ProfileBssLoginNow) {
                $request->validate([
                    'TypeAddZeboune' => 'required|string',
                ]);
                $TypeAddZeboune = strip_tags($request->TypeAddZeboune);
                if($TypeAddZeboune === 'create_Use') {
                    $request->validate([
                        'username' => 'required|string',
                        'image' => 'nullable|image|mimes:png,jpg',
                        'numberPhone' => 'nullable|string',
                        'TypeAddZeboune' => 'required|string',
                    ]);
                    $username = strip_tags($request->username);
                    $numberPhone = strip_tags($request->numberPhone);
                    $SheckZeboune = ZebouneForUser::where([
                        'usernameBss' => $idProfileBssNow,
                        'numberPhone' => $numberPhone,
                        ])->select('id')->first();
                    if($SheckZeboune) {
                        return response()->json([
                            'message' => 'This User For Number Ase On Reled add For Your Zebayn',
                            'typAction' => 2,
                            'data' => [],
                        ]);
                    }
                    $dateZebouneTCreate = [
                        'user_id' => $MyProfileNow->id,
                        'username' => $username,
                        'numberPhone' => $numberPhone,
                        'usernameBss' => $idProfileBssNow,
                        'TypeAccounte' => 'create_Use',
                        'ConfirmedRelactionUserBss' => 1,
                        'ConfirmedRelactionUserZeboune' => 1,
                        'ConfirmedRelation' => 1,
                        'HaletDeyn' => 2,
                    ];
                    if($request->hasFile('image')) {
                        $image = $request->file('image');
                        $gen = hexdec(uniqid());
                        $ext = strtolower($image->getClientOriginalExtension());
                        $namePod = $gen . '' . $ext;
                        $location = 'user-prodect/';
                        $source = $location.$namePod;
                        $name = $gen. '.' .$ext;
                        $source = $location.$name;
                        $image->move($location,$name);
                        $dateZebouneTCreate['image'] = $source;
                    }
                    $ZebouneForUserDate = ZebouneForUser::create($dateZebouneTCreate);
                    if($ZebouneForUserDate) {
                        return response()->json([
                            'message' => 'SuccessFuly Create Zeboune',
                            'data' => [],
                            "typAction" => 1,
                        ]);
                    } else {
                        return response()->json([
                            'message' => 'Sorr One Get Semthiong Error',
                            'typAction' => 7,
                            'data' => [],
                        ]);
                    }
                } else if($TypeAddZeboune === 'Online') {
                    $request->validate([
                        'IdUser' => 'required|numeric',
                        'numberPhone' => 'nullable|string',
                        'passwordSetting' => 'required|string',
                    ]);
                    $shekpas = $MyProfileNow->UserPasswordStting()->where('idbss', $idProfileBssNow)->select('id', 'password')->first();
                    $passwordStingHa = strip_tags($request->passwordSetting);
                    $passwordStingHaahing = Hash::make($passwordStingHa);
                    $passwordHash = $shekpas->password;
                    $redPassword = request('passwordSetting');
                    if($passwordHash) {
                        if(Hash::check($redPassword, $passwordHash)) {
                            $IdUser = strip_tags($request->IdUser);
                            $SheckUserProfileDate = ProfileUser::where('user_id', $IdUser)->select('id', 'user_id', 'NumberPhone', 'name', 'image')->first();
                            $sheclNupmerUser = ZebouneForUser::where([
                                'usernameBss' => $idProfileBssNow,
                                'user_id' => $SheckUserProfileDate->user_id,
                                'numberPhone' => $SheckUserProfileDate->NumberPhone,
                            ])->select('id', 'ConfirmedRelation')->first();
                            if($sheclNupmerUser && $sheclNupmerUser->ConfirmedRelation == 0) {
                                return response()->json([
                                    'message' => 'Plz Wailt User Send Message Or Relation In First Your Message',
                                    'typAction' => 7,
                                    'data' => [],
                                ]);
                            } else if($sheclNupmerUser && $sheclNupmerUser->ConfirmedRelation == 1) {
                                return response()->json([
                                    'message' => 'Sory Your Are On Relay Create This User For Yhe Number Or UserName',
                                    'data' => [],
                                    'typAction' => 3,
                                ]);
                            }
                            $dateZebouneTCreate = [
                                'user_id' => $SheckUserProfileDate->user_id,
                                'username' => $SheckUserProfileDate->name,
                                'numberPhone' => $SheckUserProfileDate->NumberPhone,
                                'usernameBss' => $idProfileBssNow,
                                'TypeAccounte' => 'Online',
                                'ConfirmedRelation' => 0,
                                'ConfirmedRelactionUserBss' => 1,
                                'image' => $SheckUserProfileDate->image,
                            ];
                            $CreateMyZebouneDat = ZebouneForUser::create($dateZebouneTCreate);
                            $dataSendMessage = MessageEghar::create([
                                'user_id' => $IdUser,
                                'titel' => 'طلب تكوين علاقة زبائنية ',
                                'sheckMessage' => 'zeboune',
                                'message' => "لفد تم اختيارك من طرف $ProfileBssLoginNow->usernameBss لاضافتك مع قائمة زبائنية و تسهبل لمعاملاتكم لبينية ",
                                'NameUserSendMessage' => $ProfileBssLoginNow->usernameBss,
                                'TypeAccountSendMessage' => 'bss',
                                'image' => $ProfileBssLoginNow->image,
                                'TypeRelationMessageUserSend' => 1,
                                'TypeMessage' => 'Waite',
                                'idbss' => $idProfileBssNow,
                            ]);
                            if($CreateMyZebouneDat && $dataSendMessage) {
                                return response()->json([
                                    'message' => 'seccesFuly Send For User In Message To Confirmed Relation',
                                    'typAction' => 1,
                                    'data' => [],
                                ]);
                            } else {
                                return response()->json([
                                    'message' => 'Error To Send For User In Message To Confirmed Relation',
                                    'typAction' => 3,
                                    'data' => [],
                                ]);
                            }
                        } else {
                            return response()->json([
                                'message' => "Sorry Your Password Setting Is Not Correct",
                                'typAction' => 8,
                                'data' => [],
                            ], 200);
                        } 
                    } else {
                        return response()->json([
                            'message' => "Sorry Your Are Not Have Password Setting Plz Created",
                            'data' => [],
                            'typAction' => 9,
                        ], 200);
                    } 
                } else {
                    return response()->json([
                        'message' => 'Error Not Found',
                        'data' => 75,
                    ]);
                }
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 9,
            ], 200);
        }
    } // End Add Zeboune For User Bss
    
    // Start Show Alls My Data Zebounes Bss 
    function GetAllMyZebounes(Request $request) {
        try {
            $ProfilNow = Auth::user();
            $SheckMyProfID = $ProfilNow->curret_profile_id_Bss;
            $SheckMyProfIDBss = $ProfilNow->curret_profile_id_Bss;
            $MyProfileBss = $ProfilNow->ProfileUserBss()->where('id', $SheckMyProfIDBss)->select('id')->first();
            if($MyProfileBss) {
                $data = ZebouneForUser::where([
                    'usernameBss' => $SheckMyProfID,
                    'ConfirmedRelactionUserBss' => 1,
                ])->select('id', 'username', 'numberPhone', 'ConfirmedRelation', 'TotelDeyn', 'HaletDeyn', 'TypeAccounte')->latest()->paginate(10);
                if($data) {
                    return response()->json([
                        'message' => 'From Show All My Zeboune',
                        'typAction' => 1,
                        'data' => $data,
                    ]);
                } else {
                    return response()->json([
                        'message' => 'Error Semthing Error Not Found',
                        'typAction' => 6,
                        'data' => [],
                    ]);
                } 
            } else {
                return response()->json([
                    'message' => 'Error Semthing Error Not Found',
                    'data' => 7,
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 9,
            ], 200);
        }
    } // End Show Alls My Data Zebounes Bss

    // Start Show All My Zeboune Data To Seck Click
    function GetAllMyZebounesToSheckClick(Request $request) {
        try {
            $SheckMyProfID = Auth::user()->curret_profile_id_Bss;
            $MyProfile = Auth::user()->ProfileUserBss()->where('id', $SheckMyProfID)->first();

            if($MyProfile) {
            $data = ZebouneForUser::where('usernameBss', $SheckMyProfID)->latest()->get();
            return response()->json([
                'message' => 'From Show All My Zeboune',
                'data' => $data,
            ]);
            } else {
                return response()->json([
                    'message' => 'Error Semthing Is Not Found',
                    'data' => 2,
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 9,
            ], 200);
        }
    } // End Show All My Zeboune Data To Seck Click

    // Start Sereach My Zeboune Data To Seck Click
    function ShowMyZebouneForSereachNumberFphone(Request $request, $NumberPhoneZeb) {
        try {
            $ProfileNOw = Auth::user();
            $SheckMyProfID = $ProfileNOw->curret_profile_id_Bss;
            $MyProfile = $ProfileNOw->ProfileUserBss()->where('id', $SheckMyProfID)->select('id')->first();
            if($MyProfile) {
                $NumberZebouneToSereach = strip_tags($NumberPhoneZeb);
                $data = ZebouneForUser::where([
                    'usernameBss' => $SheckMyProfID,
                    'numberPhone' => $NumberZebouneToSereach,
                ])->select('id', 'username', 'numberPhone', 'ConfirmedRelation', 'TotelDeyn', 'HaletDeyn', 'TypeAccounte')->latest()->paginate(10);
                return response()->json([
                    'message' => 'From Show All My Zeboune',
                    'data' => $data,
                ]);
            } else {
                return response()->json([
                    'message' => 'Error Semthing Is Not Found',
                    'data' => 2,
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 9,
            ], 200);
        }
    } // End Sereach My Zeboune Data To Seck Click

    // Start Get Date User In Type Sereach To Add Zeboune
    function StartSereachForUserToAddZ($usernameSereach) {
        try {
            $usernameSereach = strip_tags($usernameSereach);
            $ProfileData = Auth::user();
            $idProfileBssNow = $ProfileData->curret_profile_id_Bss;
            $ProfileBssLoginNow = $ProfileData->ProfileUserBss()->where('id', $idProfileBssNow)->select('id')->first();
            if($ProfileBssLoginNow) {
                $datSheckUser = [];
                $nameBssNowTrav = "";
                $data = ProfileUser::where('name', 'LIKE', $usernameSereach. '%')->select('user_id', 'image', 'name', 'NumberPhone')->latest()->get();
                if($data){
                    foreach ($data as $index => $OnUser) {
                        $nameBss = '';
                        $DatMZeboune = ZebouneForUser::where([
                            'user_id' => $OnUser->user_id,
                            'usernameBss' => $idProfileBssNow,
                            'TypeAccounte' => 'Online',
                            'ConfirmedRelactionUserBss' => 1,
                            ])->select('id', 'ConfirmedRelation')->first();
                            $DatProftrv = EdaretMewevin::where([
                            'user_id' => $OnUser->user_id,
                            'idbss' => $idProfileBssNow,
                            'confirmUser' => 1,
                            'confirmBss' => 1,
                            'typerelation' => 1,
                        ])->select('id', 'idbss')->first();
                        if($DatProftrv !== null) {
                            $DatProfBssT = ProfileUserBss::where('id', $DatProftrv->idbss)->first();
                            $nameBssNowTrav = $DatProfBssT->usernameBss;
                        }
                        $SeckUserBss = ProfileUserBss::where('user_id', $OnUser->user_id)->select('id')->first();
                        if($DatMZeboune != null && $DatMZeboune->ConfirmedRelation == 1) {
                            $SheckZ = 1;
                        } else if($DatMZeboune != null && $DatMZeboune->ConfirmedRelation == 0) {
                            $SheckZ = 2;
                        } else {
                            $SheckZ = 0;
                        }

                        $datSheckUser[] = [
                            'TypZebouneBss' => $SheckZ,
                            'firstBssNow' => $nameBssNowTrav,
                            'SeckUserBss' => $SeckUserBss ? 1 : 0,
                            'id' => $index+1,
                            'user_id' => $OnUser->user_id,
                            'image' => $OnUser->image,
                            'iDMyprodBss' => $idProfileBssNow,
                            'name' => $OnUser->name,
                            'NumberPhone' => $OnUser->NumberPhone,
                        ];
                    }
                    return response()->json([
                        'message' => 'from Sereach User Name',
                        'data' => $datSheckUser,
                        'typAction' => 1
                    ]);
                } else {
                    return response()->json([
                        'message' => 'No Data For This Name User',
                        'typAction' => 4,
                        'data' => [],
                    ]);
                }
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 9,
            ], 200);
        }
    } // End Get Date User In Type Sereach To Add Zeboune

    // Start Show Date My Zeboune For ID
    function ShowMyZebouneDataForID($IdZebouneIClick) {
        try {
            $IdZebouneSmlIClick = strip_tags($IdZebouneIClick);
            $ProfilNow = Auth::user();
            $SheckMyProfIDBss = $ProfilNow->curret_profile_id_Bss;
            $MyProfileBss = $ProfilNow->ProfileUserBss()->where('id', $SheckMyProfIDBss)->select('id')->first();
            if($MyProfileBss) {
                $MyZebouneForID = ZebouneForUser::where([
                    'usernameBss' => $SheckMyProfIDBss,
                    'id' => $IdZebouneSmlIClick
                ])->select('id', 'username', 'numberPhone', 'TotelBayMent', 'created_at', 'image', 'TotelDeyn', 'HaletDeyn', 'TypeAccounte')->first();
                $TotelPaymentForZeboune = $ProfilNow->CurrentPaymentForUseBss->select('currentCantry')->first();
                return response()->json([
                    'message' => 'from Show My Data Zeboune',
                    'typAction' => 1,
                    'data' => $MyZebouneForID,
                    'TotelPaymentForZeboune' => $TotelPaymentForZeboune
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 99,
            ], 200);
        }
    } //  End Show Date My Zeboune For ID

    // Start Active Deyn For My Zeboune
    function StartActiveDeunFoeMyZeboune(Request $request, $ZebouneID) {
        try {
            $ProfileNow = Auth::user();
            $SheckMyProfIDBss = $ProfileNow->curret_profile_id_Bss;
            $MyProfileBss = $ProfileNow->ProfileUserBss()->where('id', $SheckMyProfIDBss)->select('id', 'usernameBss', 'image')->first();
            if($MyProfileBss) {
                $request->validate([
                    'passwordSetting' => 'required|string',
                ]);
                $shekpas = $ProfileNow->UserPasswordStting()->where('idbss', $SheckMyProfIDBss)->select('id', 'password')->first();
                if($shekpas == null) {
                    return response()->json([
                        'message' => 'Your`e Not Have Eny Password Setting Son Plz Go To Create',
                        'data' => 5
                    ]);
                }
                $passwordStingHa = strip_tags($request->passwordSetting);
                $passwordStingHaahing = Hash::make($passwordStingHa);
                $passwordHash = $shekpas->password;
                $ZebouneIDSmpl = strip_tags($ZebouneID);
                $redPassword = request('passwordSetting');
                if($passwordHash) {
                    if(Hash::check($redPassword, $passwordHash)) {
                        $DataZebouneUpdat = ZebouneForUser::where([
                            'usernameBss' => $SheckMyProfIDBss,
                            'id' => $ZebouneIDSmpl,
                        ])->select('id', 'HaletDeyn', 'TypeAccounte', 'user_id', 'ConfirmedRelation', 'ConfirmedRelactionUserBss')->first();
                        if($DataZebouneUpdat->ConfirmedRelation != 1 || $DataZebouneUpdat->ConfirmedRelactionUserBss != 1) {
                            $dataUpdat = ZebouneForUser::where('usernameBss', $SheckMyProfIDBss)->select('id', 'username', 'numberPhone', 'ConfirmedRelation', 'TotelDeyn', 'HaletDeyn', 'TypeAccounte')->latest()->paginate(10);
                            return response()->json([
                                'message' => 'Sorryn Your Are One Realy Confirmed Deyne For This Zeboune',
                                'typAction' => 12,
                                'data' => $dataUpdat,
                            ], 201);
                        } else if($DataZebouneUpdat->HaletDeyn == 1) {
                            $dataUpdat = ZebouneForUser::where('usernameBss', $SheckMyProfIDBss)->select('id', 'username', 'numberPhone', 'ConfirmedRelation', 'TotelDeyn', 'HaletDeyn', 'TypeAccounte')->latest()->paginate(10);
                            return response()->json([
                                'message' => 'Sorryn Your Are One Realy Confirmed Deyne For This Zeboune',
                                'typAction' => 8,
                                'data' => $dataUpdat,
                            ], 201);
                        }
                        if($DataZebouneUpdat->TypeAccounte === "Online") {
                            $dataSendMessage = MessageEghar::create([
                                'user_id' => $DataZebouneUpdat->user_id,
                                'titel' => "تنبيه لقد سمح لك تاجر $MyProfileBss->usernameBss صلاحية الدين",
                                'sheckMessage' => 'DeyneZeboune',
                                'message' => "لقد قام تاجر $MyProfileBss->usernameBss بسماح لك صلاحية الدين حيث يمكنك استعمالها حاليا  ",
                                'NameUserSendMessage' => $MyProfileBss->usernameBss,
                                'TypeAccountSendMessage' => 'bss',
                                'image' => $MyProfileBss->image,
                                'TypeRelationMessageUserSend' => 1,
                                'TypeMessage' => 'Confirmed',
                                'idbss' => $MyProfileBss->id,
                            ]);
                        }
                        $UpdateDatZ = $DataZebouneUpdat->update([
                            'HaletDeyn' => 1,
                        ]);
                        if($UpdateDatZ) {
                            $dataUpdat = ZebouneForUser::where('usernameBss', $SheckMyProfIDBss)->latest()->paginate(10);
                            return response()->json([
                                'message' => 'from Active My Zeboune To Deyn',
                                'typAction' => 1,
                                'data' => $dataUpdat,
                            ], 201);
                        }
                    } else {
                        return response()->json([
                            'message' => 'Sory This Password Not Correct',
                            'typAction' => 7,
                            'data' => [],
                        ], 200);
                    }
                } else {
                    return response()->json([
                        'message' => 'Sory Error Semthing Not Found',
                        'typAction' => 5,
                        'data' => [],
                    ], 404);
                }
                
            } else {
                return response()->json([
                    'message' => 'Your`e Not Have Eny Password Setting Son Plz Go To Create',
                    'data' => 12
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 99,
            ], 200);
        }
    } //== End Active Deyn For My Zeboune ==//

    // Start Dsc Active Deyn For My Zeboune
    function StopDeynForMyZeboune(Request $request, $ZebouneID) {
        try {
            $DataProfile = Auth::user();
            $SheckMyProfIDBss = $DataProfile->curret_profile_id_Bss;
            $MyProfileBss = $DataProfile->ProfileUserBss()->where('id', $SheckMyProfIDBss)->select('id', 'usernameBss', 'image')->first();
            if($MyProfileBss) {
                $request->validate([
                    'passwordSetting' => 'required|string',
                ]);
                $shekpas = $DataProfile->UserPasswordStting()->where('idbss', $SheckMyProfIDBss)->select('id', 'password')->first();
                if($shekpas === null) {
                    return response()->json([
                        'message' => 'Your`e Not Have Eny Password Setting Son Plz Go To Create',
                        'typAction' => 5,
                        'data' => [],
                    ]);
                }
                $passwordStingHa = strip_tags($request->passwordSetting);
                $passwordStingHaahing = Hash::make($passwordStingHa);
                $passwordHash = $shekpas->password;
                $ZebouneIDSmpl = strip_tags($ZebouneID);
                $redPassword = request('passwordSetting');

                if($passwordHash) {
                    if(Hash::check($redPassword, $passwordHash)) {
                        $DataZebouneUpdat = ZebouneForUser::where([
                            'usernameBss' => $SheckMyProfIDBss,
                            'id' => $ZebouneIDSmpl,
                        ])->select('id', 'HaletDeyn', 'TypeAccounte', 'user_id', 'ConfirmedRelation', 'ConfirmedRelactionUserBss')->first();
                        
                        if($DataZebouneUpdat->ConfirmedRelation != 1 || $DataZebouneUpdat->ConfirmedRelactionUserBss != 1) {
                            $dataUpdat = ZebouneForUser::where('usernameBss', $SheckMyProfIDBss)->select('id', 'username', 'numberPhone', 'ConfirmedRelation', 'TotelDeyn', 'HaletDeyn', 'TypeAccounte')->latest()->paginate(10);
                            return response()->json([
                                'message' => 'Sorryn Your Are One Realy Confirmed Deyne For This Zeboune',
                                'typAction' => 12,
                                'data' => $dataUpdat,
                            ], 201);
                        } else if($DataZebouneUpdat->HaletDeyn == 2) {
                            $dataUpdat = ZebouneForUser::where('usernameBss', $SheckMyProfIDBss)->latest()->paginate(10);
                            return response()->json([
                                'message' => 'Sorry Your Are One Realy Dsc Active Deyeyne For Zeboune',
                                'typAction' => 8,
                                'data' => $dataUpdat,
                            ], 201);
                        }
                        if($DataZebouneUpdat->TypeAccounte === "Online") {
                            $dataSendMessage = MessageEghar::create([
                                'user_id' => $DataZebouneUpdat->user_id,
                                'titel' => "تنبيه لقد اوقف عنك تاجر $MyProfileBss->usernameBss صلاحية الدين",
                                'sheckMessage' => 'DeyneZeboune',
                                'message' => "لقد قام تاجر $MyProfileBss->usernameBss بايقاف صلاحية الدين حيث لم تعد متاح لك الى حين اشعار اخر للمزيد تواصل مع تاجر  ",
                                'NameUserSendMessage' => $MyProfileBss->usernameBss,
                                'TypeAccountSendMessage' => 'bss',
                                'image' => $MyProfileBss->image,
                                'TypeRelationMessageUserSend' => 1,
                                'TypeMessage' => 'Confirmed',
                                'idbss' => $MyProfileBss->id,
                            ]);
                        }
                        $UpdateDatZ = $DataZebouneUpdat->update([
                            'HaletDeyn' => 2,
                        ]);
                        if($UpdateDatZ) {
                            $dataUpdat = ZebouneForUser::where('usernameBss', $SheckMyProfIDBss)->latest()->paginate(10);
                            return response()->json([
                                'message' => 'from Active My Zeboune To Deyn',
                                'typAction' => 1,
                                'data' => $dataUpdat,
                            ], 201);
                        }
                    } else {
                        return response()->json([
                            'message' => 'Sory This Password Not Correct',
                            'typAction' => 7,
                            'data' => [],
                        ], 200);
                    }
                } else {
                    return response()->json([
                        'message' => 'Sory Error Semthing Not Found',
                        'typAction' => 5,
                        'data' => [],
                    ], 404);
                }
            } else {
                return response()->json([
                    'message' => 'Your`e Not Have Eny Password Setting Son Plz Go To Create',
                    'data' => 12
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 99,
            ], 200);
        }
    } // End Dsc Active Deyn For My Zeboune

    // Start Update Deyn For My Zeboune
    function StartUpdateDeynForMyZeboune(Request $request, $ZebouneID) {
        try {
            $DatProfile = Auth::user();
            $SheckMyProfIDBss = $DatProfile->curret_profile_id_Bss;
            $MyProfileBss = $DatProfile->ProfileUserBss()->where('id', $SheckMyProfIDBss)->select('id', 'usernameBss', 'image')->first();
            if($MyProfileBss) {
                $request->validate([
                    'passwordSetting' => 'required|string',
                    'deynUpdate' => 'required|string',
                ]);
                $shekpas = $DatProfile->UserPasswordStting()->where('idbss', $SheckMyProfIDBss)->select('id', 'password')->first();
                if($shekpas === null) {
                    return response()->json([
                        'message' => 'Your`e Not Have Eny Password Setting Son Plz Go To Create',
                        'typAction' => 5,
                        'data' => [],
                    ]);
                }
                $passwordStingHa = strip_tags($request->passwordSetting);
                $passwordStingHaahing = Hash::make($passwordStingHa);
                $passwordHash = $shekpas->password;
                $ZebouneIDSmpl = strip_tags($ZebouneID);
                $DeynNowUd = strip_tags($request->deynUpdate);
                $redPassword = request('passwordSetting');
                if($passwordHash) {
                    if(Hash::check($redPassword, $passwordHash)) {
                        $DataZebouneUpdat = ZebouneForUser::where([
                            'usernameBss' => $SheckMyProfIDBss,
                            'id' => $ZebouneIDSmpl,
                        ])->select('id', 'TotelDeyn', 'TypeAccounte', 'user_id', 'ConfirmedRelation', 'ConfirmedRelactionUserBss')->first();
                        if($DataZebouneUpdat->ConfirmedRelation != 1 || $DataZebouneUpdat->ConfirmedRelactionUserBss != 1) {
                            $dataUpdat = ZebouneForUser::where('usernameBss', $SheckMyProfIDBss)->select('id', 'username', 'numberPhone', 'ConfirmedRelation', 'TotelDeyn', 'HaletDeyn', 'TypeAccounte')->latest()->paginate(10);
                            return response()->json([
                                'message' => 'Sorryn Your Are One Realy Confirmed Deyne For This Zeboune',
                                'typAction' => 12,
                                'data' => $dataUpdat,
                            ], 201);
                        } else if($DataZebouneUpdat->ConfirmedRelation != 1) {
                            $dataUpdat = ZebouneForUser::where('usernameBss', $SheckMyProfIDBss)->select('id', 'username', 'numberPhone', 'ConfirmedRelation', 'TotelDeyn', 'HaletDeyn', 'TypeAccounte')->latest()->paginate(10);
                            return response()->json([
                                'message' => 'Sorry Plz Late Youser Confiremd Relation First Aboute To Do This Action',
                                'typAction' => 9,
                                'data' => $dataUpdat,
                            ], 201);
                        }
                        if($DataZebouneUpdat->TypeAccounte === "Online") {
                            $dataSendMessage = MessageEghar::create([
                                'user_id' => $DataZebouneUpdat->user_id,
                                'titel' => "تنبيه تعديل لحالت لدين مرتبط بك مع تاجر $MyProfileBss->usernameBss",
                                'sheckMessage' => 'DeyneZeboune',
                                'message' => "لقد قام تاجر $MyProfileBss->usernameBss بتعديل حالت الدين و لتي كانت $DataZebouneUpdat->TotelDeyn لتصبح الان $DeynNowUd للمزيد من معلومات تواصل مع تاجر و شكرا  ",
                                'NameUserSendMessage' => $MyProfileBss->usernameBss,
                                'TypeAccountSendMessage' => 'bss',
                                'image' => $MyProfileBss->image,
                                'TypeRelationMessageUserSend' => 1,
                                'TypeMessage' => 'Confirmed',
                                'idbss' => $MyProfileBss->id,
                            ]);
                        }
                        $confupdate = $DataZebouneUpdat->update([
                            'TotelDeyn' => $DeynNowUd,
                        ]);
                        if($confupdate) {
                            $dataUpdat = ZebouneForUser::where('usernameBss', $SheckMyProfIDBss)->select('id', 'username', 'numberPhone', 'ConfirmedRelation', 'TotelDeyn', 'HaletDeyn', 'TypeAccounte')->latest()->paginate(10);
                            return response()->json([
                                'message' => 'from Active My Zeboune To Deyn',
                                'typAction' => 1,
                                'data' => $dataUpdat,
                            ], 201);
                        }
                    } else {
                        return response()->json([
                            'message' => 'Sory This Password Not Correct',
                            'typAction' => 7,
                            'data' => [],
                        ], 200);
                    }
                } else {
                    return response()->json([
                        'message' => 'Sory Error Semthing Not Found',
                        'typAction' => 5,
                        'data' => [],
                    ], 404);
                }
                
            } else {
                return response()->json([
                    'message' => 'Your`e Not Have Eny Password Setting Son Plz Go To Create',
                    'data' => 12
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 99,
            ], 200);
        }
    } //== End Update Deyn For My Zeboune ==//
    // End Alls Action For Edart Zebaynes Bss ==//

    function switchProfile(Request $request) {
        try {
            if(Auth::check()){
                $request->validate([
                    'profileID' => 'required|string',
                    'TypeProfile' => 'required|string',
                ]);
                
                $profileID = strip_tags($request->profileID);
                $TypeProfile = strip_tags($request->TypeProfile);
                $user = Auth::user();

                if($TypeProfile === 'user') {
                    $MyProfile = $user->ProfileUser()->select('id', 'user_id')->first();
                    if(!$MyProfile) {
                        return response()->json([
                            'message' => 'Sorry Error Not Found',
                            'typAction' => 6,
                        ]);
                    }
                    $user->update([
                        'curret_profile_id' => $MyProfile->user_id,
                        'curret_profile_id_Bss' => '',
                        'current_my_travel' => ''
                    ]);
                    $AllsDataProfileNow = $this->StartGetAllsDataProfileSmpl();
                    $token = $user->createToken('user_token')->plainTextToken;
                    return response()->json([
                        'message' => 'Sussefuly Login My Profile',
                        'data' => $AllsDataProfileNow->getData(),
                        'typAction' => 1,
                        'token' => $token,
                    ]);
                } else if($TypeProfile === 'bss') {
                    $MyProfileBss = $user->ProfileUserBss()->where('id', $profileID)->select('id')->first();
                    if(!$MyProfileBss) {
                        return response()->json([
                            'message' => 'Sorry Error Not Found',
                            'typAction' => 5,
                        ]);
                    }
                        $user->update([
                            'curret_profile_id_Bss' => $MyProfileBss->id,
                            'curret_profile_id' => '',
                            'current_my_travel' => '',
                        ]);
                        
                        $AllsDataProfileNow = $this->GetAllsDataMyProfileBssNow();
                        $token = $user->createToken('user_token')->plainTextToken;
                        return response()->json([
                            'message' => 'My Profile Login',
                            'data' => $AllsDataProfileNow->getData(),
                            'typAction' => 1,
                            'token' => $token,
                        ]);
                    
                } else if($TypeProfile === 'teweve') {
                    $MyTravel = $user->EdaretMewevin()->where([
                        'idbss' => $profileID,
                        'confirmUser' => 1,
                        'confirmBss' => 1,
                        'typerelation' => 1,
                    ])->select('idbss')->first();
                    if(!$MyTravel) {
                        return response()->json([
                            'message' => 'Sorry Error Not Found',
                            'typAction' => 7,
                        ]);
                    }
                    $user->update([
                        'current_my_travel' => $MyTravel->idbss,
                        'curret_profile_id_Bss' => '',
                        'curret_profile_id' => '',
                    ]);
                    
                    $AllsDataProfileNow = $this->GetAllsDataMyProfileTrave();
                    $token = $user->createToken('user_token')->plainTextToken;
                    return response()->json([
                        'message' => 'My Profile Login',
                        'data' => $AllsDataProfileNow->getData(),
                        'typAction' => 1,
                        'token' => $token,
                    ]);
                }
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 99,
            ], 200);
        }
    }

    function ShowMyProfileLoginNow() {
        try {
            $MyProfileNow= Auth::user();
            if($MyProfileNow->curret_profile_id) {
                $MyProf = Auth::user()->ProfileUser()->where('user_id', $MyProfileNow->curret_profile_id)->first();
                return response()->json([
                    'message' => 'Sussefuly Login My Profile User',
                    'TypeAcounte' => 'user',
                ]);
            } else if($MyProfileNow->curret_profile_id_Bss) {
                $MyProf = Auth::user()->ProfileUserBss()->where('id', $MyProfileNow->curret_profile_id_Bss)->first();
                return response()->json([
                    'message' => 'Sussefuly Show My Profile Bss',
                    'TypeAcounte' => 'bss',
                ]);
            } else {
                $userup = User::where('id', $MyProfileNow->id)->update([
                    'curret_profile_id' => $MyProfileNow->id,
                    'curret_profile_id_Bss' => null
                ]);
            }
            $userup = User::where('id', $MyProfileNow->id)->update([
                'curret_profile_id' => $MyProfileNow->id,
                'curret_profile_id_Bss' => null
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 99,
            ], 200);
        }
    }

    // Start Get Alls Data Profile User Smpl Now
    function StartGetAllsDataProfileSmpl($user = null) {
        try {
            $MyDatUser = $user ?: Auth::user();
            $MyProfileUser = $MyDatUser->ProfileUser()->select('id', 'user_id', 'name', 'image', 'NumberPhone', 'cantry', 'address', 'city', 'Gender', 'email', 'bastclabe', 'bastgaming', 'data_of_birth', 'created_at')->first();
            $DataProfileBss = $MyDatUser->ProfileUserBss()->get();
            $Profile = $MyDatUser->ProfileUser()->select('id', 'user_id', 'name', 'email', 'image', 'NumberPhone')->first();
            $MyTaweve = $MyDatUser->EdaretMewevin()->where([
                'confirmUser' => 1,
                'confirmBss' => 1,
                'typerelation' => 1,
            ])
            ->join('profile_user_bsses', 'edaret_mewevins.idbss', 'profile_user_bsses.id')
            ->select('profile_user_bsses.*')
            ->latest()->get();
            $FirsZ = $MyDatUser->EdaretMewevin()->where([
                'confirmUser' => 1,
                'confirmBss' => 1,
                'typerelation' => 1,
            ])->select('idbss')->first();
            $MyCurrentPaymentPay = [];
            if($FirsZ) {
                $MyCurrentPaymentPay = CurrentPaymentForUseBss::where('usernameBss', $FirsZ->idbss)->latest()->select('currentCantry')->first();
            }
            if($MyProfileUser) {
                $DatProfileLoginNow = [
                    "id" => $MyProfileUser->id,
                    "user_id" => $MyProfileUser->user_id,
                    "name" => $MyProfileUser->name,
                    "email" => $MyProfileUser->email,
                    "image" => $MyProfileUser->image,
                    "NumberPhone" => $MyProfileUser->NumberPhone,
                    "address" => $MyProfileUser->address,
                    "Gender" => $MyProfileUser->Gender,
                    "city" => $MyProfileUser->city,
                    "cantry" => $MyProfileUser->cantry,
                    "created_at" => $MyProfileUser->created_at,
                    "mycalb" => $MyProfileUser->bastclabe,
                    "mygame" => $MyProfileUser->bastgaming,
                    "data_of_birth" => $MyProfileUser->data_of_birth,
                    "TypProf" => "user",
                    "codcat" => $MyDatUser->country_code,
                ];
                $DatMyCalyan = $MyDatUser->ZebouneForUser()->where([
                    'TypeAccounte' => 'Online',
                    'ConfirmedRelactionUserBss' => 1,
                    'ConfirmedRelactionUserZeboune' => 1,
                    'ConfirmedRelation' => 1,
                ])
                ->join('profile_user_bsses', 'zeboune_for_users.usernameBss', 'profile_user_bsses.id')
                ->select('profile_user_bsses.*')->latest()->get();

                $AllsMyOrders = $MyDatUser->MyOrdersPayBss()->latest()->paginate(10);
                $TotalOrderIDo = $MyDatUser->MyOrdersPayBss()->count();
                
                $SpmlDatBssICalyan = [];
                foreach (json_decode($DatMyCalyan) as $index => $dat) {
                    $TotalDeynIs = $MyDatUser->ZebouneForUser()->where([
                        'TypeAccounte' => 'Online',
                        'ConfirmedRelactionUserBss' => 1,
                        'ConfirmedRelactionUserZeboune' => 1,
                        'ConfirmedRelation' => 1,
                        'usernameBss' => $dat->id
                    ])->select('TotelDeyn')->first();
                    $SpmlDatBssICalyan[] = [
                        'id' => $dat->id,
                        'nameOne' => $dat->usernameBss,
                        'image' => $dat->image,
                        'nameTou' => $dat->megaleBss,
                        'nameThere' => $dat->Country,
                        'totaleMyDeyn' => $TotalDeynIs->TotelDeyn,
                    ];
                }
                $datAllProdectBss = $MyDatUser->ZebouneForUser()->where([
                    'TypeAccounte' => 'Online',
                    'ConfirmedRelactionUserBss' => 1,
                    'ConfirmedRelactionUserZeboune' => 1,
                    'ConfirmedRelation' => 1,
                    ])
                ->join('prodect_users', "zeboune_for_users.usernameBss", 'prodect_users.idBss')
                ->select('prodect_users.*')
                ->latest()->get();

                $SpmlDataProdectsBss = [];
                foreach (json_decode($datAllProdectBss) as $index => $dat) {
                    $SpmlDataProdectsBss[] = [
                        'id' => $dat->id,
                        'nameOne' => $dat->name,
                        'image' => $dat->img,
                        'nameTou' => $dat->price,
                        'nameThere' => $dat->totaleinstorage,
                        'TypeActionNow' => $dat->TypePayprd == 1 ? "Active" : "DscActive",
                        'TypeData' => 'Prodects',
                        'IdBss' => $dat->IdBss
                    ];
                }

                $paymentsMethodsBss = $MyDatUser->ZebouneForUser()->where([
                    'TypeAccounte' => 'Online',
                    'ConfirmedRelactionUserBss' => 1,
                    'ConfirmedRelactionUserZeboune' => 1,
                    'ConfirmedRelation' => 1,
                    ])
                ->join('payment_method_user_bsses', "zeboune_for_users.usernameBss", 'payment_method_user_bsses.usernameBss')
                ->select('payment_method_user_bsses.*')
                ->latest()->get();
                $SpmlDataPaymentMethods = [];
                foreach (json_decode($paymentsMethodsBss) as $index => $dat) {
                    $SpmlDataPaymentMethods[] = [
                        'IdBss' => $dat->usernameBss,
                        'id' => $dat->id,
                        'nameOne' => $dat->namepayment,
                        'nameTou' => $dat->TypeNumberPay,
                        'TypeActionNow' => $dat->TypePayment == 1 ? "Active" : "DscActive",
                        'TypeData' => 'PaymentsMethods'
                    ];
                }
                // جلب 10 مستخدمين عشوائيين باستثناء المستخدم الحالي
                $randomUsers = ProfileUser::where('user_id', '!=', $MyDatUser->id)
                ->inRandomOrder()
                ->limit(10)
                ->get();
                $AllsUserToShow = [];
                foreach (json_decode($randomUsers) as $index => $dat) {
                    $typtrv = 'none';
                    $SheckTrave = EdaretMewevin::where([
                        'user_id' => $dat->user_id,
                        'confirmUser' => 1,
                        'confirmBss' => 1,
                        'typerelation' => 1,
                    ])->exists();
                    $SheckUserbss = ProfileUserBss::where('user_id', $dat->user_id)->exists();
                    if($SheckTrave) {
                        $typtrv = 'trv';
                    }
                    if($SheckUserbss) {
                        $typtrv = 'bss';
                    }
                    $AllsUserToShow[] = [
                        'id' => $dat->id,
                        'image' => $dat->image,
                        'name' => $dat->name,
                        'nameTou' => $typtrv,
                        'cantry' => $dat->cantry,
                    ];
                }

                // جلب 10 مستخدمين عشوائيين باستثناء المستخدم الحالي
                $randomBss = ProfileUserBss::where('user_id', '!=', $MyDatUser->id)
                ->inRandomOrder()
                ->limit(10)
                ->get();
                $AllsBssToShow = [];
                foreach (json_decode($randomBss) as $index => $dat) {
                    $AllsBssToShow[] = [
                        'id' => $dat->id,
                        'image' => $dat->image,
                        'name' => $dat->usernameBss,
                        'nameTou' => $dat->megaleBss,
                        'cantry' => $dat->address,
                    ];
                }
                $dataShowTrave = [
                    'message' => 'Sussefuly Login My Profile Trave',
                    'Profile' => $Profile,
                    'Profilenow' => $DatProfileLoginNow,
                    'typeProfile' => 'user',
                    'Profile_Bss' => $DataProfileBss,
                    'Profile_tweve' => $MyTaweve,
                    'DatBssICalyan' => $SpmlDatBssICalyan,
                    'dataAllProdBss' => $SpmlDataProdectsBss,
                    'PaymentsMeyhods' => $SpmlDataPaymentMethods,
                    'MyCurrentPaymentPay' => $MyCurrentPaymentPay,
                    'AllsMyOrders' => $AllsMyOrders,
                    'TotalOrderIDo' => $TotalOrderIDo,
                    'AllsUserToShow' => $AllsUserToShow,
                    'AllsBssToShow' => $AllsBssToShow,
                ];
                
                return response()->json($dataShowTrave);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 99,
                'usd' => $user
            ], 200);
        }
    } //== End Get Alls Data Profile User Smpl Now ==//

    // Start Get Alls Data Profile User Bss Login Now
    function GetAllsDataMyProfileBssNow() {
        try{ 
            $MyDatUser = Auth::user();
            $SheckDataUserLoginNow = $MyDatUser->curret_profile_id;
            $SheckMyProfIDBss = $MyDatUser->curret_profile_id_Bss;
            $SheckMyProfIDBssTv = $MyDatUser->current_my_travel;
            $MyCurrentPaymentPay = $MyDatUser->CurrentPaymentForUseBss()->where('usernameBss', $SheckMyProfIDBss)->latest()->select('currentCantry')->first();
            $DatProfileLoginNow = [];
            $MyProfileUser = $MyDatUser->ProfileUser()->where('user_id', $SheckDataUserLoginNow)->select('id', 'user_id', 'name', 'image', 'NumberPhone', 'cantry', 'address', 'city', 'Gender', 'email', 'bastclabe', 'bastgaming', 'data_of_birth', 'created_at')->first();
            $DataProfileBss = $MyDatUser->ProfileUserBss()->get();
            $Profile = $MyDatUser->ProfileUser()->select('id', 'user_id', 'name', 'email', 'image', 'NumberPhone')->first();
            $MyTaweve = $MyDatUser->EdaretMewevin()->where([
                'confirmUser' => 1,
                'confirmBss' => 1,
                'typerelation' => 1,
            ])
            ->join('profile_user_bsses', 'edaret_mewevins.idbss', 'profile_user_bsses.id')
            ->select('profile_user_bsses.*')
            ->latest()->get();

            $MyProfileBss = $MyDatUser->ProfileUserBss()->where('id', $SheckMyProfIDBss)->select('id', 'user_id', 'email', 'bigImg', 'Numberphone', 'CountEdartManye', 'usernameBss', 'image', 'address', 'Country', 'discription', 'megaleBss', 'gbsbss', 'created_at')->first();
            $MyProfileTrave = $MyDatUser->EdaretMewevin()->where([
                'confirmUser' => 1,
                'confirmBss' => 1,
                'typerelation' => 1,
                'idbss' => $SheckMyProfIDBssTv,
            ])
            ->join('profile_user_bsses', 'edaret_mewevins.idbss', 'profile_user_bsses.id')
            ->select('profile_user_bsses.*')
            ->latest()->first();
            $DataOllsOrders = $MyDatUser->MyOrdersPayBss()->where([
                'usernameBss' => $SheckMyProfIDBss,
                'TypeOrder' => 0,
                ])->latest()->select('id')->count();
            $MayZeboune = ZebouneForUser::where([
                'usernameBss' => $SheckMyProfIDBss,
                'ConfirmedRelactionUserBss' => 1,
                'ConfirmedRelactionUserZeboune' => 1,
                'ConfirmedRelation' => 1,
            ])->latest()->select('username', 'id', 'TotelDeyn', 'numberPhone', 'HaletDeyn', 'image')->get();
            $SmplDatZebayns = [];
            foreach (json_decode($MayZeboune) as $index => $dat) {
                $SmplDatZebayns[] = [
                    'id' => $dat->id,
                    'nameOne' => $dat->username,
                    'image' => $dat->image,
                    'nameTou' => $dat->numberPhone,
                    'nameThere' => $dat->TotelDeyn,
                    'TypeActionNow' => $dat->HaletDeyn == 1 ? "Active" : "DscActive",
                    'TypeData' => 'Zebouns'
                ];
            }
            $MyPaymentProdectPay = $MyDatUser->PaymentProdectUserBss()->where('IdBss', $SheckMyProfIDBss)->latest()->select('id', 'totalpriceprodectspay', 'namezeboune', 'allquantitelprodect', 'typepayment')->get();
            $MyOrderPayment = $MyDatUser->MyOrdersPayBss()->where('usernameBss', $SheckMyProfIDBss)->latest()->latest()->select('id')->get();
            $MayProd = $MyDatUser->ProdectUser()->where('IdBss', $SheckMyProfIDBss)->select('id', 'name', 'price', 'totaleinstorage', 'TypePayprd', 'img')->get();
            $SpmlDatProdect = [];
            foreach (json_decode($MayProd) as $index => $dat) {
                $SpmlDatProdect[] = [
                    'id' => $dat->id,
                    'nameOne' => $dat->name,
                    'image' => $dat->img,
                    'nameTou' => $dat->price,
                    'nameThere' => $dat->totaleinstorage,
                    'TypeActionNow' => $dat->TypePayprd == 1 && $dat->totaleinstorage >= 1 ? "Active" : "DscActive",
                ];
            }
            
            if($MyProfileBss) {
                $SpmlDatCategory = [];
                $SpmlDatPayments = [];
                $DatProfileLoginNow = [
                    "id" => $MyProfileBss->id,
                    "user_id" => $MyProfileBss->user_id,
                    'TypProf' => 'bss',
                    "name" => $MyProfileBss->usernameBss,
                    "image" => $MyProfileBss->image,
                    "countEdarMal" => $MyProfileBss->CountEdartManye,
                    "megaleBss" => $MyProfileBss->megaleBss,
                    "gbsbss" => $MyProfileBss->gbsbss,
                    "CountEdartManye" => $MyProfileBss->CountEdartManye,
                    "discription" => $MyProfileBss->discription,
                    "bigImg" => $MyProfileBss->bigImg,
                    "Numberphone" => $MyProfileBss->Numberphone,
                    "email" => $MyProfileBss->email,
                    "address" => $MyProfileBss->address,
                    "Country" => $MyProfileBss->Country,
                    "created_at" => $MyProfileBss->created_at,
                    "codcat" => $MyDatUser->country_code,
                ];
                $PaymentsMerthods = $MyDatUser->paymentmethoduserbsses()->where([
                    'usernameBss' => $SheckMyProfIDBss,
                ])->select('id','namepayment', 'TypeNumberPay', 'TypePayment')->get();
                foreach (json_decode($PaymentsMerthods) as $index => $dat) {
                    $SpmlDatPayments[] = [
                        'id' => $dat->id,
                        'nameOne' => $dat->namepayment,
                        'image' => '',
                        'nameTou' => $dat->TypeNumberPay,
                        'TypeActionNow' => $dat->TypePayment == 1 ? "Active" : "DscActive",
                    ];
                }
                $MayCategory = $MyDatUser->UserCategory()->where('IdBss', $SheckMyProfIDBss)->latest()->select('id', 'category')->get();
                foreach (json_decode($MayCategory) as $index => $dat) {
                    $SpmlDatCategory[] = [
                        'id' => $dat->id,
                        'nameOne' => $dat->category,
                    ];
                }
                $totalOrdersProfit = $MyDatUser->MyOrdersPayBss()->where([
                    'usernameBss' => $SheckMyProfIDBss,
                    'typepayment' => 1,
                    'TypeOrder' => 1,
                ])->sum('totalpriceprodectspay');
                
                $totalSalesProfit = $MyDatUser->PaymentProdectUserBss()->where([
                    'IdBss' => $SheckMyProfIDBss,
                    'typepayment' => 1,
                ])->sum('totalpriceprodectspay');

                $TotaleProfit = $totalOrdersProfit + $totalSalesProfit;

                $currentMonth = Carbon::now()->month;
                $currentYear = Carbon::now()->year;
                
                // أرباح الطلبيات لهذا الشهر
                $monthlyOrdersProfit = $MyDatUser->MyOrdersPayBss()->where([
                    'usernameBss' => $SheckMyProfIDBss,
                    'typepayment' => 1,
                    'TypeOrder' => 1,
                ])->whereMonth('created_at', $currentMonth)
                    ->whereYear('created_at', $currentYear)
                    ->sum('totalpriceprodectspay');
                
                // أرباح المبيعات لهذا الشهر
                $monthlySalesProfit = $MyDatUser->PaymentProdectUserBss()->where([
                    'IdBss' => $SheckMyProfIDBss,
                    'typepayment' => 1,
                ])->whereMonth('created_at', $currentMonth)
                    ->whereYear('created_at', $currentYear)
                    ->sum('totalpriceprodectspay');

                $TotaleProfiteMonth = $monthlyOrdersProfit + $monthlySalesProfit;

                $dataShowTrave = [
                    'message' => 'Sussefuly Login My Profile Bss Or Trave',
                    'Profile' => $Profile,
                    'Profilenow' => $DatProfileLoginNow,
                    'typeProfile' => 'bss',
                    'Profile_Bss' => $DataProfileBss,
                    'Profile_tweve' => $MyTaweve,
                    'MayProd' => $SpmlDatProdect,
                    'MyPaymentProdectPay' => $MyPaymentProdectPay,
                    'MyOrderPayment' => $MyOrderPayment,
                    'MayZeboune' => $SmplDatZebayns,
                    'MyCurrentPaymentPay' => $MyCurrentPaymentPay,
                    'MayCategory' => $SpmlDatCategory,
                    'MyPaymentMehods' => $SpmlDatPayments,
                    'allOrderDontConfrmed' => $DataOllsOrders,
                    'TotaleProfit' => $TotaleProfit,
                    'TotaleProfiteMonth' => $TotaleProfiteMonth
                ];

            }
            return response()->json($dataShowTrave);
            // return response()->json([
            // 'prof bss' => $MyProfileBss,
            // 'SheckMyProfIDBss Id' => $SheckMyProfIDBss,
            // 'user' => $MyDatUser,
            // ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'حدث خطأ في النظام',
                'typAction' => 20,
                'error' => $e->getMessage(),
            ], 200);
        }

    } //== End Get Alls Data Profile User Bss Login Now ==//
    
    // Start Get Alls Data Type Profile Login Now
    function MyTotalDatShow() {
        try{ 
            $MyDatUser = Auth::user();
            if($MyDatUser->curret_profile_id) {
                $dataShowTrave = $this->StartGetAllsDataProfileSmpl($MyDatUser)->getData();
            } else if($MyDatUser->curret_profile_id_Bss) {
                $dataShowTrave = $this->GetAllsDataMyProfileBssNow()->getData();
            } else if($MyDatUser->current_my_travel) {
                $dataShowTrave = $this->GetAllsDataMyProfileTrave()->getData();
            } else {
                return response()->json([
                    'message' => '  Sory Bls Later',
                    'typAction' => 5,
                    'data' => [],
                ]);
            }
            return response()->json($dataShowTrave);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'حدث خطأ في النظام',
                'error' => $e,
                'typAction' => 20,
            ], 500);
        }

    } //== End Get Alls Data Type Profile Login Now ==//

    // Start Get Alls Data Profile Trave Now
    function GetAllsDataMyProfileTrave() {
        try{ 
            $MyDatUser = Auth::user();
            $SheckMyProfIDBssTv = $MyDatUser->current_my_travel;
            $MyCurrentPaymentPay = CurrentPaymentForUseBss::where('usernameBss', $SheckMyProfIDBssTv)->latest()->select('currentCantry')->first();
            $DatProfileLoginNow = [];
            $DataProfileBss = $MyDatUser->ProfileUserBss()->get();
            $Profile = $MyDatUser->ProfileUser()->select('id', 'user_id', 'name', 'email', 'image', 'NumberPhone')->first();
            $MyTaweve = $MyDatUser->EdaretMewevin()->where([
                'confirmUser' => 1,
                'confirmBss' => 1,
                'typerelation' => 1,
            ])
            ->join('profile_user_bsses', 'edaret_mewevins.idbss', 'profile_user_bsses.id')
            ->select('profile_user_bsses.*')
            ->latest()->get();

            $MyProfileTrave = $MyDatUser->EdaretMewevin()->where([
                'confirmUser' => 1,
                'confirmBss' => 1,
                'typerelation' => 1,
                'idbss' => $SheckMyProfIDBssTv,
            ])
            ->join('profile_user_bsses', 'edaret_mewevins.idbss', 'profile_user_bsses.id')
            ->select('profile_user_bsses.*')
            ->latest()->first();
            $DataOllsOrders = MyOrdersPayBss::where([
                'usernameBss' => $SheckMyProfIDBssTv,
                'TypeOrder' => 0,
                ])->latest()->select('id')->count();
            $MayZeboune = ZebouneForUser::where([
                'usernameBss' => $SheckMyProfIDBssTv,
                'ConfirmedRelactionUserBss' => 1,
                'ConfirmedRelactionUserZeboune' => 1,
                'ConfirmedRelation' => 1,
            ])->latest()->select('username', 'id', 'TotelDeyn', 'numberPhone', 'HaletDeyn', 'image')->get();
            $SmplDatZebayns = [];
            foreach (json_decode($MayZeboune) as $index => $dat) {
                $SmplDatZebayns[] = [
                    'id' => $dat->id,
                    'nameOne' => $dat->username,
                    'image' => $dat->image,
                    'nameTou' => $dat->numberPhone,
                    'nameThere' => $dat->TotelDeyn,
                    'TypeActionNow' => $dat->HaletDeyn == 1 ? "Active" : "DscActive",
                ];
            }
            $MyPaymentProdectPay = PaymentProdectUserBss::where('IdBss', $SheckMyProfIDBssTv)->latest()->select('id', 'totalpriceprodectspay', 'namezeboune', 'allquantitelprodect', 'typepayment')->get();
            $MyOrderPayment = MyOrdersPayBss::where('usernameBss', $SheckMyProfIDBssTv)->latest()->latest()->select('id')->get();
            $MayProd = ProdectUser::where('IdBss', $SheckMyProfIDBssTv)->select('id', 'name', 'price', 'totaleinstorage', 'TypePayprd', 'img')->get();
            $SpmlDatProdect = [];
            foreach (json_decode($MayProd) as $index => $dat) {
                $SpmlDatProdect[] = [
                    'id' => $dat->id,
                    'nameOne' => $dat->name,
                    'image' => $dat->img,
                    'nameTou' => $dat->price,
                    'nameThere' => $dat->totaleinstorage,
                    'TypeActionNow' => $dat->TypePayprd == 1 && $dat->totaleinstorage >= 1 ? "Active" : "DscActive",
                ];
            }
            
            if($MyProfileTrave) {
                $SpmlDatPayments = [];
                $PaymentsMerthods = PaymentMethodUserBss::where([
                    'usernameBss' => $SheckMyProfIDBssTv,
                ])->select('id','namepayment', 'TypeNumberPay', 'TypePayment')->get();
                foreach (json_decode($PaymentsMerthods) as $index => $dat) {
                    $SpmlDatPayments[] = [
                        'id' => $dat->id,
                        'nameOne' => $dat->namepayment,
                        'image' => '',
                        'nameTou' => $dat->TypeNumberPay,
                        'TypeActionNow' => $dat->TypePayment == 1 ? "Active" : "DscActive",
                    ];
                }
                $MyProfileTraveSpm = $MyDatUser->EdaretMewevin()->where([
                    'confirmUser' => 1,
                    'confirmBss' => 1,
                    'typerelation' => 1,
                    'idbss' => $SheckMyProfIDBssTv,
                ])->select('id', 'edartemaney', 'edartPaymentProdects', 'edartOreders')->first();

                $DatProfileLoginNow = [
                    "id" => $MyProfileTrave->id,
                    "user_id" => $MyProfileTrave->user_id,
                    "name" => $MyProfileTrave->usernameBss,
                    "image" => $MyProfileTrave->image,
                    "countEdarMal" => $MyProfileTrave->CountEdartManye,
                    "megaleBss" => $MyProfileTrave->megaleBss,
                    "gbsbss" => $MyProfileTrave->gbsbss,
                    "edartmaney" => $MyProfileTraveSpm->edartemaney,
                    "edartpayprodects" => $MyProfileTraveSpm->edartPaymentProdects,
                    "edartOrders" => $MyProfileTraveSpm->edartOreders,
                    'TypProf' => 'teweve',
                    "codcat" => $MyDatUser->country_code,
                ];
                $dataShowTrave = [
                    'message' => 'Sussefuly Login My Profile Trave',
                    'Profile' => $Profile,
                    'Profilenow' => $DatProfileLoginNow,
                    'typeProfile' => 'teweve',
                    'Profile_Bss' => $DataProfileBss,
                    'Profile_tweve' => $MyTaweve,
                    'MayProd' => $SpmlDatProdect,
                    'MayZeboune' => $SmplDatZebayns,
                    'MyPaymentProdectPay' => $MyPaymentProdectPay,
                    'MyOrderPayment' => $MyOrderPayment,
                    'MyCurrentPaymentPay' => $MyCurrentPaymentPay,
                    'MyPaymentMehods' => $SpmlDatPayments,
                    'allOrderDontConfrmed' => $DataOllsOrders,
                ];
            }
            return response()->json($dataShowTrave);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'حدث خطأ في النظام',
                'typAction' => 20,
            ], 500);
        }

    } //== End Get Alls Data Profile Trave Now ==//

    function UpdateOrCreateMyProfileNow(Request $request) {
        try {
            $MyDataProfile = Auth::user();
            $SheckMyProfIDBss = $MyDataProfile->curret_profile_id_Bss;
            $SheckMyProfID = $MyDataProfile->curret_profile_id ;
            $MyProfileuser = $MyDataProfile->ProfileUser()->where('user_id', $SheckMyProfID)->select('id', 'name', 'city', 'bastclabe', 'bastgaming')->first();
            $MyProfileBss = $MyDataProfile->ProfileUserBss()->where('id', $SheckMyProfIDBss)->select('id', 'usernameBss', 'megaleBss', 'discription', 'gbsbss', 'address')->first();
            
            if ($MyProfileuser) {
                $request->validate([
                    'name' => 'required|string|min:3|max:115',
                    'city' => 'nullable|string|min:3|max:135',
                    'bastgame' => 'nullable|string|min:3|max:95',
                    'bastclab' => 'nullable|string|min:3|max:112',
                ]);
                $name = strip_tags($request->name);
                $city = strip_tags($request->city);
                $bastgame = strip_tags($request->bastgame);
                $bastclab = strip_tags($request->bastclab);

                if($name !='') {
                    if($MyProfileuser->name != $name) {
                        $MyProfileuser->update([
                            'name' => $name,
                        ]);
                    }
                }
                if($city !='') {
                    if($MyProfileuser->city != $city) {
                        $MyProfileuser->update([
                            'city' => $city,
                        ]);
                    }
                }
                if($bastclab !='') {
                    if($MyProfileuser->bastclabe != $bastclab) {
                        $MyProfileuser->update([
                            'bastclabe' => $bastclab,
                        ]);
                    }
                }
                if($bastgame !='') {
                    if($MyProfileuser->bastgaming != $bastgame) {
                        $MyProfileuser->update([
                            'bastgaming' => $bastgame,
                        ]);
                    }
                }

                if($MyProfileuser) {
                    $AllsDataProfileNow = $this->StartGetAllsDataProfileSmpl($MyDataProfile);
                    return response()->json([
                        'message' => 'Start Updat My Profile Bss',
                        'typAction' => 1,
                        'data' => $AllsDataProfileNow->getData(),
                    ]);
                } else {
                    return response()->json([
                        'message' => 'Start Updat My Profile Bss',
                        'typAction' => 2,
                    ]);
                }
            } else if($MyProfileBss) {
                $request->validate([
                    'nameBssTiUpdaTe' => 'required|string|min:3|max:115',
                    'megaleBssT' => 'nullable|string|min:3|max:135',
                    'gbsbssT' => 'nullable|string|min:3|max:95',
                    'addressT' => 'nullable|string|min:3|max:112',
                    'discription' => 'nullable|string|min:3|max:155',
                ]);
                $nameBssTiUpdaTe = strip_tags($request->nameBssTiUpdaTe);
                $megaleBssT = strip_tags($request->megaleBssT);
                $gbsbssT = strip_tags($request->gbsbssT);
                $address = strip_tags($request->addressT);
                $discription = strip_tags($request->discription);

                if($nameBssTiUpdaTe !='') {
                    if($MyProfileBss->usernameBss != $nameBssTiUpdaTe) {
                        $MyProfileBss->update([
                            'usernameBss' => $nameBssTiUpdaTe,
                        ]);
                    }
                }
                if($megaleBssT !='') {
                    if($MyProfileBss->megaleBss != $megaleBssT) {
                        $MyProfileBss->update([
                            'megaleBss' => $megaleBssT,
                        ]);
                    }
                }
                if($discription !='') {
                    if($MyProfileBss->discription != $discription) {
                        $MyProfileBss->update([
                            'discription' => $discription,
                        ]);
                    }
                }
                if($address !='') {
                    if($MyProfileBss->address != $address) {
                        $MyProfileBss->update([
                            'address' => $address,
                        ]);
                    }
                }
                if($gbsbssT !='') {
                    if($MyProfileBss->gbsbss != $gbsbssT) {
                        $MyProfileBss->update([
                            'gbsbss' => $gbsbssT,
                        ]);
                    }
                }

                if($MyProfileBss) {
                    $AllsDataProfileNow = $this->GetAllsDataMyProfileBssNow();
                    return response()->json([
                        'message' => 'Start Updat My Profile Bss',
                        'typAction' => 1,
                        'data' => $AllsDataProfileNow->getData(),
                    ]);
                } else {
                    return response()->json([
                        'message' => 'Start Updat My Profile Bss',
                        'typAction' => 2,
                    ]);
                }
            } else {
                return response()->json([
                    'message' => 'Sorry Error Not Found Semthing Error',
                    'data' => 5,
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 99,
            ], 200);
        }
    }

    function ImdateMyAvatarProfiole(Request $request)  {
        try {
            $userData = Auth::user();
            $SheckMyProfIDBss = $userData->curret_profile_id_Bss;
            $MyProfileBss = $userData->ProfileUserBss()->where('id', $SheckMyProfIDBss)->first();
            
            if($MyProfileBss) {
                $request->validate([
                    'MyAvatarBigImgProfileBss' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10050',
                    'MyAvatarImgProfile' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10050',
                ]);

                if($request->hasFile('MyAvatarImgProfile')) {
                    $SkeckImgNow = $MyProfileBss->image;
                    if($SkeckImgNow) {
                        unlink($SkeckImgNow);
                    }
                    
                    $image = $request->file('MyAvatarImgProfile');
                    $gen = hexdec(uniqid());
                    $ext = strtolower($image->getClientOriginalExtension());
                    $namePod = $gen . '' . $ext;
                    $location = 'Profile_User_Bss/';
                    $source = $location.$namePod;
                    $name = $gen. '.' .$ext;
                    $source = $location.$name;
                    $image->move($location,$name);

                    $UpdatImg=$MyProfileBss->update([
                        'image' => $source
                    ]);
                    $AllsDataProfileNow = $this->GetAllsDataMyProfileBssNow();

                    if($UpdatImg) {
                        return response()->json([
                            'message' => 'SuccessFuly Update My Profile',
                            'data' => $AllsDataProfileNow->getData(),
                            'typAction' => 3,
                        ]);
                        return;
                    } else {
                        return response()->json([
                            'message' => 'SuccessFuly Update My Profile Big Image',
                            'data' => $AllsDataProfileNow->getData(),
                            'typAction' => 2,
                        ]);
                        return;
                    }
                } else if($request->hasFile('MyAvatarBigImgProfileBss')) {
                    $SkeckImgNow = $MyProfileBss->bigImg;
                    if($SkeckImgNow) {
                        unlink($SkeckImgNow);
                    }
                    
                    $image = $request->file('MyAvatarBigImgProfileBss');
                    $gen = hexdec(uniqid());
                    $ext = strtolower($image->getClientOriginalExtension());
                    $namePod = $gen . '' . $ext;
                    $location = 'Big_Profile_User_Bss/';
                    $source = $location.$namePod;
                    $name = $gen. '.' .$ext;
                    $source = $location.$name;
                    $image->move($location,$name);
                    $UpdatImg = $MyProfileBss->update([
                        'bigImg' => $source
                    ]);
                    $AllsDataProfileNow = $this->GetAllsDataMyProfileBssNow();

                    if($UpdatImg) {
                        return response()->json([
                            'message' => 'SuccessFuly Update My Profile Big Image',
                            'data' => $AllsDataProfileNow->getData(),
                            'typAction' => 1,
                        ]);
                        return;
                    } else {
                        return response()->json([
                            'message' => 'SuccessFuly Update My Profile Big Image',
                            'data' => $AllsDataProfileNow->getData(),
                            'typAction' => 2,
                        ]);
                        return;
                    }
                }
            }
            
            $MyProfileUser = $userData->ProfileUser()->where('user_id', $userData->curret_profile_id )->first();
            
            if($MyProfileUser) {
                $request->validate([
                    'MyAvatarImgProfile' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                ]);
                if($request->hasFile('MyAvatarImgProfile')) {
                    $MyProfileUserImg = $MyProfileUser->image;
                    if($MyProfileUserImg) {
                        unlink("$MyProfileUserImg");
                    }
                    
                    $image = $request->file('MyAvatarImgProfile');
                    $gen = hexdec(uniqid());
                    $ext = strtolower($image->getClientOriginalExtension());
                    $namePod = $gen . '' . $ext;
                    $location = 'Avatar_user_Profile/';
                    $source = $location.$namePod;
                    $name = $gen. '.' .$ext;
                    $source = $location.$name;
                    $image->move($location,$name);
                    $UpdatImg = $MyProfileUser->update([
                        'image' => $source,
                    ]);
                    $AllsDataProfileNow = $this->StartGetAllsDataProfileSmpl();
                    if($UpdatImg) {
                        return response()->json([
                            'message' => 'SuccessFuly Update My Profile Big Image',
                            'data' => $AllsDataProfileNow->getData(),
                            'typAction' => 1,
                        ]);
                    } else {
                        return response()->json([
                            'message' => 'Error Update My Profile Big Image',
                            'data' => $AllsDataProfileNow->getData(),
                            'typAction' => 2,
                        ]);
                    } 

                }
            } else {
                return response()->json([
                    'message' => 'Sorry Error Not Found Semthing Error',
                    'data' => [],
                    'typAction' => 6,
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sory This Number Phone Is One Realy Created',
                'typAction' => 6,
                'data' => [],
            ], 200);
            return;
        }
    
    }

    // Start To Shange Numer Phone For Profile Login Now
    function ShangeMyNumberPhoneForMyProfile(Request $request) {
        try {
            $DatProfile = Auth::user();
            $SheckMyProfIDBss = $DatProfile->curret_profile_id_Bss;
            $SheckMyProfID = $DatProfile->curret_profile_id ;
            $MyProfileuser = $DatProfile->ProfileUser()->where('user_id', $SheckMyProfID)->select('id', 'name', 'city', 'bastclabe', 'bastgaming')->first();
            $MyProfileBss = $DatProfile->ProfileUserBss()->where('id', $SheckMyProfIDBss)->select('id', 'Numberphone')->first();
            
            if($MyProfileuser) {
                $request->validate([
                    'PhoneUpd' => 'required|string',
                ]);
                $datPhoneUpd = strip_tags($request->PhoneUpd);

                $SheckNumberPh = User::where([
                    'NumberPhone' => $datPhoneUpd,
                ])->exists();
                if($SheckNumberPh) {
                    return response()->json([
                        'message' => 'Sory This Number Phone Is One Realy Created',
                        'typAction' => 3,
                        'data' => [],
                    ], 200);
                    return;
                }
                
                $updaData = $MyProfileuser->update([
                    'NumberPhone' => $datPhoneUpd,
                ]);

                $updaDataUsr = $DatProfile->update([
                    'NumberPhone' => $datPhoneUpd,
                ]);
                
                if($updaData && $updaDataUsr) {
                    $AllsDataProfileNow = $this->StartGetAllsDataProfileSmpl($DatProfile);
                    return response()->json([
                        'message' => 'Sory This Password Not Correct',
                        'typAction' => 1,
                        'data' => $AllsDataProfileNow->getData(),
                    ], 200);
                    return;
                } else {
                    return response()->json([
                        'message' => 'Sory This Password Not Correct',
                        'typAction' => 2,
                        'data' => [],
                    ], 200);
                    return;
                }
            } else if($MyProfileBss) {
                $request->validate([
                    'passwordSetting' => 'required|string',
                    'PhoneUpd' => 'required|string',
                ]);
                $shekpas = $DatProfile->UserPasswordStting()->where('idbss', $SheckMyProfIDBss)->select('id', 'password')->first();
                if($shekpas === null) {
                    return response()->json([
                        'message' => 'Your`e Not Have Eny Password Setting Son Plz Go To Create',
                        'typAction' => 5,
                        'data' => [],
                    ]);
                    return;
                }
                $passwordStingHa = strip_tags($request->passwordSetting);
                $passwordStingHaahing = Hash::make($passwordStingHa);
                $passwordHash = $shekpas->password;
                $redPassword = request('passwordSetting');
                if($passwordHash) {
                    if(Hash::check($redPassword, $passwordHash)) {
                        $datPhoneUpd = strip_tags($request->PhoneUpd);
                        
                        $updaData = $MyProfileBss->update([
                            'Numberphone' => $datPhoneUpd,
                        ]);
                        if($updaData) {
                            $AllsDataProfileNow = $this->GetAllsDataMyProfileBssNow();
                            return response()->json([
                                'message' => 'Sory This Password Not Correct',
                                'typAction' => 1,
                                'data' => $AllsDataProfileNow->getData(),
                            ], 200);
                        } else {
                            return response()->json([
                                'message' => 'Sory This Password Not Correct',
                                'typAction' => 2,
                                'data' => [],
                            ], 200);
                        }

                    } else {
                        return response()->json([
                            'message' => 'Sory This Password Not Correct',
                            'typAction' => 7,
                            'data' => [],
                        ], 200);
                    }
                } else {
                    return response()->json([
                        'message' => 'Sory Error Semthing Not Found',
                        'typAction' => 5,
                        'data' => [],
                    ], 200);
                    return;
                }
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sory This Number Phone Is One Realy Created',
                'typAction' => 32,
                'data' => [],
            ], 200);
            return;
        }
    } //== End To Shange Numer Phone For Profile Login Now ==//


    function AllDatePaymentMethodBssToShow() {
        try {
            $MyData = Auth::user();
            $SheckMyProfIDBss = $MyData->curret_profile_id_Bss;
            $SheckMyProfIDBssTv = $MyData->current_my_travel;
            $ProfileLoginNowIs = $SheckMyProfIDBss || $SheckMyProfIDBssTv;
            $PaymentsMerthods = PaymentMethodUserBss::where('usernameBss', $ProfileLoginNowIs)->select('id','namepayment', 'TypeNumberPay', 'TypePayment')->get();
            $MyProfileBss = $MyData->ProfileUserBss()->where('id', $SheckMyProfIDBss)->first();
            $MyProfileTrav = $MyData->EdaretMewevin()->where([
                'confirmUser' => 1,
                'confirmBss' => 1,
                'typerelation' => 1,
                'idbss' => $SheckMyProfIDBssTv,
            ])
            ->join('profile_user_bsses', 'edaret_mewevins.idbss', 'profile_user_bsses.id')
            ->select('profile_user_bsses.*')
            ->latest()->first();
            if($MyProfileBss) {
                return response()->json([
                    'message' => '  Show All Data PayMentMethod',
                    'data' => $PaymentsMerthods,
                ]);
            } if($MyProfileTrav) {
                return response()->json([
                    'message' => '  Show All Data PayMentMethod',
                    'data' => $PaymentsMerthods,
                ]);
            } else {
                return response()->json([
                    'message' => '  Sory Bls Later',
                    'data' => 2,
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sory This Number Phone Is One Realy Created',
                'typAction' => 32,
                'data' => [],
            ], 500);
            return;
        }
    }

    // Start Alls Action From Edart Payment Methods For Bss
    // Start To Show Alls Data From Edart Payment Methods Bss
    function ShowMyPaymentsMethodsBss() {
        try {
            $ProdileDat = Auth::user();
            $SheckMyProfIDBss = $ProdileDat->curret_profile_id_Bss;
            $MyProfileBss = $ProdileDat->ProfileUserBss()->where('id', $SheckMyProfIDBss)->select('id')->first();
            if($MyProfileBss) {
                $PaymentsMerthods = $ProdileDat->paymentmethoduserbsses()->where([
                    'user_id' => $ProdileDat->id,
                ])->select('id', 'namepayment', 'TypeNumberPay', 'TypePayment')->latest()->paginate(10);
                return response()->json([
                    'message' => '  Show Alls Your Data From Edart Payments Method',
                    'data' => $PaymentsMerthods,
                    'typAction' => 1,
                ]);
            } else {
                return response()->json([
                    'message' => '  Sory Bls Later',
                    'data' => 9,
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 99,
            ], 200);
        }
    } //== End To Show Alls Data From Edart Payment Methods Bss ==//

    // Start Add PaymentMethod For Setting User Bss ''''''
    function AddSettingPaymentForBss(Request $request) {
        try {
            if(Auth::check()) {
                $DataUser = Auth::user();
                $SheckMyProfIDBss = $DataUser->curret_profile_id_Bss;
                $MyProfileBss = $DataUser->ProfileUserBss()->where('id', $SheckMyProfIDBss)->select('id')->first();
                if($MyProfileBss) {
                    $request->validate([
                        'NamesPaymentmethod' => 'required|string',
                    ]);
                    $NamesPaymentmethod = strip_tags($request->NamesPaymentmethod);
                    $NumbersPaymentmethod = strip_tags($request->NumbersPaymentmethod);
                    if($NamesPaymentmethod == 'CASH' | $NamesPaymentmethod == 'Selefe') {
                        $SheckPay = PaymentMethodUserBss::where([
                            'usernameBss' => $SheckMyProfIDBss, 
                            'namepayment' => $NamesPaymentmethod,
                        ])->select('id', 'namepayment')->first();
                        if($SheckPay != null && $SheckPay->namepayment == 'CASH' || $SheckPay != null &&  $SheckPay->namepayment == 'Selefe') {
                            $dataUpt = PaymentMethodUserBss::where('usernameBss', $SheckMyProfIDBss)->select('id', 'namepayment', 'TypeNumberPay', 'TypePayment')->latest()->paginate(10);
                            return response()->json([
                                'message' => " Sorry Your Dont Have relation To Update This Payment ",
                                'typAction' => 9,
                                'data' => $dataUpt,
                            ], 200);
                        }
                        $CreatePayment = PaymentMethodUserBss::create([
                            'user_id' => $DataUser->id,
                            'usernameBss' => $SheckMyProfIDBss,
                            'namepayment' => $NamesPaymentmethod,
                            'TypeNumberPay' => $NumbersPaymentmethod,
                            'TypePayment' => 1,
                        ]);
                    } else {
                        $request->validate([
                            'NumbersPaymentmethod' => 'required|string',
                        ]);
                        $SheckPay = PaymentMethodUserBss::where([
                            'usernameBss' => $SheckMyProfIDBss, 
                            'namepayment' => $NamesPaymentmethod,
                            'TypeNumberPay' => $NumbersPaymentmethod,
                        ])->select('id')->first();
                        if($SheckPay) {
                            return response()->json([
                                'message' => " Sorry Your Dont Have relation To Update This Payment ",
                                'typAction' => 11,
                                'data' => [],
                            ], 200);
                        }
                        $SheckPayment = PaymentMethodUserBss::where([
                            'user_id' => strip_tags($DataUser->id),
                            'usernameBss' => strip_tags($SheckMyProfIDBss),
                            'namepayment' => strip_tags($NamesPaymentmethod),
                            'TypeNumberPay' => strip_tags($NumbersPaymentmethod),
                        ])->select('id')->first();
                        if($SheckPayment) {
                            return response()->json([
                                'message' => 'Sorry Your Are One Realy Create This Data Payment',
                                'typAction' => 9,
                                'data' => [],
                            ]);
                        }
                        $CreatePayment = PaymentMethodUserBss::create([
                            'user_id' => strip_tags($DataUser->id),
                            'usernameBss' => strip_tags($SheckMyProfIDBss),
                            'namepayment' => strip_tags($NamesPaymentmethod),
                            'TypeNumberPay' => strip_tags($NumbersPaymentmethod),
                            'TypePayment' => 1,
                        ]);
                    }

                    // $CreatePayment = PaymentMethodUserBss::create([
                    //     'user_id' => $DataUser->id,
                    //     'usernameBss' => $SheckMyProfIDBss,
                    //     'namepayment' => $NamesPaymentmethod,
                    //     'TypeNumberPay' => $NumbersPaymentmethod,
                    //     'TypePayment' => 1,
                    // ]);

                    // $CreatePayment = PaymentMethodUserBss::create([
                    //     'user_id' => strip_tags($DataUser->id),
                    //     'usernameBss' => strip_tags($SheckMyProfIDBss),
                    //     'namepayment' => strip_tags($NamesPaymentmethod),
                    //     'TypeNumberPay' => '359090',
                    //     'TypePayment' => 1,
                    // ]);

                    if ($CreatePayment) {
                        return response()->json([
                            'message' => 'SuccesFuly Payment Method',
                            'typAction' => 1,
                            'data' => [],
                        ]);
                    } else {
                        return response()->json([
                            'message' => 'Error Soerr Payment Method',
                            'typAction' => 2,
                            'data' => [],
                        ]);
                    }

                } else {
                    return response()->json([
                        'message' => '  Sory Bls Later',
                        'data' => 5,
                    ]);
                }
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 99,
            ], 200);
        }
    } // End Add PaymentMethod For Setting User Bss //

    // Start Add PaymentMethod For Setting User Bss 
    function AddCurrentCantryForBss(Request $request) {
        try {
            $profileData = Auth::user();
            $SheckMyProfIDBss = $profileData->curret_profile_id_Bss;
            $MyProfileBss = $profileData->ProfileUserBss()->where('id', $SheckMyProfIDBss)->select('id')->first();
            if($MyProfileBss) {
                $request->validate([
                    'passwordSetting' => 'required|string',
                    'currentCantery' => 'required|string',
                ]);
                $currentCantery = strip_tags($request->currentCantery);
                $shekpas = $profileData->UserPasswordStting()->where('idbss', $SheckMyProfIDBss)->select('id', 'password')->first();
                $passwordStingHa = strip_tags($request->passwordSetting);
                $passwordStingHaahing = Hash::make($passwordStingHa);
                $passwordHash = $shekpas->password;
                $redPassword = request('passwordSetting');
                if($passwordHash) {
                    if(Hash::check($redPassword, $passwordHash)) {
                        $MyCurrent = $profileData->CurrentPaymentForUseBss()->where([
                            'user_id' => $profileData->id,
                            'usernameBss' => $SheckMyProfIDBss,
                        ])->select('id')->first();
                        if($MyCurrent) {
                            return response()->json([
                                'message' => 'Sorry Your Are One Realey Create Current Payment',
                                'typAction' => 7,
                                'data' => [],
                            ]);
                        } else {
                            $CreateCantyPay = CurrentPaymentForUseBss::create([
                                'user_id' => $profileData->id,
                                'usernameBss' => $SheckMyProfIDBss,
                                'currentCantry' => $currentCantery,
                            ]);

                            if ($CreateCantyPay) {
                                return response()->json([
                                    'message' => 'SuccesFuly Payment Method',
                                    'typAction' =>1,
                                    'data' => [],
                                ]);
                            } else {
                                return response()->json([
                                    'message' => 'Error Soerr Payment Method',
                                    'typAction' => 2,
                                    'data' => [],
                                ]);
                            }
                            
                        }
                    } else {
                        return response()->json([
                            'message' => 'Sorry Password Is Not Found',
                            'typAction' => 3,
                            'data' => [],
                        ]);
                    }
                } else {
                    return response()->json([
                        'message' => 'You Are Not Have Any Password Settinh Plz Created',
                        'typAction' => 4,
                        'data' => [],
                    ]);
                }
            } else {
                return response()->json([
                    'message' => 'Errorr Semthing Not Found',
                    'data' => 16,
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 99,
            ], 200);
        } 
    } // End Add PaymentMethod For Setting User Bss //

    // Start Action Payments Methods For Bss
    function ActiveMyPaymentSettings(Request $request, $id) {
        try {
            $ProfileDat = Auth::user();
            $SheckMyProfIDBss = $ProfileDat->curret_profile_id_Bss;
            $MyProfileBss = $ProfileDat->ProfileUserBss()->where('id', $SheckMyProfIDBss)->select('id')->first();
            if($MyProfileBss) { 
                $request->validate([
                    'passwordSetting' => 'required|string'
                ]);
                $passwordStingHa = strip_tags($request->passwordSetting);
                $samplId = strip_tags($id);

                $shekpas = UserPasswordStting()->where('idbss', $SheckMyProfIDBss)->select('id', 'password')->first();
                $passwordStingHaahing = Hash::make($passwordStingHa);
                $passwordHash = $shekpas->password;
                $redPassword = request('passwordSetting');
                if($passwordHash) {
                    if(Hash::check($redPassword, $passwordHash)) {
                        $UpdatePay = PaymentMethodUserBss::where([
                            'usernameBss' => $SheckMyProfIDBss, 
                            'id' => $samplId,
                        ])->select('id', 'TypePayment')->first();
                        if($UpdatePay->TypePayment == 1) {
                            $dataUpdat = PaymentMethodUserBss::where('usernameBss', $SheckMyProfIDBss)->select('id', 'namepayment', 'TypeNumberPay', 'TypePayment')->latest()->paginate(10);
                            return response()->json([
                                'message' => " Sorry Your Are On Realy Save Update Active Pament",
                                'data' => $dataUpdat,
                                'typAction' => 9,
                            ], 200);
                        }
                        $confirmUpdtPay = $UpdatePay->update(['TypePayment' => 1]);

                        if($confirmUpdtPay) {
                            $dataUpdat = PaymentMethodUserBss::where('usernameBss', $SheckMyProfIDBss)->select('id', 'namepayment', 'TypeNumberPay', 'TypePayment')->latest()->paginate(10);
                            return response()->json([
                                'message' => " Sussfuly Save Update Active Pament",
                                'data' => $dataUpdat,
                                'typAction' => 1,
                            ], 200);
                        } else {
                            $dataUpdat = PaymentMethodUserBss::where('usernameBss', $SheckMyProfIDBss)->select('id', 'namepayment', 'TypeNumberPay', 'TypePayment')->latest()->paginate(10);
                            return response()->json([
                                'message' => " From Edart Setting Paye3mt",
                                'typAction' => 3,
                                'data' => $dataUpdat,
                            ], 200);
                        }
                    } else {
                            return response()->json([
                                'message' => "Sorry Your Password Setting Is Not Correct",
                                'typAction' => 7,
                                'data' => [],
                            ], 200);
                        }
                } else {
                    return response()->json([
                        'message' => "Sorry Your Are Not Have Password Setting Plz Created",
                        'typAction' => 8,
                        'data' => [],
                    ], 200);
                }
            } else {
                return response()->json([
                    'message' => " error",
                    'data' => 12,
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 99,
            ], 200);
        }
    } //== End Action Payments Methods For Bss ==//

    // Start Dsc Action Payments Methods For Bss
    function DscActiveMyPaymentSettings(Request $request, $id) {
        try {
            $DatProfile = Auth::user();
            $SheckMyProfIDBss = $DatProfile->curret_profile_id_Bss;
            $MyProfileBss = $DatProfile->ProfileUserBss()->where('id', $SheckMyProfIDBss)->first();
            if($MyProfileBss) { 
                $request->validate([
                    'passwordSetting' => 'required|string',
                ]);
                $passwordStingHa = strip_tags($request->passwordSetting);
                $samplId = strip_tags($id);
                $shekpas = $DatProfile->UserPasswordStting()->where('idbss', $SheckMyProfIDBss)->select('id', 'password')->first();
                $passwordStingHaahing = Hash::make($passwordStingHa);
                $passwordHash = $shekpas->password;
                $redPassword = request('passwordSetting');
                if($passwordHash) {
                    if(Hash::check($redPassword, $passwordHash)) {
                        $UpdatePay = PaymentMethodUserBss::where([
                            'usernameBss' => $SheckMyProfIDBss, 
                            'id' => $samplId,
                        ])->select('id', 'TypePayment')->first();
                        if($UpdatePay->TypePayment == 2) {
                            $dataUpdat = PaymentMethodUserBss::where('usernameBss', $SheckMyProfIDBss)->select('id', 'namepayment', 'TypeNumberPay', 'TypePayment')->latest()->paginate(10);
                            return response()->json([
                                'message' => " Sorry Your Are On Realy desc Actiive Pament",
                                'data' => $dataUpdat,
                                'typAction' => 9,
                            ], 200);
                        }
                        $datupdpayment = $UpdatePay->update(['TypePayment' => 2]);
                        if($datupdpayment) {
                            $datupdat = PaymentMethodUserBss::where('usernameBss', $SheckMyProfIDBss)->select('id', 'namepayment', 'TypeNumberPay', 'TypePayment')->latest()->paginate(10);
                            return response()->json([
                                'message' => " Sussfuly Save Update  Pament",
                                'typAction' => 1,
                                'data' => $datupdat,
                            ], 200);
                        } else {
                            $datupdat = PaymentMethodUserBss::where('usernameBss', $SheckMyProfIDBss)->select('id', 'namepayment', 'TypeNumberPay', 'TypePayment')->latest()->paginate(10);
                            return response()->json([
                                'message' => " From Edart Setting Paye3mt",
                                'typAction' => 3,
                                'data' => $datupdat,
                            ], 200);
                        }
                    } else {
                            return response()->json([
                                'message' => "Sorry Your Password Setting Is Not Correct",
                                'typAction' => 7,
                                'data' => [],
                            ], 200);
                        }
                } else {
                    return response()->json([
                        'message' => "Sorry Your Are Not Have Password Setting Plz Created",
                        'typAction' => 8,
                        'data' => [],
                    ], 200);
                }
            } else {
                return response()->json([
                    'message' => " error",
                    'data' => 0,
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 99,
            ], 200);
        }
    } //== End Dsc Action Payments Methods For Bss ==//

    // Start Update Payments Methods For Bss Click To Id
    function UpdateMyPaymentSettings(Request $request, $id) {
        try {
            $ProfileData = Auth::user();
            $SheckMyProfIDBss = $ProfileData->curret_profile_id_Bss;
            $MyProfileBss = $ProfileData->ProfileUserBss()->where('id', $SheckMyProfIDBss)->select('id')->first();
            if($MyProfileBss) { 
                $request->validate([
                    'passwordSetting' => 'required|string',
                    'numberPay' => 'required|string',
                ]);
                $passwordStingHa = strip_tags($request->passwordSetting);
                $shekpas = $ProfileData->UserPasswordStting()->where('idbss', $SheckMyProfIDBss)->select('id', 'password')->first();
                $passwordStingHaahing = Hash::make($passwordStingHa);
                $passwordHash = $shekpas->password;
                $redPassword = request('passwordSetting');
                if($passwordHash) {
                    if(Hash::check($redPassword, $passwordHash)) {
                        $numberPay = strip_tags($request->numberPay);
                        $smplIdPay = strip_tags($id);
                        $SheckPay = PaymentMethodUserBss::where([
                            'usernameBss' => $SheckMyProfIDBss, 
                            'id' => $smplIdPay,
                        ])->select('id', 'namepayment', 'TypeNumberPay')->first();
                        if($SheckPay->namepayment == 'CASH' || $SheckPay->namepayment == 'Selefe') {
                            $dataUpt = PaymentMethodUserBss::where('usernameBss', $SheckMyProfIDBss)->select('id', 'namepayment', 'TypeNumberPay', 'TypePayment')->latest()->paginate(10);
                            return response()->json([
                                'message' => " Sorry Your Dont Have relation To Update This Payment ",
                                'typAction' => 12,
                                'data' => $dataUpt,
                            ], 200);
                        }
                        $SheckPayMore = PaymentMethodUserBss::where([
                            'usernameBss' => $SheckMyProfIDBss, 
                            'namepayment' => $SheckPay->namepayment,
                            'TypeNumberPay' => $numberPay,
                        ])->select('id')->first();
                        if($SheckPayMore) {
                            $dataUpt = PaymentMethodUserBss::where('usernameBss', $SheckMyProfIDBss)->select('id', 'namepayment', 'TypeNumberPay', 'TypePayment')->latest()->paginate(10);
                            return response()->json([
                                'message' => " Sorry Your Dont Have relation To Update This Payment ",
                                'typAction' => 11,
                                'data' => $dataUpt,
                            ], 200);
                        } else {
                            $UpdatePay = $SheckPay->update(['TypeNumberPay' => $numberPay]);
                            if($UpdatePay) {
                            $dataUpt = PaymentMethodUserBss::where('usernameBss', $SheckMyProfIDBss)->select('id', 'namepayment', 'TypeNumberPay', 'TypePayment')->latest()->paginate(10);
                                return response()->json([
                                    'message' => " Sussfuly Save Update Active Pament",
                                    'data' => $dataUpt,
                                    'typAction' => 1,
                                ], 200);
                            } else {
                                $dataUpt = PaymentMethodUserBss::where('usernameBss', $SheckMyProfIDBss)->select('id', 'namepayment', 'TypeNumberPay', 'TypePayment')->latest()->paginate(10);
                                return response()->json([
                                    'message' => " From Edart Setting Paye3mt",
                                    'data' => $dataUpt,
                                    'typAction' => 3,
                                ], 200);
                            }
                        }
                    } else {
                            return response()->json([
                                'message' => "Sorry Your Password Setting Is Not Correct",
                                'typAction' => 7,
                                'data' => [],
                            ], 200);
                        }
                } else {
                    return response()->json([
                        'message' => "Sorry Your Are Not Have Password Setting Plz Created",
                        'typAction' => 8,
                        'data' => [],
                    ], 200);
                }
            } else {
                return response()->json([
                    'message' => " error",
                    'data' => 12,
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 99,
            ], 200);
        }
    } //== End Update Payments Methods For Bss Click To Id ==//
    //== End Alls Action From Edart Payment Methods For Bss ==//

    // Start Show Alls Actions From Edart Pay Prodects
    // Start Show Alls Data From Edart Pay Prodects
    function ShowMyAllsPaymentProdects() {
        try {
            $ProfileDat = Auth::user();
            $SheckMyProfIDBss = $ProfileDat->curret_profile_id_Bss;
            $SheckMyProfIDBssTv = $ProfileDat->current_my_travel;
            $currentProfileBssOrTravNow = $SheckMyProfIDBss ? $SheckMyProfIDBss : $SheckMyProfIDBssTv;
            $MyProfileBss = $ProfileDat->ProfileUserBss()->where('id', $SheckMyProfIDBss)->select('id')->first();
            $MyProfileTrav = $ProfileDat->EdaretMewevin()->where([
                'confirmUser' => 1,
                'confirmBss' => 1,
                'typerelation' => 1,
                'idbss' => $SheckMyProfIDBssTv,
            ])
            ->join('profile_user_bsses', 'edaret_mewevins.idbss', 'profile_user_bsses.id')
            ->select('profile_user_bsses.*')
            ->latest()->first();

            if($MyProfileBss || $MyProfileTrav) {
                $payments = PaymentProdectUserBss::where('idbss', $currentProfileBssOrTravNow)->select('id', 'namezeboune', 'numberzeboune', 'totalprodectspay', 'totalpriceprodectspay',  'typepayment', 'paymentmethod', 'numberpaymentmethod', 'currentPay', 'allquantitelprodect')->latest()->paginate(10);
                return response()->json([
                    'message' => 'Sorry Error Not Found Semthing Error',
                    'data' => $payments,
                    'Id Now' => $currentProfileBssOrTravNow,
                ], 200);
            } else {
                return response()->json([
                    'message' => 'Sorry Error Not Found Semthing Error',
                    'data' => 7,
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 99,
            ], 200);
        }
    } // End Show Alls Data From Edart Pay Prodects

    // Start Show My Payment Prodect For Id
    function ShowMyPaymentProdectID($PaymentPay) {
        try {
            $ProfileDat = Auth::user();
            $SheckMyProfIDBss = $ProfileDat->curret_profile_id_Bss;
            $SheckMyProfIDBssTv = $ProfileDat->current_my_travel;
            $ProfileBssLoginNowOrTrave = $SheckMyProfIDBssTv || $SheckMyProfIDBss;
            $MyProfileBss = $ProfileDat->ProfileUserBss()->where('id', $SheckMyProfIDBss)->select('id')->first();
            $MyProfileTrav = $ProfileDat->EdaretMewevin()->where([
                'confirmUser' => 1,
                'confirmBss' => 1,
                'typerelation' => 1,
                'idbss' => $SheckMyProfIDBssTv,
            ])
            ->join('profile_user_bsses', 'edaret_mewevins.idbss', 'profile_user_bsses.id')
            ->select('profile_user_bsses.*')
            ->latest()->first();
            $dataForEftProd = [];

            if($MyProfileBss || $MyProfileTrav) {
                $MyPaymentMethod = PaymentProdectUserBss::where([
                    'idbss' => $ProfileBssLoginNowOrTrave,
                    'id' => $PaymentPay,
                ])->select('id', 'nameprodectspay', 'priceprodectspay', 'namezeboune', 'numberzeboune', 'totalprodectspay', 'totalpriceprodectspay', 'imgconfirmedpay', 'typepayment', 'paymentmethod', 'numberpaymentmethod', 'allquantitelprodect', 'idMeshole', 'typMeshole', 'created_at', 'quantiteyprodectspay', 'currentPay', 'currentPay')->first();
                
                foreach (json_decode($MyPaymentMethod->nameprodectspay) as $index => $nameProd) {
                    $price = json_decode($MyPaymentMethod->priceprodectspay)[$index];
                    $quantite = json_decode($MyPaymentMethod->quantiteyprodectspay)[$index];
                    $dataForEftProd[] = [
                        'id' => $index+1,
                        'name' => $nameProd,
                        'price' => "$MyPaymentMethod->currentPay $price",
                        'quantite' => $quantite
                    ];
                }

                $AllDataToShow = [
                    'id' => $MyPaymentMethod->id,
                    'priceprodectspay'=> $MyPaymentMethod->priceprodectspay,
                    'namezeboune'=> $MyPaymentMethod->namezeboune,
                    'numberzeboune' => $MyPaymentMethod->numberzeboune,
                    'totalprodectspay' => $MyPaymentMethod->totalprodectspay,
                    'totalpriceprodectspay' => $MyPaymentMethod->totalpriceprodectspay,
                    'imgconfirmedpay' => $MyPaymentMethod->imgconfirmedpay,
                    'typepayment' => $MyPaymentMethod->typepayment,
                    'paymentmethod' => $MyPaymentMethod->paymentmethod,
                    'numberpaymentmethod' => $MyPaymentMethod->numberpaymentmethod,
                    'allquantitelprodect' => $MyPaymentMethod->allquantitelprodect,
                    'idMeshole' => $MyPaymentMethod->idMeshole,
                    'typMeshole' => $MyPaymentMethod->typMeshole,
                    'created_at' => $MyPaymentMethod->created_at,
                    'currentPay' => $MyPaymentMethod->currentPay,
                ];

                if($MyPaymentMethod->typMeshole == 2) {
                    $SheclUser_IdMeshol = EdaretMewevin::where([
                        'idbss' => $ProfileBssLoginNowOrTrave,
                        'id' => $MyPaymentMethod->idMeshole,
                    ])->select('user_id')->first();
                    $ProfileMeshole = ProfileUser::where([
                        'user_id' => $SheclUser_IdMeshol->user_id,
                    ])->select('NumberPhone')->first();
                    $AllDataToShow['datMeshole'] = $ProfileMeshole->NumberPhone;
                }

                

                return response()->json([
                    'message' => 'Show My Payment Pethod Data',
                    'typAction' => 1,
                    'dataForEftProd' => $dataForEftProd,
                    'data' => $AllDataToShow,
                ], 200);

            } else {
                return response()->json([
                    'message' => 'Error Semthing Not Found',
                    'data' => 3,
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 99,
            ], 200);
        }
    } // End Show My Payment Prodect For Id

    // Start To Store Noew Prodect Data
    function StoragePayMyProdectConfirmed(Request $request) {
        try {
            $ProfileData = Auth::user();
            $SheckMyProfIDBss = $ProfileData->curret_profile_id_Bss;
            $SheckMyProfIDBssTv = $ProfileData->current_my_travel;
            $spmlIdBssLoginNow = $SheckMyProfIDBss || $SheckMyProfIDBssTv;
            $MyProfileBss = $ProfileData->ProfileUserBss()->where('id', $SheckMyProfIDBss)->select('id')->first();
            $MyProfileTrav = $ProfileData->EdaretMewevin()->where([
                'confirmUser' => 1,
                'confirmBss' => 1,
                'typerelation' => 1,
                'idbss' => $SheckMyProfIDBssTv,
            ])
            ->join('profile_user_bsses', 'edaret_mewevins.idbss', 'profile_user_bsses.id')
            ->select('profile_user_bsses.*')
            ->latest()->first();
            if($MyProfileBss) {
                $request->validate([
                    'productID' => 'required|array',
                    'productID.*' => 'integer|exists:prodect_users,id',
                    'quantities' => 'required|array',
                    'quantities.*' => 'integer|min:1',
                    'PaymentMethod' => 'integer|exists:payment_method_user_bsses,id',
                    'NamberZeboune' => 'required|exists:zeboune_for_users,numberPhone',
                    'imagePayment' => 'nullable|image|mimes:jpeg,jpg|max:4000',
                ]);
                $currentPay = CurrentPaymentForUseBss::where('usernameBss', $spmlIdBssLoginNow)->select('currentCantry')->first();
                $MyZeboune = ZebouneForUser::where('numberPhone', strip_tags($request->NamberZeboune))->select('id', 'user_id', 'TypeAccounte', 'username', 'numberPhone', 'HaletDeyn', 'TotelDeyn', 'TotelBayMent')->first();
                $TotelProdects = count($request->productID);
                $TotelPrices = 0;
                $productNamesPay = [];
                $NamePerodectPay = [];
                $IDsPerodectPay = [];
                $TotelPrices = 0;
                $ValQuanteOne = $request->quantities;
                try { //productID
                    DB::beginTransaction();
                    $totalPrice = 0;
                    $PricePerodectPay = [];
                    $totalquantite = 0;
                    $PaymentMethod = PaymentMethodUserBss::where([
                        'usernameBss' => $spmlIdBssLoginNow,
                        'id' => strip_tags($request->PaymentMethod),
                    ])->select('id', 'namepayment', 'TypeNumberPay')->first();
                    foreach ($request->productID as $index => $productId) {
                        $product = ProdectUser::lockForUpdate()->find($productId);
                        if (!$product) {
                            return response()->json([
                                'message' => 'Sorry Error Not Found Semthing Error',
                                'typAction' => 10,
                                'data' => [],
                            ], 200);
                            continue;
                        }
                        $quantity = $request->quantities[$index];
                        if ($quantity > $product->totaleinstorage) {
                            $errotm = [
                                "name" => $product->name,
                                "quantite" => $product->totaleinstorage,
                                "quantitetopay" => $quantity,
                            ];
                            return response()->json([
                                'message' => "Sorry Prodect name $product->name Has ToTale $product->totaleinstorage for pay $quantity",
                                'typAction' => 7,
                                'data' => $errotm,
                            ], 200);
                            continue;
                        }

                        if (!is_numeric($quantity) || $quantity <= 0) {
                            return response()->json([
                                'message' => 'Sorry Error Not Found Semthing Error',
                                'typAction' => 6,
                                'data' => [],
                            ], 200);
                            continue;
                        }
                        
                        $product->decrement('totaleinstorage', $quantity);
                        
                        $TotelPrices += $product->price * $quantity;
                        $PricePerodectPay[] = $product->price;
                        $NamePerodectPay[] = $product->name;
                        $IDsPerodectPay[] = $product->id;
                        $totalquantite += $request->quantities[$index];
                    }
                    DB::commit();
                    $PaymentUpdate = [
                        'user_id' => $ProfileData->id,
                        'idbss' => $spmlIdBssLoginNow,
                        'typeaccountzeboune' => $MyZeboune->TypeAccounte,
                        'idaccountzeboune' => $MyZeboune->id,
                        'totalprodectspay' => $TotelProdects,
                        'totalpriceprodectspay' => $TotelPrices,
                        'nameprodectspay' => json_encode($NamePerodectPay),
                        'idprodectspay' => json_encode($IDsPerodectPay),
                        'priceprodectspay' => json_encode($PricePerodectPay),
                        'quantiteyprodectspay' => json_encode($ValQuanteOne),
                        'allquantitelprodect' => $totalquantite,
                        'currentPay' => $currentPay->currentCantry,
                        'idMeshole' => $spmlIdBssLoginNow,
                        'typMeshole' => 1,
                        'paymentmethod' => $PaymentMethod->namepayment,
                    ];

                    if($MyZeboune->TypeAccounte === "Online") {
                        $ProfileZeboune = ProfileUser::where([
                            'user_id' => $MyZeboune->user_id,
                        ])->select('id','name', 'NumberPhone')->first();
                        $PaymentUpdate['namezeboune'] = $ProfileZeboune->name;
                        $PaymentUpdate['numberzeboune'] = $ProfileZeboune->NumberPhone;
                    } else {
                        $PaymentUpdate['namezeboune'] = $MyZeboune->username;
                        $PaymentUpdate['numberzeboune'] = $MyZeboune->numberPhone;
                    }

                    if($PaymentMethod->namepayment == 'Selefe' || $PaymentMethod->namepayment === 'CASH' ) {
                            if($MyZeboune->HaletDeyn === 0 || $MyZeboune->HaletDeyn == 2) {
                                return response()->json([
                                    'message' => 'Sory This User Has Not Have Relaction To do THIS tYPE',
                                    'typAction' => 12,
                                    'data' => [],
                                ]);
                            }
                            if($MyZeboune->HaletDeyn === 1) {
                                $MyZeboune->increment('TotelDeyn', $TotelPrices);
                                $PaymentUpdate['typepayment'] = 1;
                            }
                            $PaymentUpdate['typepayment'] = 1;
                            $PaymentUpdate['numberpaymentmethod'] = null;
                    } else {
                        $PaymentUpdate['numberpaymentmethod'] = $PaymentMethod->TypeNumberPay;
                        $PaymentUpdate['typepayment'] = 1;
                    }
                    if($request->hasFile('imagePayment')) {
                        $image = $request->file('imagePayment');
                        $gen = hexdec(uniqid());
                        $ext = strtolower($image->getClientOriginalExtension());
                        $namePod = $gen . '' . $ext;
                        $location = 'Payment_Prodect_Pay/';
                        $source = $location.$namePod;
                        $name = $gen. '.' .$ext;
                        $source = $location.$name;
                        $image->move($location,$name);
                        $PaymentUpdate['imgconfirmedpay'] = $source;
                    }
                // return response()->json(['dat' => 'Ok New', 'dat M' => $PaymentUpdate,]);
                    $ConfirmedUpdatePayment = PaymentProdectUserBss::create($PaymentUpdate);
                    if($ConfirmedUpdatePayment) {
                        $MyZeboune->increment('TotelBayMent', 1);
                        return response()->json([
                            'message' => 'SuccessFuly Create Payment Prodect',
                            'typAction' => 1,
                            'data' => [],
                        ], 201);
                    } else {
                        return response()->json([
                            'message' => 'Sorry Error Not Found Semthing Error',
                            'typAction' => 2,
                            'data' => [],
                        ]);
                    }
                } catch (\Exception $e) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'حدث خطأ غير متوقع: ' . $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine()
                    ], 500);
                }
            } else if($MyProfileTrav) {
                $request->validate([
                    'productID' => 'required|array',
                    'productID.*' => 'integer|exists:prodect_users,id',
                    'quantities' => 'required|array',
                    'quantities.*' => 'integer|min:1',
                    'PaymentMethod' => 'integer|exists:payment_method_user_bsses,id',
                    'NamberZeboune' => 'required|integer|exists:zeboune_for_users,numberPhone',
                    'imagePayment' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                ]);
                $currentPay = CurrentPaymentForUseBss::where('usernameBss', $spmlIdBssLoginNow)->select('currentCantry')->first();
                $MyZeboune = ZebouneForUser::where('numberPhone', strip_tags($request->NamberZeboune))->select('id', 'user_id', 'TypeAccounte', 'username', 'numberPhone', 'HaletDeyn', 'TotelDeyn', 'TotelBayMent')->first();
                $TotelProdects = count($request->productID);
                $TotelPrices = 0;
                $productNamesPay = [];
                $NamePerodectPay = [];
                $IDsPerodectPay = [];
                $TotelPrices = 0;

                try {
                    DB::beginTransaction();
                    
                    $totalPrice = 0;
                    $PricePerodectPay = [];
                    $totalquantite = 0;
                    $ValQuanteOne = $request->quantities;
                    $MyProfileTravSmp = $ProfileData->EdaretMewevin()->where([
                        'user_id' => $ProfileData->id,
                        'idbss' => $SheckMyProfIDBssTv,
                        'typerelation' => 1,
                        'confirmBss' => 1,
                        'confirmUser' => 1,
                    ])->select('id', 'edartPaymentProdects', 'totaledartPayProds', 'PaymentEcteronect', 'totaledartPayEct')->first();
                    if($MyProfileTravSmp->edartPaymentProdects != 1) {
                        return response()->json([
                            'message' => 'Sorry Error Not Found Semthing Error',
                            'data' => [],
                            'typAction' => 16,
                        ]);
                    }
                    $PaymentMethod = PaymentMethodUserBss::where([
                            'usernameBss' => $SheckMyProfIDBssTv,
                            'id' => strip_tags($request->PaymentMethod),
                        ])->select('id', 'namepayment', 'TypeNumberPay')->first();
                        foreach ($request->productID as $index => $productId) {
                            $product = ProdectUser::lockForUpdate()->find($productId);
                            if (!$product) {
                                return response()->json([
                                    'message' => 'Sorry Error Not Found Semthing Error',
                                    'typAction' => 10,
                                    'data' => [],
                                ], 200);
                                continue;
                            }
                            $quantity = $request->quantities[$index];
                            if ($quantity > $product->totaleinstorage) {
                                $errotm = [
                                    "name" => $product->name,
                                    "quantite" => $product->totaleinstorage,
                                    "quantitetopay" => $quantity,
                                ];
                                return response()->json([
                                    'message' => "Sorry Prodect name $product->name Has ToTale $product->totaleinstorage for pay $quantity",
                                    'typAction' => 7,
                                    'data' => $errotm,
                                ], 200);
                                continue;
                            }
                            if (!is_numeric($quantity) || $quantity <= 0) {
                                return response()->json([
                                    'message' => 'Sorry Error Not Found Semthing Error',
                                    'typAction' => 6,
                                    'data' => [],
                                ], 200);
                                continue;
                            }
                            $product->decrement('totaleinstorage', $quantity);
                            $TotelPrices += $product->price * $quantity;
                            $PricePerodectPay[] = $product->price;
                            $NamePerodectPay[] = $product->name;
                            $IDsPerodectPay[] = $product->id;
                            $totalquantite += $request->quantities[$index];
                        }
                        DB::commit();
                        $PaymentUpdate = [
                            'user_id' => $MyProfileTrav->user_id,
                            'idbss' => $SheckMyProfIDBssTv,
                            'typeaccountzeboune' => $MyZeboune->TypeAccounte,
                            'idaccountzeboune' => $MyZeboune->id,
                            'totalprodectspay' => $TotelProdects,
                            'totalpriceprodectspay' => $TotelPrices,
                            'nameprodectspay' => json_encode($NamePerodectPay),
                            'idprodectspay' => json_encode($IDsPerodectPay),
                            'priceprodectspay' => json_encode($PricePerodectPay),
                            'quantiteyprodectspay' => json_encode($ValQuanteOne),
                            'allquantitelprodect' => $totalquantite,
                            'currentPay' => $currentPay->currentCantry,
                            'idMeshole' => $MyProfileTravSmp->id,
                            'paymentmethod' => $PaymentMethod->namepayment,
                        ];

                        if($MyZeboune->typeaccountzeboune === "Online") {
                            $ProfileZeboune = ProfileUser::where([
                                'user_id' => $MyZeboune->user_id,
                            ])->select('name', 'NumberPhone')->first();
                            $PaymentUpdate['namezeboune'] = $ProfileZeboune->name;
                            $PaymentUpdate['numberzeboune'] = $ProfileZeboune->NumberPhone;
                        } else {
                            $PaymentUpdate['namezeboune'] = $MyZeboune->username;
                            $PaymentUpdate['numberzeboune'] = $MyZeboune->numberPhone;
                        }

                        if($PaymentMethod->namepayment === 'Selefe' ||
                            $PaymentMethod->namepayment === 'CASH' ) {
                            if($MyZeboune->HaletDeyn === 0 || $MyZeboune->HaletDeyn == 2) {
                                return response()->json([
                                    'message' => 'Sory This User Has Not Have Relaction To do THIS tYPE',
                                    'typAction' => 12,
                                    'data' => [],
                                ]);
                            }
                            $PaymentUpdate['typMeshole'] = 2;
                            if($MyZeboune->HaletDeyn === 1) {
                                $MyZeboune->increment('TotelDeyn', $TotelPrices);
                                $PaymentUpdate['typepayment'] = 1;
                            }
                            $PaymentUpdate['typepayment'] = 1;
                            $PaymentUpdate['numberpaymentmethod'] = null;
                        } else if($MyProfileTravSmp->PaymentEcteronect === 1) {
                            $PaymentUpdate['numberpaymentmethod'] = $PaymentMethod->TypeNumberPay;
                            $PaymentUpdate['typepayment'] = 1;
                            $PaymentUpdate['typMeshole'] = 2;
                            $MyProfileTravSmp->increment('totaledartPayEct', 1);
                        } else {
                            $PaymentUpdate['numberpaymentmethod'] = $PaymentMethod->TypeNumberPay;
                            $PaymentUpdate['typepayment'] = 0;
                            $PaymentUpdate['typMeshole'] = 0;
                        }

                        $MyZeboune->increment('TotelBayMent', 1);

                        if($request->hasFile('imagePayment')) {
                            $image = $request->file('imagePayment');
                            $gen = hexdec(uniqid());
                            $ext = strtolower($image->getClientOriginalExtension());
                            $namePod = $gen . '' . $ext;
                            $location = 'Payment_Prodect_Pay/';
                            $source = $location.$namePod;
                            $name = $gen. '.' .$ext;
                            $source = $location.$name;
                            $image->move($location,$name);
                            $PaymentUpdate['imgconfirmedpay'] = $source;
                        }

                        $ConfirmedUpdatePayment = PaymentProdectUserBss::create($PaymentUpdate);
                        if($ConfirmedUpdatePayment) {
                            $MyProfileTravSmp->increment('totaledartPayProds', 1);
                            return response()->json([
                                'message' => 'SuccessFuly Create Payment Prodect',
                                'typAction' => 1,
                                'data' => [],
                            ], 201);
                        } else {
                            return response()->json([
                                'message' => 'Sorry Error Not Found Semthing Error',
                                'data' => [],
                                'typAction' => 2,
                            ]);
                        }
                } catch (\Exception $e) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'حدث خطأ غير متوقع: ' . $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine()
                    ], 500);
                }
            } else {
                return response()->json([
                    'message' => 'Sorry Error Not Found Semthing Not Correct',
                    'data' => 40,
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 999,
            ], 200);
        }
    } //=== End To Store Noew Prodect Data ==//

    // Start Confirmed Payment Prodect
    function ActiveConfirmendPAaymentProdects(Request $request, $PaymentPay) {
        try {
            $PaymentPaySpl = strip_tags($PaymentPay);
            $ProfileData = Auth::user();
            $SheckMyProfIDBss = $ProfileData->curret_profile_id_Bss;
            $SheckMyProfIDBssTv = $ProfileData->current_my_travel;
            $MyProfileBss = $ProfileData->ProfileUserBss()->where('id', $SheckMyProfIDBss)->select('id')->first();
            $MyProfileTrav = $ProfileData->EdaretMewevin()->where([
                'confirmUser'=> 1,
                'confirmBss' => 1,
                'typerelation' => 1,
                'idbss' => $SheckMyProfIDBssTv,
            ])
            ->join('profile_user_bsses', 'edaret_mewevins.idbss', 'profile_user_bsses.id')
            ->select('profile_user_bsses.*')
            ->latest()->first();

            if($MyProfileBss) {
                $request->validate([
                    'passwordSetting' => 'required|string',
                ]);
                $shekpas = $ProfileData->UserPasswordStting()->where('idbss', $SheckMyProfIDBss)->select('id', 'password')->first();
                if($shekpas=== null) {
                    return response()->json([
                        'message' => 'Your`e Not Have Eny Password Setting Son Plz Go To Create',
                        'typAction' => 5,
                        'data' => [],
                    ]);
                }
                $passwordStingHa = strip_tags($request->passwordSetting);
                $passwordStingHaahing = Hash::make($passwordStingHa);
                $passwordHash = $shekpas->password;
                $redPassword = request('passwordSetting');
                    if($passwordHash) {
                        if(Hash::check($redPassword, $passwordHash)) {
                            $MyPaymentMethod = PaymentProdectUserBss::where([
                                'id' => $PaymentPay,
                                'idbss' => $SheckMyProfIDBss,
                            ])->select('id', 'idaccountzeboune', 'typepayment', 'paymentmethod', 'idMeshole', 'typMeshole', 'typMeshole')->first();
                            $MyZeboune = ZebouneForUser::where([
                                'id' => $MyPaymentMethod->idaccountzeboune,
                                'usernameBss' => $SheckMyProfIDBss,
                            ])->select('id', 'TotelDeyn', 'TotelBayMent')->first();
                            if($MyPaymentMethod->typepayment == 1) {
                                $MyPaymentMethodUpdate = PaymentProdectUserBss::where('idbss', $SheckMyProfIDBss)->select('id', 'namezeboune', 'numberzeboune', 'totalprodectspay', 'totalpriceprodectspay',  'typepayment', 'paymentmethod', 'numberpaymentmethod', 'currentPay', 'allquantitelprodect')->latest()
                                    ->paginate(10);
                                return response()->json([
                                    'message' => 'Sorry Your Are One Realy Confirmed Pyment Prodect',
                                    'typAction' => 13,
                                    'data' => $MyPaymentMethodUpdate,
                                ], 200);
                            } else if($MyPaymentMethod->typepayment == 2) {
                                $MyPaymentMethodUpdate = PaymentProdectUserBss::where('idbss', $SheckMyProfIDBss)->select('id', 'namezeboune', 'numberzeboune', 'totalprodectspay', 'totalpriceprodectspay',  'typepayment', 'paymentmethod', 'numberpaymentmethod', 'currentPay', 'allquantitelprodect')->latest()
                                    ->paginate(10);
                                return response()->json([
                                    'message' => 'Sorry Your Are One Realy CDscConfirmed Pyment Prodect',
                                    'typAction' => 14,
                                    'data' => $MyPaymentMethodUpdate,
                                ], 200);
                            } else if($MyPaymentMethod->typepayment == 0) {
                                
                                if($MyPaymentMethod->paymentmethod === 'CASH' ||
                                    $MyPaymentMethod->paymentmethod === 'Selefe' 
                                    ) {
                                    if($MyPaymentMethod->paymentmethod === 'Selefe') {
                                        $MyZeboune->decrement('TotelDeyn', $MyPaymentMethod->totalpriceprodectspay);
                                    }
                                    $UpdateDat = [
                                        'typepayment' => 1,
                                        'typMeshole' => 1,
                                        'idMeshole' => $SheckMyProfIDBss,
                                    ];
                                    
                                } else {
                                    $UpdateDat = [
                                        'typepayment' => 1,
                                        'typMeshole' => 1,
                                        'idMeshole' => $SheckMyProfIDBss,
                                    ];
                                }
                                $updatPayment = $MyPaymentMethod->update($UpdateDat);
                                if($updatPayment) {
                                    $MyZeboune->increment('TotelBayMent', 1);
                                    $MyPaymentMethodUpdate = PaymentProdectUserBss::where('idbss', $SheckMyProfIDBss)->select('id', 'namezeboune', 'numberzeboune', 'totalprodectspay', 'totalpriceprodectspay',  'typepayment', 'paymentmethod', 'numberpaymentmethod', 'currentPay', 'allquantitelprodect')->latest()
                                    ->paginate(10);
                                    return response()->json([
                                        'message' => 'SuccessFuly Confirmed Realation Payment For Prodect',
                                        'typAction' => 1,
                                        'data' => $MyPaymentMethodUpdate,
                                    ], 201);
                                } else {
                                    return response()->json([
                                        'message' => 'Error Semthing Not Found Plz Later',
                                        'typAction' => 2,
                                        'data' => [],
                                    ], 200);
                                }
                            }
                            
                        } else {
                            return response()->json([
                                'message' => 'Sorry Your Paawiord Is Not Correct',
                                'typAction' => 6,
                                'data' => [],
                            ], 200);
                        }
                    
                    } else {
                        return response()->json([
                            'message' => 'Your`e Not Have Eny Password Setting Son Plz Go To Create',
                            'typAction' => 5,
                            'data' => [],
                        ]);
                    }
            } else if($MyProfileTrav) {
                $MyTrf = $ProfileData->EdaretMewevin()->where('idbss', $SheckMyProfIDBssTv)->select('id', 'edartPaymentProdects', 'totaledartPayProds', 'PaymentEcteronect', 'totaledartPayEct')->first();
                $MyPaymentMethod = PaymentProdectUserBss::where([
                    'id' => $PaymentPaySpl,
                    'idbss' => $SheckMyProfIDBssTv,
                    ])->select('id', 'idaccountzeboune', 'typepayment', 'idprodectspay', 'paymentmethod', 'idMeshole', 'typMeshole', 'typMeshole')->first();
                $MyZeboune = ZebouneForUser::where([
                    'id' => $MyPaymentMethod->idaccountzeboune,
                    'usernameBss' => $SheckMyProfIDBssTv,
                ])->select('id', 'TotelDeyn', 'TotelBayMent')->first();
                if($MyPaymentMethod) {
                    if($MyTrf->edartPaymentProdects === 1) {
                        if($MyPaymentMethod->typepayment == 1) {
                            $MyPaymentMethodUpdate = PaymentProdectUserBss::where('idbss', $SheckMyProfIDBssTv)->select('id', 'namezeboune', 'numberzeboune', 'totalprodectspay', 'totalpriceprodectspay',  'typepayment', 'paymentmethod', 'numberpaymentmethod', 'currentPay', 'allquantitelprodect')->latest()
                                ->paginate(10);
                            return response()->json([
                                'message' => 'Sorry Your Are One Realy Confirmed Pyment Prodect',
                                'typAction' => 13,
                                'data' => $MyPaymentMethodUpdate,
                            ], 200);
                        } else if($MyPaymentMethod->typepayment == 2) {
                            $MyPaymentMethodUpdate = PaymentProdectUserBss::where('idbss', $SheckMyProfIDBssTv)->select('id', 'namezeboune', 'numberzeboune', 'totalprodectspay', 'totalpriceprodectspay',  'typepayment', 'paymentmethod', 'numberpaymentmethod', 'currentPay', 'allquantitelprodect')->latest()
                                ->paginate(10);
                            return response()->json([
                                'message' => 'Sorry Your Are One Realy CDscConfirmed Pyment Prodect',
                                'typAction' => 14,
                                'data' => $MyPaymentMethodUpdate,
                            ], 200);
                        } else if($MyPaymentMethod->typepayment == 0) {
                            
                            if($MyPaymentMethod->paymentmethod === 'CASH' ||
                                $MyPaymentMethod->paymentmethod === 'Selefe' 
                                ) {
                                if($MyPaymentMethod->paymentmethod === 'Selefe') {
                                    if($MyZeboune->MyZeboune !== 1) {
                                        foreach (json_decode($MyPaymentMethod->idprodectspay) as $index => $productId) {
                                            $product = ProdectUser::find($productId)->select('id', 'totaleinstorage')->first();
                                            
                                            if (!$product) {
                                                return response()->json([
                                                    'message' => 'Sorry Error Not Found Semthing Error',
                                                    'typAction' => 5,
                                                    'data' => [],
                                                ], 200);
                                                continue;
                                            }
                                            $quantity = json_decode($MyPaymentMethod->quantiteyprodectspay)[$index];
                                            
                                            $product->increment('totaleinstorage', $quantity);
                                        }
                                        $UpdateDat = [
                                            'typepayment' => 3,
                                            'typMeshole' => 2,
                                            'idMeshole' => $MyTrf->id,
                                        ];
                                        $updatPayment = $MyPaymentMethod->update($UpdateDat);
                                        return response()->json([
                                            'message' => 'Sorry Your Not Have This Slahie Payment Elect',
                                            'typAction' => 4,
                                            'data' => [],
                                        ]);
                                        
                                    }
                                    $MyZeboune->increment('TotelDeyn', $MyPaymentMethod->totalpriceprodectspay);
                                    $UpdateDat = [
                                        'typepayment' => 1,
                                        'typMeshole' => 2,
                                        'idMeshole' => $MyTrf->id,
                                    ];
                                }
                                $UpdateDat = [
                                    'typepayment' => 1,
                                    'typMeshole' => 2,
                                    'idMeshole' => $MyTrf->id,
                                ];
                            } else {

                                if($MyTrf->PaymentEcteronect != 1) {
                                    $MyPaymentMethodUpdate = PaymentProdectUserBss::where('idbss', $SheckMyProfIDBssTv)->select('id', 'namezeboune', 'numberzeboune', 'totalprodectspay', 'totalpriceprodectspay',  'typepayment', 'paymentmethod', 'numberpaymentmethod', 'currentPay', 'allquantitelprodect')->latest()
                                    ->paginate(10);
                                    return response()->json([
                                        'message' => 'Sorry Your Not Have This Slahie Payment Elect',
                                        'typAction' => 9,
                                        'data' => $MyTrf->id,
                                    ]);
                                }
                                $UpdateDat = [
                                    'typepayment' => 1,
                                    'typMeshole' => 2,
                                    'idMeshole' => $MyTrf->id,
                                ];
                                $MyTrf->increment('totaledartPayEct', 1);
                            }
                            $updatPayment = $MyPaymentMethod->update($UpdateDat);
                            if($updatPayment) {
                                $MyZeboune->increment('TotelBayMent', 1);
                                $MyPaymentMethodUpdate = PaymentProdectUserBss::where('idbss', $SheckMyProfIDBssTv)->select('id', 'namezeboune', 'numberzeboune', 'totalprodectspay', 'totalpriceprodectspay',  'typepayment', 'paymentmethod', 'numberpaymentmethod', 'currentPay', 'allquantitelprodect')->latest()
                                ->paginate(10);
                                return response()->json([
                                    'message' => 'SuccessFuly Confirmed Realation Payment For Prodect',
                                    'typAction' => 1,
                                    'data' => $MyPaymentMethodUpdate,
                                    'MyPaymentMethod' => $MyPaymentMethod,
                                    'MyTrf' => $MyTrf,
                                ], 201);
                            } else {
                                $MyPaymentMethodUpdate = PaymentProdectUserBss::where('idbss', $SheckMyProfIDBssTv)->select('id', 'namezeboune', 'numberzeboune', 'totalprodectspay', 'totalpriceprodectspay',  'typepayment', 'paymentmethod', 'numberpaymentmethod', 'currentPay', 'allquantitelprodect')->latest()
                                ->paginate(10);
                                return response()->json([
                                    'message' => 'Error Semthing Not Found Plz Later',
                                    'typAction' => 2,
                                    'data' => $MyPaymentMethodUpdate,
                                ], 200);
                            }
                        }
                            
                    } else {
                        return response()->json([
                            'message' => 'Sorry Your Not Have This Slahie',
                            'typAction' => 16,
                            'data' => [],
                        ]);
                    }
                } else {
                    return response()->json([
                        'message' => 'Sorry Hele Dont Have This Payment Prodect',
                        'typAction' => 6,
                        'data' => [],
                    ], 200);
                }

            }  else {
                return response()->json([
                    'message' => 'Your`e Not Have Relation To Do This Action',
                    'typAction' => 5,
                    'data' => [],
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 99,
            ], 200);
        }
    } //== End Confirmed Payment Prodect ==//

    // Start Dsc Confirmed Payment Prodect
    function DescConfirmendPAaymentProdects(Request $request, $PaymentPay) {
        try {
            $PaymentPaySpl = strip_tags($PaymentPay);
            $DataProfile = Auth::user();
            $SheckMyProfIDBss = $DataProfile->curret_profile_id_Bss;
            $SheckMyProfIDBssTv = $DataProfile->current_my_travel;
            $MyProfileBss = $DataProfile->ProfileUserBss()->where('id', $SheckMyProfIDBss)->select('id')->first();
            $MyProfileTrav = $DataProfile->EdaretMewevin()->where([
                'confirmUser' => 1,
                'confirmBss' => 1,
                'typerelation' => 1,
                'idbss' => $SheckMyProfIDBssTv,
            ])
            ->join('profile_user_bsses', 'edaret_mewevins.idbss', 'profile_user_bsses.id')
            ->select('profile_user_bsses.*')
            ->latest()->first();

            if($MyProfileBss) {
                $request->validate([
                    'passwordSetting' => 'required|string',
                ]);
                $shekpas = $DataProfile->UserPasswordStting()->where('idbss', $SheckMyProfIDBss)->select('id', 'password')->first();
                if($shekpas === null) {
                    return response()->json([
                        'message' => 'Your`e Not Have Eny Password Setting Son Plz Go To Create',
                        'typAction' => 5,
                        'data' => [],
                    ]);
                }
                $passwordStingHa = strip_tags($request->passwordSetting);
                $passwordStingHaahing = Hash::make($passwordStingHa);
                $passwordHash = $shekpas->password;
                $redPassword = request('passwordSetting');
                    if($passwordHash) {
                        if(Hash::check($redPassword, $passwordHash)) {
                            $MyPaymentMethod = PaymentProdectUserBss::where('id', $PaymentPaySpl)->first();
                            if($MyPaymentMethod->typepayment == 1) {
                                $MyPaymentMethodUpdate = PaymentProdectUserBss::where('idbss', $SheckMyProfIDBss)->select('id', 'namezeboune', 'numberzeboune', 'totalprodectspay', 'totalpriceprodectspay',  'typepayment', 'paymentmethod', 'numberpaymentmethod', 'currentPay', 'allquantitelprodect')->latest()
                                ->paginate(10);
                                return response()->json([
                                    'message' => 'Sorry Your Are One Realy Confirmed Pyment Prodect',
                                    'typAction' => 13,
                                    'data' => $MyPaymentMethodUpdate,
                                ], 200);
                            } else if($MyPaymentMethod->typepayment == 2) {
                                $MyPaymentMethodUpdate = PaymentProdectUserBss::where('idbss', $SheckMyProfIDBss)->select('id', 'namezeboune', 'numberzeboune', 'totalprodectspay', 'totalpriceprodectspay',  'typepayment', 'paymentmethod', 'numberpaymentmethod', 'currentPay', 'allquantitelprodect')->latest()
                                ->paginate(10);
                                return response()->json([
                                    'message' => 'Sorry Your Are One Realy CDscConfirmed Pyment Prodect',
                                    'typAction' => 14,
                                    'data' => $MyPaymentMethodUpdate,
                                ], 200);
                            } else if($MyPaymentMethod->typepayment == 0) {
                                $MyZeboune = ZebouneForUser::where([
                                    'id' => $MyPaymentMethod->idaccountzeboune,
                                    'usernameBss' => $SheckMyProfIDBss,
                                ])->first();
                                
                                if($MyPaymentMethod->paymentmethod === 'CASH' ||
                                    $MyPaymentMethod->paymentmethod === 'Selefe' 
                                    ) {
                                    if($MyPaymentMethod->paymentmethod === 'Selefe') {
                                        $MyZeboune->decrement('TotelDeyn', $MyPaymentMethod->totalpriceprodectspay);
                                    }
                                    $UpdateDat = [
                                        'typepayment' => 2,
                                        'typMeshole' => 1,
                                        'idMeshole' => $SheckMyProfIDBss,
                                    ];
                                    
                                } else {
                                    
                                    $UpdateDat = [
                                        'typepayment' => 2,
                                        'typMeshole' => 1,
                                        'idMeshole' => $SheckMyProfIDBss,
                                    ];
                                }
                                $updatPayment = $MyPaymentMethod->update($UpdateDat);
                                foreach (json_decode($MyPaymentMethod->idprodectspay) as $index => $productId) {
                                    $product = ProdectUser::find($productId);
                                    if (!$product) {
                                        return response()->json([
                                            'message' => 'Sorry Error Not Found Semthing Error',
                                            'typAction' => 22,
                                            'data' => [],
                                        ], 200);
                                        continue;
                                    }
                                    $quantity = json_decode($MyPaymentMethod->quantiteyprodectspay)[$index];
                                    $product->increment('totaleinstorage', $quantity);
                                }
                                if($updatPayment) {
                                    $MyZeboune->decrement('TotelBayMent', 1);
                                    $MyPaymentMethodUpdate = PaymentProdectUserBss::where('idbss', $SheckMyProfIDBss)->select('id', 'namezeboune', 'numberzeboune', 'totalprodectspay', 'totalpriceprodectspay',  'typepayment', 'paymentmethod', 'numberpaymentmethod', 'currentPay', 'allquantitelprodect')->latest()
                                    ->paginate(10);
                                    return response()->json([
                                        'message' => 'SuccessFuly Confirmed Realation Payment For Prodect',
                                        'typAction' => 1,
                                        'data' => $MyPaymentMethodUpdate,
                                    ], 201);
                                } else {
                                    $MyPaymentMethodUpdate = PaymentProdectUserBss::where('idbss', $SheckMyProfIDBss)->select('id', 'namezeboune', 'numberzeboune', 'totalprodectspay', 'totalpriceprodectspay',  'typepayment', 'paymentmethod', 'numberpaymentmethod', 'currentPay', 'allquantitelprodect')->latest()
                                    ->paginate(10);
                                    return response()->json([
                                        'message' => 'Error Semthing Not Found Plz Later',
                                        'typAction' => 2,
                                        'data' => $MyPaymentMethodUpdate,
                                    ], 200);
                                }
                            }
                            
                        } else {
                            return response()->json([
                                'message' => 'Sorry Your Paawiord Is Not Correct',
                                'typAction' => 6,
                                'data' => [],
                            ], 200);
                        }
                    
                    }
            } else if($MyProfileTrav) {
                $MyTrf = $DataProfile->EdaretMewevin()->where([
                    'user_id' => $DataProfile->id,
                    'idbss' => $SheckMyProfIDBssTv,
                    'typerelation' => 1,
                    'confirmBss' => 1,
                    'confirmUser' => 1,
                ])->select('id', 'edartPaymentProdects', 'totaledartPayProds', 'PaymentEcteronect', 'totaledartPayEct')->first();
                $MyPaymentMethod = PaymentProdectUserBss::where([
                    'id' => $PaymentPaySpl,
                    'idbss' => $SheckMyProfIDBssTv,
                    ])->first();
                $MyZeboune = ZebouneForUser::where([
                    'id' => $MyPaymentMethod->idaccountzeboune,
                    'usernameBss' => $SheckMyProfIDBssTv,
                ])->first();
                if($MyPaymentMethod) {
                    if($MyTrf->edartPaymentProdects === 1) {
                        if($MyPaymentMethod->typepayment == 1) {
                            $MyPaymentMethodUpdate = PaymentProdectUserBss::where('idbss', $SheckMyProfIDBssTv)->select('id', 'idMeshole', 'namezeboune', 'numberzeboune', 'totalprodectspay', 'totalpriceprodectspay',  'typepayment', 'paymentmethod', 'numberpaymentmethod', 'currentPay', 'allquantitelprodect')->latest()
                            ->paginate(10);
                            return response()->json([
                                'message' => 'Sorry Your Are One Realy Confirmed Pyment Prodect',
                                'typAction' => 3,
                                'data' => $MyPaymentMethodUpdate,
                            ], 200);
                        } else if($MyPaymentMethod->typepayment == 2) {
                            $MyPaymentMethodUpdate = PaymentProdectUserBss::where('idbss', $SheckMyProfIDBssTv)->select('id', 'namezeboune', 'numberzeboune', 'totalprodectspay', 'totalpriceprodectspay',  'typepayment', 'paymentmethod', 'numberpaymentmethod', 'currentPay', 'allquantitelprodect')->latest()
                            ->paginate(10);
                            return response()->json([
                                'message' => 'Sorry Your Are One Realy CDscConfirmed Pyment Prodect',
                                'typAction' => 14,
                                'data' => $MyPaymentMethodUpdate,
                            ], 200);
                        } else if($MyPaymentMethod->typepayment == 0) {
                            if($MyPaymentMethod->paymentmethod === 'CASH' ||
                                $MyPaymentMethod->paymentmethod === 'Selefe' 
                                ) {
                                if($MyPaymentMethod->paymentmethod === 'Selefe') {
                                    $MyZeboune->decrement('TotelDeyn', $MyPaymentMethod->totalpriceprodectspay);
                                }
                                $UpdateDat = [
                                    'typepayment' => 2,
                                    'typMeshole' => 2,
                                    'idMeshole' => $MyTrf->id,
                                ];
                            } else {
                                if($MyTrf->PaymentEcteronect !== 1) {
                                    $MyPaymentMethodUpdate = PaymentProdectUserBss::where('idbss', $SheckMyProfIDBssTv)->select('id', 'namezeboune', 'numberzeboune', 'totalprodectspay', 'totalpriceprodectspay',  'typepayment', 'paymentmethod', 'numberpaymentmethod', 'currentPay', 'allquantitelprodect')->latest()
                                    ->paginate(10);
                                    return response()->json([
                                        'message' => 'Sorry Your Not Have This Slahie Payment Elect',
                                        'typAction' => 9,
                                        'data' => $MyPaymentMethodUpdate,
                                    ]);
                                }

                                foreach (json_decode($MyPaymentMethod->idprodectspay) as $index => $productId) {
                                    $product = ProdectUser::find($productId);
                                    if (!$product) {
                                        return response()->json([
                                            'message' => 'Sorry Error Not Found Semthing Error',
                                            'typAction' => 22,
                                            'data' => [],
                                        ], 200);
                                        continue;
                                    }
                                    $quantity = json_decode($MyPaymentMethod->quantiteyprodectspay)[$index];
                                    $product->increment('totaleinstorage', $quantity);
                                }
                                $UpdateDat = [
                                    'typepayment' => 2,
                                    'typMeshole' => 2,
                                    'idMeshole' => $MyTrf->id,
                                ];
                                $MyTrf->increment('totaledartPayEct', 1);
                            }
                            $updatPayment = $MyPaymentMethod->update($UpdateDat);
                            if($updatPayment) {
                                $MyZeboune->decrement('TotelBayMent', 1);
                                $MyPaymentMethodUpdate = PaymentProdectUserBss::where('idbss', $SheckMyProfIDBssTv)->latest()
                                ->paginate(10);
                                if($MyPaymentMethod->idMeshole != 1) {
                                    if($MyPaymentMethod->idMeshole == $MyTrf->id) {
                                        $MyTrf->decrement('totaledartPayProds', 1);
                                    } else {
                                        $MyTrf->increment('totaledartPayProds', 1);
                                    }
                                }
                                return response()->json([
                                    'message' => 'SuccessFuly Confirmed Realation Payment For Prodect',
                                    'typAction' => 1,
                                    'data' => $MyPaymentMethodUpdate,
                                ], 201);
                            } else {
                                return response()->json([
                                    'message' => 'Error Semthing Not Found Plz Later',
                                    'typAction' => 2,
                                    'data' => [],
                                ], 200);
                            }
                        }
                            
                    } else {
                        $MyPaymentMethodUpdate = PaymentProdectUserBss::where('idbss', $SheckMyProfIDBssTv)->latest()
                        ->paginate(10);
                        return response()->json([
                            'message' => 'Sorry Your Not Have This Slahie',
                            'typAction' => 16,
                            'data' => $MyPaymentMethodUpdate,
                        ]);
                    }
                } else {
                    return response()->json([
                        'message' => 'Sorry This Password Is Not Corrects Plz Later',
                        'typAction' => 6,
                        'data' => [],
                    ], 200);
                }

            }  else {
                return response()->json([
                    'message' => 'Your`e Not Have Eny Password Setting Son Plz Go To Create',
                    'typAction' => 5,
                    'data' => [],
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 99,
            ], 200);
        }
    } // End Dsc Confirmed Payment Prodect ==//

    // Start Sereach Alls PaymentProdects For My Zeboune
    function SearchForMyZebouneIsDoPaymentProdect($ZebouneId) {
        try {
            $datProfile = Auth::user();
            $SheckMyProfIDBss = $datProfile->curret_profile_id_Bss;
            $MyProfileBss = $datProfile->ProfileUserBss()->where('id', $SheckMyProfIDBss)->select('id')->first();
            $SheckMyProfIDBssTv = $datProfile->current_my_travel;
            $ProfileLoginNowToTrave = $datProfile->current_my_travel || $datProfile->curret_profile_id_Bss;
            $MyProfileTrav = $datProfile->EdaretMewevin()->where([
                'confirmUser' => 1,
                'confirmBss' => 1,
                'typerelation' => 1,
                'idbss' => $SheckMyProfIDBssTv,
            ])
            ->join('profile_user_bsses', 'edaret_mewevins.idbss', 'profile_user_bsses.id')
            ->select('profile_user_bsses.*')
            ->latest()->first();
            $SmplZeouneId = strip_tags($ZebouneId);
            if($MyProfileBss || $MyProfileTrav) {
                $MyZebouneDataPayment = PaymentProdectUserBss::where([
                    'idaccountzeboune' => $SmplZeouneId,
                    'idbss' => $ProfileLoginNowToTrave, 
                ])->latest()->paginate(10);
                if($MyZebouneDataPayment) {
                    return response()->json([
                        'message' => 'ErrSuccessFuly Show Data My Zeboune For Payment Ptrodect',
                        'data' => $MyZebouneDataPayment,
                        'typAction' => 1,
                    ], 200);
                } else {
                    return response()->json([
                        'message' => 'ErrSuccessFuly Show Data My Zeboune For Payment Ptrodect',
                        'data' => [],
                        'typAction' => 2,
                    ], 200);
                }
                
            } else {
                return response()->json([
                    'message' => 'Error Semthing Not Found',
                    'data' => 3,
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 99,
            ], 200);
        }
    } //== End Sereach Alls PaymentProdects For My Zeboune ==//
    // Start Show Alls Actions From Edart Pay Prodects

    function getAllMyCalyanes() {
        try {
            if(Auth::user()->curret_profile_id) {
                $datar = Auth::user()->ZebouneForUser()->where('TypeAccounte', 'Online')
                ->join('profile_user_bsses', 'zeboune_for_users.usernameBss', 'profile_user_bsses.id')
                ->select('profile_user_bsses.*')
                ->latest()->get();
                if($datar) {
                    return response()->json([
                        'message' => "get all my calyane",
                        'data' => $datar,
                    ], 200);
                } else {
                    return response()->json([
                        'message' => "get all my calyane",
                        'data' => 2,
                    ], 200);
                }
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 99,
            ], 200);
        }
    }

    function getAllMyCalyanesPayments() {
        try {
            $datar = Auth::user()->ZebouneForUser()->where('TypeAccounte', 'Online')
            ->join('payment_method_user_bsses', 'zeboune_for_users.usernameBss', 'payment_method_user_bsses.usernameBss')
            ->select('payment_method_user_bsses.*')
            ->latest()->get();

            if($datar) {
                return response()->json([
                    'message' => "get all my calyane Payments",
                    'data' => $datar,
                ], 200);
            } else {
                return response()->json([
                    'message' => "get all my calyane Payments",
                    'data' => 2,
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 99,
            ], 200);
        }
    }

    function getAllMyCalyanesProdects() {
        try {
            $SheckMyProfIDBssTv = Auth::user()->curret_profile_id;
            if($SheckMyProfIDBssTv) {
                $datar = Auth::user()->ZebouneForUser()->where([
                    'TypeAccounte' => 'Online',
                    'ConfirmedRelactionUserBss' => 1,
                    'ConfirmedRelactionUserZeboune' => 1,
                    'ConfirmedRelation' => 1,
                    ])
                ->join('prodect_users', "zeboune_for_users.usernameBss", 'prodect_users.idBss')
                ->select('prodect_users.*')
                ->latest()->get();

                if($datar) {
                    return response()->json([
                        'message' => "get all my calyane Prodects",
                        'data' => $datar,
                    ], 200);
                } else {
                    return response()->json([
                        'message' => "get all my calyane Prodects",
                        'data' => 2,
                    ], 200);
                }
            } else {
                return response()->json([
                    'message' => "Erro Semthing Error",
                    'data' => 3,
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 99,
            ], 200);
        }
    }

    // Start Alls Action For Edart Order User And Bss
    // Start To Create Noew My Orders For Send Semthing Bss
    function CreateOrderForPaymMentProdects(Request $request) {
        try {
            $user = Auth::user();
            if($user->curret_profile_id) {
                $request->validate([
                    'productID' => 'required|array',
                    'productID.*' => 'integer|exists:prodect_users,id',
                    'quantities' => 'required|array',
                    'quantities.*' => 'integer|min:1',
                    'IDBss' => 'required|integer',
                    'idsergetOrder' => 'nullable|string',
                    'PaymentMethod' => 'integer|exists:payment_method_user_bsses,id',
                    'imagePayment' => 'nullable|image|mimes:jpeg,jpg|max:4000',
                ]);
                $bssiD = strip_tags($request->IDBss); //nameUserBss
                $PaymentMethodSpl = strip_tags($request->PaymentMethod);
                $MyData = $user->ZebouneForUser()->where([
                    'usernameBss' => $bssiD,
                    'TypeAccounte' => 'Online',
                    'user_id' => $user->id,
                ])->select('id', 'HaletDeyn', 'TotelDeyn', 'username', 'numberPhone', 'TypeAccounte', 'TotelBayMent')->first();
                $MtCalyan = ProfileUserBss::where('id', $bssiD)->select('image', 'usernameBss')->first();
                if($MyData) {
                    $PaymentMethod = PaymentMethodUserBss::where('id', $PaymentMethodSpl)
                    ->select('id', 'namepayment', 'TypeNumberPay')->first();
                    if($PaymentMethod->namepayment === "Selefe" && $MyData->HaletDeyn != 1) {
                        return response()->json([
                            'message' => 'Sory This User Has Not Have Relaction To do THIS tYPE',
                            'typAction' => 12,
                            'data' => [],
                        ]);
                    }
                    $TotelProdects = count($request->productID);
                    $Totelqauntites = count($request->quantities);
                    $TotelPrices = 0;
                    $productNamesPay = [];
                    $NamePerodectPay = [];
                    $IDsPerodectPay = [];
                    $TotelPrices = 0;
                    $Totelquat = 0;
                    try {
                        DB::beginTransaction();
                        $totalPrice = 0;
                        $PricePerodectPay = [];
                        $ValQuanteOne = $request->quantities;
                        foreach ($request->productID as $index => $productId) {
                            $product = ProdectUser::find($productId);
                            if (!$product) {
                                return response()->json([
                                    'message' => 'Sorry Error Not Found Semthing Error',
                                    'typAction' => 10,
                                    'data' => [],
                                ], 200);
                                continue;
                            }
                            $quantity = $request->quantities[$index];
                            if ($quantity > $product->totaleinstorage) {
                                $errotm = [
                                    "name" => $product->name,
                                    "quantite" => $product->totaleinstorage,
                                    "quantitetopay" => $quantity,
                                ];
                                return response()->json([
                                    'message' => "Sorry Prodect name $product->name Has ToTale $product->totaleinstorage for pay $quantity",
                                    'typAction' => 7,
                                    'data' => $errotm
                                ], 200);
                                continue;
                            }
                            if (!is_numeric($quantity) || $quantity <= 0) {
                                return response()->json([
                                    'message' => 'Sorry Error Not Found Semthing Error',
                                    'typAction' => 22,
                                    'data' => [],
                                ], 200);
                                continue;
                            }
                            $Totelquat += $request->quantities[$index];
                            $product->decrement('totaleinstorage', $quantity);
                            $TotelPrices += $product->price * $quantity;
                            $PricePerodectPay[] = $product->price;
                            $NamePerodectPay[] = $product->name;
                            $IDsPerodectPay[] = $product->id;
                        }
                        DB::commit();
                        $CurrentPay = CurrentPaymentForUseBss::where('usernameBss', $bssiD)->select('currentCantry')->first();
                        $PaymentUpdate = [
                            'user_id' => $user->id,
                            'allquantitelprodect' => $Totelquat,
                            // 'idusergetorder' => $user->id,
                            'usernameBss' => $bssiD,
                            'namezeboune' => $MyData->username,
                            'typeorderforzeboune' => 0,
                            'imgUserBss' => $MtCalyan->image,
                            'namebss' => $MtCalyan->usernameBss,
                            'currentPay' => $CurrentPay->currentCantry,
                            'numberzeboune' => $MyData->numberPhone,
                            'typeaccountzeboune' => $MyData->TypeAccounte,
                            'idaccountzeboune' => $MyData->id,
                            'totalprodectspay' => $TotelProdects,
                            'totalpriceprodectspay' => $TotelPrices,
                            'nameprodectspay' => json_encode($NamePerodectPay),
                            'idprodectspay' => json_encode($IDsPerodectPay),
                            'priceprodectspay' => json_encode($PricePerodectPay),
                            'quantiteyprodectspay' => json_encode($ValQuanteOne),
                            'typepayment' => 0,
                            'paymentmethod' => $PaymentMethod->namepayment,
                        ];
                        if($PaymentMethod->namepayment === "Selefe" ||
                            $PaymentMethod->namepayment == 'CASH') {
                            if($PaymentMethod->namepayment === "Selefe") {
                                $MyData->increment('TotelDeyn', $TotelPrices);
                            }
                            $PaymentUpdate['numberpaymentmethod'] = '';
                            $PaymentUpdate['typepayment'] = 0;
                        } else {
                            $PaymentUpdate['numberpaymentmethod'] = $PaymentMethod->TypeNumberPay;
                        }

                        if($request->hasFile('imagePayment')) {
                            $image = $request->file('imagePayment');
                            $gen = hexdec(uniqid());
                            $ext = strtolower($image->getClientOriginalExtension());
                            $namePod = $gen . '' . $ext;
                            $location = 'Order_Payment_Prodect_Pay/';
                            $source = $location.$namePod;
                            $name = $gen. '.' .$ext;
                            $source = $location.$name;
                            $image->move($location,$name);
                            $PaymentUpdate['imgconfirmedpay'] = $source;
                        }
                        $ConfirmedUpdatePayment = MyOrdersPayBss::create($PaymentUpdate);
                        if($ConfirmedUpdatePayment) {
                            $MyData->increment('TotelBayMent', 1);
                            return response()->json([
                                'message' => 'SuccessFuly Create Order To Pay Prodect',
                                'typAction' => 1,
                                'data' => [],
                            ], 201);
                        } else {
                            return response()->json([
                                'message' => 'Sorry Error Not Found Semthing Error',
                                'typAction' => 90,
                                'data' => [],
                            ]);
                        }
                    } catch (\Exception $e) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => 'حدث خطأ غير متوقع: ' . $e->getMessage(),
                            'file' => $e->getFile(),
                            'line' => $e->getLine()
                        ], 500);
                    }
                } else {
                    return response()->json([
                        'message' => 'Wawe Error To Create Payment Prodect',
                        'data' => 2,
                    ], 201);
                }
            } else {
                return response()->json([
                    'message' => 'Plz Login For Your Acounet Personel For Create Now Order',
                    'data' => 10,
                ], 201);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 99,
            ], 200);
        }
    } //== End To Create Noew My Orders For Send Semthing Bss ==//

    // Start Show Alls Data Order User For Aney Profile Login Now
    function ShowAllMyOrders() {
        try {
            $MyProfileNow= Auth::user();
            $SheckMyProfIDBssTv = $MyProfileNow->current_my_travel;
            $SheckMyProfIDBss = $MyProfileNow->curret_profile_id_Bss;
            $ProfileLoginNow = $SheckMyProfIDBss ? $SheckMyProfIDBss : $SheckMyProfIDBssTv;
            $profilBssNow = $MyProfileNow->ProfileUserBss()->where('id', $SheckMyProfIDBss)->select('id')->first();
            $MyProfileTrav = $MyProfileNow->EdaretMewevin()->where([
                'confirmUser' => 1,
                'confirmBss' => 1,
                'typerelation' => 1,
                'idbss' => $SheckMyProfIDBssTv,
            ])->select('id')->latest()->first();
            if($MyProfileNow->curret_profile_id) {
                $datar = $MyProfileNow->MyOrdersPayBss()->select('id', 'TypeOrder', 'currentPay','totalprodectspay', 'totalpriceprodectspay', 'typepayment', 'paymentmethod', 'namebss', 'numberpaymentmethod')->latest()->paginate(10);
                if($datar) {
                    return response()->json([
                        'message' => "get all my calyane Prodects User",
                        'data' => $datar,
                    ], 200);
                } else {
                    return response()->json([
                        'message' => "get all my calyane Prodects User",
                        'data' => 2,
                    ], 200);
                }
            } else if($profilBssNow || $MyProfileTrav) {
                $datar = MyOrdersPayBss::where('usernameBss', $ProfileLoginNow)->select('id', 'TypeOrder', 'currentPay','totalprodectspay', 'totalpriceprodectspay', 'typepayment', 'paymentmethod', 'namezeboune', 'numberzeboune', 'numberpaymentmethod')->latest()->paginate(10);
                if($datar) {
                    return response()->json([
                        'message' => "get all my calyane Prodects User Bss",
                        'data' => $datar,
                    ], 200);
                } else {
                    return response()->json([
                        'message' => "get all my calyane Prodects User Bss",
                        'data' => 2,
                    ], 200);
                }
            } else {
                return response()->json([
                    'message' => "get all my calyane Prodects User Bss",
                    'data' => 27,
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 99,
            ], 200);
        }
    } //== End Show Alls Data Order User For Aney Profile Login Now ==//

    // Start User Sereach Alls Data Order Contect For Semthing Bss For Id Bss
    function SearchAllMyOrderForThisBss($BssId) {
        try {
            if(Auth::user()->curret_profile_id) {
                $SmplIdBss =  strip_tags($BssId);
                $AllMyOrderForBss = Auth::user()->MyOrdersPayBss()->where(
                    'usernameBss', $SmplIdBss)->select('id', 'TypeOrder', 'currentPay','totalprodectspay', 'totalpriceprodectspay', 'typepayment', 'paymentmethod', 'namebss', 'numberpaymentmethod')->latest()->paginate(10);
                if($AllMyOrderForBss) {
                    return response()->json([
                        'message' => 'ErrSuccessFuly Show Data My Zeboune For Payment Ptrodect',
                        'data' => $AllMyOrderForBss,
                    ], 200);
                } else {
                    return response()->json([
                        'message' => 'Error Dont Get Value This Data',
                        'data' => 4,
                    ], 200);
                }
                
            } else {
                return response()->json([
                    'message' => 'Error Semthing Not Found',
                    'data' => 3,
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 99,
            ], 200);
        }
    } //== End User Sereach Alls Data Order Contect For Semthing Bss For Id Bss ==//

    // Start Show My Data Order For Id
    function ShowMyOrdersForId($OrderID) {
        try {
            $user = Auth::user();
            $SheckMyProfIDBss = $user->curret_profile_id_Bss;
            $ProfileLoginUser = $user->curret_profile_id;
            $SheckMyProfIDBssTv = $user->current_my_travel;
            $SpmlIdOrderd = strip_tags($OrderID);
            $DataProfileLoginNow = $SheckMyProfIDBssTv || $SheckMyProfIDBss;
            $MyProfileBss = $user->ProfileUserBss()->where('id', $SheckMyProfIDBss)->first();
            $MyProfileTrav = $user->EdaretMewevin()->where([
                'confirmUser' => 1,
                'confirmBss' => 1,
                'typerelation' => 1,
                'idbss' => $SheckMyProfIDBssTv,
            ])->select('id')->first();
            $dataForEftProd = [];
            if($ProfileLoginUser != null) {
                $MyPaymentMethod = $user->MyOrdersPayBss()->where([
                    'id' => $SpmlIdOrderd,
                ])->select('imgconfirmedpay', 
                'currentPay', 'typMeshole', 'idMeshole',
                'totalprodectspay', 'totalpriceprodectspay', 'allquantitelprodect', 'typepayment',
                'paymentmethod', 'TypeOrder', 'numberpaymentmethod', 'created_at', 'nameprodectspay',
                'quantiteyprodectspay', 'usernameBss', 'priceprodectspay', 'user_id'
                )->first();
                if($MyPaymentMethod) {
                    $ProfileBssNow = ProfileUserBss::where('id', $MyPaymentMethod->usernameBss)->select('usernameBss')->first();
                    $ProfileZeboune = ProfileUser::where('user_id', $MyPaymentMethod->user_id)->select('name', 'NumberPhone')->first();
                    $datShowNowMore = [
                        'namebss' => $ProfileBssNow->usernameBss,
                        'numberzeboune' => $ProfileZeboune->NumberPhone,
                        'imgconfirmedpay' => $MyPaymentMethod->imgconfirmedpay,
                        'currentPay' => $MyPaymentMethod->currentPay,
                        'typMeshole' => $MyPaymentMethod->typMeshole,
                        'totalprodectspay' => $MyPaymentMethod->totalprodectspay,
                        'totalpriceprodectspay' => $MyPaymentMethod->totalpriceprodectspay,
                        'allquantitelprodect' => $MyPaymentMethod->allquantitelprodect,
                        'typepayment' => $MyPaymentMethod->typepayment,
                        'paymentmethod' => $MyPaymentMethod->paymentmethod,
                        'TypeOrder' => $MyPaymentMethod->TypeOrder,
                        'numberpaymentmethod' => $MyPaymentMethod->numberpaymentmethod,
                        'quantiteyprodectspay' => $MyPaymentMethod->quantiteyprodectspay,
                        'created_at' => $MyPaymentMethod->created_at,
                        'created_at' => $MyPaymentMethod->created_at,
                    ];
                    if($MyPaymentMethod->typMeshole == 2) {
                        $MyProfileNowZ = EdaretMewevin::where([
                            'idbss' => $MyPaymentMethod->usernameBss,
                            'id' => $MyPaymentMethod->idMeshole,
                        ])->select('user_id')->first();
                        $ProfilMeshole = ProfileUser::where('user_id', $MyProfileNowZ->user_id)->select('name')->first();
                        $datShowNowMore['nameMeshol'] = $ProfilMeshole->name;
                    }
                    foreach (json_decode($MyPaymentMethod->nameprodectspay) as $index => $nameProd) {
                        $price = json_decode($MyPaymentMethod->priceprodectspay)[$index];
                        $quantite = json_decode($MyPaymentMethod->quantiteyprodectspay)[$index];
                        $dataForEftProd[] = [
                            'id' => $index+1,
                            'name' => $nameProd,
                            'price' => $price . "$MyPaymentMethod->currentPay",
                            'quantite' => $quantite
                        ];
                        
                    }
                    return response()->json([
                        'message' => 'Show My Payment Pethod Data',
                        'typAction' => 1,
                        'data' => $datShowNowMore,
                        'dataForEftProd' => $dataForEftProd,
                    ], 200);
                } else {
                    $dataUpdat = $user->MyOrdersPayBss()->select('id', 'TypeOrder', 'TypeOrder', 'currentPay', 'currentPay','totalprodectspay', 'totalpriceprodectspay', 'typepayment', 'paymentmethod', 'namebss', 'numberpaymentmethod')->latest()->paginate(10);
                    return response()->json([
                        'message' => 'Show My Payment Pethod Data',
                        'typAction' => 2,
                        'data' => $dataUpdat,
                    ], 200);
                }

            } else if($MyProfileBss || $MyProfileTrav) {
                $MyPaymentMethod = MyOrdersPayBss::where([
                    'usernameBss' => $DataProfileLoginNow,
                    'id' => $SpmlIdOrderd,
                ])->select('numberzeboune', 'imgconfirmedpay', 
                'imgprofilezeboune', 'currentPay', 'typMeshole', 'idMeshole',
                'totalprodectspay', 'totalpriceprodectspay', 'allquantitelprodect', 'typepayment',
                'paymentmethod', 'TypeOrder', 'numberpaymentmethod', 'created_at', 'nameprodectspay',
                'quantiteyprodectspay', 'usernameBss', 'priceprodectspay', 'user_id'
                )->first();
                if($MyPaymentMethod) {
                    $ProfileBssNow = ProfileUserBss::where('id', $MyPaymentMethod->usernameBss)->select('usernameBss')->first();
                    $ProfileZeboune = ProfileUser::where('user_id', $MyPaymentMethod->user_id)->select('name', 'NumberPhone')->first();
                    $datShowNowMore = [
                        'namezeboune' => $ProfileZeboune->name,
                        'numberzeboune' => $ProfileZeboune->NumberPhone,
                        'imgconfirmedpay' => $MyPaymentMethod->imgconfirmedpay,
                        'currentPay' => $MyPaymentMethod->currentPay,
                        'typMeshole' => $MyPaymentMethod->typMeshole,
                        'totalprodectspay' => $MyPaymentMethod->totalprodectspay,
                        'totalpriceprodectspay' => $MyPaymentMethod->totalpriceprodectspay,
                        'allquantitelprodect' => $MyPaymentMethod->allquantitelprodect,
                        'typepayment' => $MyPaymentMethod->typepayment,
                        'paymentmethod' => $MyPaymentMethod->paymentmethod,
                        'TypeOrder' => $MyPaymentMethod->TypeOrder,
                        'numberpaymentmethod' => $MyPaymentMethod->numberpaymentmethod,
                        'quantiteyprodectspay' => $MyPaymentMethod->quantiteyprodectspay,
                        'created_at' => $MyPaymentMethod->created_at,
                    ];
                    if($MyPaymentMethod->typMeshole === 2) {
                        $MyProfileNow = EdaretMewevin::where([
                            'idbss' => $DataProfileLoginNow,
                            'id' => $MyPaymentMethod->idMeshole,
                        ])->select('user_id')->first();
                        $ProfilMeshole = ProfileUser::where('user_id', $MyProfileNow->user_id)->select('NumberPhone')->first();
                        $datShowNowMore['nameMeshol'] = $ProfilMeshole->NumberPhone;
                    }
                    foreach (json_decode($MyPaymentMethod->nameprodectspay) as $index => $nameProd) {
                        $price = json_decode($MyPaymentMethod->priceprodectspay)[$index];
                        $quantite = json_decode($MyPaymentMethod->quantiteyprodectspay)[$index];
                        $dataForEftProd[] = [
                            'id' => $index+1,
                            'name' => $nameProd,
                            'price' => $price . "$MyPaymentMethod->currentPay",
                            'quantite' => $quantite
                        ];
                        
                    }
                    return response()->json([
                        'message' => 'Show My Payment Pethod Data',
                        'typAction' => 1,
                        'data' => $datShowNowMore,
                        'dataForEftProd' => $dataForEftProd,
                    ], 200);
                } else {
                    $dataUpda = MyOrdersPayBss::where('usernameBss', $DataProfileLoginNow)->select('id', 'TypeOrder', 'TypeOrder', 'currentPay', 'currentPay','totalprodectspay', 'totalpriceprodectspay', 'typepayment', 'paymentmethod', 'namezeboune', 'numberzeboune', 'numberpaymentmethod',)->latest()->paginate(10);
                    return response()->json([
                        'message' => 'Show My Payment Pethod Data',
                        'typAction' => 2,
                        'data' => $dataUpda,
                    ], 200);
                }

            } else {
                return response()->json([
                    'message' => 'Error Semthing Not Found',
                    'data' => 3,
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 99,
            ], 200);
        }
    } // End Show My Data Order For Id

    // Start Sereach Alls Data My Zeboune From Orders Bss
    function SearchOllOrderForZeboune($IdZeboun) {
        try {
            $SpmlIdSpone = strip_tags($IdZeboun);
            $user = Auth::user();
            $SheckMyProfIDBss = $user->curret_profile_id_Bss;
            $MyProfileBss = Auth::user()->ProfileUserBss()->where('id', $SheckMyProfIDBss)->select('id')->first();
            $SheckMyProfIDBssTv = $user->current_my_travel;
            $datProfileLoginNow = $SheckMyProfIDBss || $SheckMyProfIDBssTv;
            $MyProfileTrav = Auth::user()->EdaretMewevin()->where([
                'confirmUser' => 1,
                'confirmBss' => 1,
                'typerelation' => 1,
                'idbss' => $SheckMyProfIDBssTv,
            ])
            ->select('id')
            ->latest()->first();

            if($MyProfileBss || $MyProfileTrav) {
                $MyZebouneDataPayment = MyOrdersPayBss::where([
                    'usernameBss' => $datProfileLoginNow, 
                    'idaccountzeboune' => $SpmlIdSpone,
                ])->select('id', 'TypeOrder', 'TypeOrder', 'currentPay', 'currentPay','totalprodectspay', 'totalpriceprodectspay', 'typepayment', 'paymentmethod', 'namezeboune', 'numberzeboune', 'numberpaymentmethod')->latest()->paginate(10);
                if($MyZebouneDataPayment) {
                    return response()->json([
                        'message' => 'ErrSuccessFuly Show Data My Zeboune For Payment Ptrodect',
                        'data' => $MyZebouneDataPayment,
                    ], 200);
                }
            } else {
                return response()->json([
                    'message' => 'Error Semthing Not Found',
                    'data' => 3,
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 99,
            ], 200);
        }
    } //== End Sereach Alls Data My Zeboune From Orders Bss ==//

    // Start To Stop Send My Order From User
    function StopSendConfirmedMyOder($OrderID) {
        try {
            if(Auth::check()){
                $MyProfileNow = Auth::user();
                $ProfilNow = $MyProfileNow->curret_profile_id;
                $SmplIdOrder = strip_tags($OrderID);
                if($ProfilNow || $ProfilNow !== null) {
                    $datar = $MyProfileNow->MyOrdersPayBss()->where('id', $SmplIdOrder)->select('id', 'TypeOrder', 'idaccountzeboune', 'typeorderforzeboune', 'idprodectspay', 'quantiteyprodectspay', 'usernameBss', 'totalpriceprodectspay', 'paymentmethod')->first();
                    if($datar) {
                        $MyZebounProfBss = $MyProfileNow->ZebouneForUser()->where([
                            'usernameBss' => $datar->usernameBss,
                            'id' => $datar->idaccountzeboune,
                        ])->select('id', 'HaletDeyn', 'TotelBayMent', 'TotelDeyn')->first();
                        if($datar->TypeOrder == 4 || $datar->typeorderforzeboune == 2 ) {
                            $datarUpd = $MyProfileNow->MyOrdersPayBss()->select('id', 'TypeOrder', 'currentPay','totalprodectspay', 'totalpriceprodectspay', 'typepayment', 'paymentmethod', 'namebss', 'numberpaymentmethod')->latest()->paginate(10);
                            return response()->json([
                                'message' => "get all my calyane Prodects User",
                                'data' => $datarUpd,
                                'typAction' => 6,
                            ], 200);
                        } else if($datar->TypeOrder == 0) {
                            foreach (json_decode($datar->idprodectspay) as $index => $productId) {
                                $product = ProdectUser::find($productId);
                                if (!$product) {
                                    return response()->json([
                                        'message' => 'Sorry Error Not Found Semthing Error',
                                        'typAction' => 22,
                                        'data' => [],
                                    ], 200);
                                    continue;
                                }
                                $quantity = json_decode($datar->quantiteyprodectspay)[$index];
                                $product->increment('totaleinstorage', $quantity);
                            }
                            $uPDtAT = $datar->update([
                                'typeorderforzeboune' => 2,
                                'TypeOrder' => 4
                            ]);
                            if($uPDtAT) {
                                if($datar->paymentmethod === 'Selefe') {
                                    $MyZebounProfBss->decrement('TotelDeyn', $datar->totalpriceprodectspay);
                                }
                                $MyZebounProfBss->decrement('TotelBayMent', 1);
                                $datarUpd = $MyProfileNow->MyOrdersPayBss()->select('id', 'TypeOrder', 'TypeOrder', 'currentPay', 'currentPay','totalprodectspay', 'totalpriceprodectspay', 'typepayment', 'paymentmethod', 'namebss', 'numberpaymentmethod')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "get all my calyane Prodects User",
                                    'typAction' => 1,
                                    'data' => $datarUpd,
                                ], 200);
                            }
                        } else {
                            $datarUpd = $MyProfileNow->MyOrdersPayBss()->select('id', 'TypeOrder', 'TypeOrder', 'currentPay', 'currentPay','totalprodectspay', 'totalpriceprodectspay', 'typepayment', 'paymentmethod', 'namebss', 'numberpaymentmethod')->latest()->paginate(10);
                            return response()->json([
                                'message' => "get all my calyane Prodects User",
                                'typAction' => 4,
                                'data' => $datarUpd,
                            ], 200);
                        }
                    } else {
                        $datarUpd = $MyProfileNow->MyOrdersPayBss()->select('id', 'TypeOrder', 'TypeOrder', 'currentPay', 'currentPay','totalprodectspay', 'totalpriceprodectspay', 'typepayment', 'paymentmethod', 'namebss', 'numberpaymentmethod')->latest()->paginate(10);
                        return response()->json([
                            'message' => "get all my calyane Prodects User Bss",
                            'typAction' => 3,
                            'data' => $datarUpd,
                        ], 200);
                    }
                } else {
                    return response()->json([
                        'message' => "E",
                        'typAction' => 2,
                        'data' => [],
                    ], 200);
                }
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 99,
            ], 200);
        }
    } //== End To Stop Send My Order From User ==//

    // Start To Delete My Order From User
    function DeleteSendConfirmedMyOder($OrderID) {
        try {
            if(Auth::check()){
                $MyProfileNow= Auth::user();
                $SmplIdOrder = strip_tags($OrderID);
                if($MyProfileNow->curret_profile_id || $MyProfileNow->curret_profile_id !== null) {
                    $datar = $MyProfileNow->MyOrdersPayBss()->where('id', $SmplIdOrder)->select('id', 'TypeOrder', 'idaccountzeboune', 'typeorderforzeboune', 'idprodectspay', 'quantiteyprodectspay', 'usernameBss', 'totalpriceprodectspay', 'paymentmethod')->first();
                    if($datar) {
                        if($datar->TypeOrder == 1 || $datar->TypeOrder == 3) {
                            $datarUpd = $MyProfileNow->MyOrdersPayBss()->select('id', 'TypeOrder', 'currentPay','totalprodectspay', 'totalpriceprodectspay', 'typepayment', 'paymentmethod', 'namebss', 'numberpaymentmethod')->latest()->paginate(10);
                            return response()->json([
                                'message' => "Sorry Bss Has One Relay Do Action For Your Order",
                                'typAction' => 4,
                                'data' => $datarUpd,
                            ], 200);
                        } else if($datar->TypeOrder == 0) {
                            $MyZebounProfBss = $MyProfileNow->ZebouneForUser()->where([
                                'usernameBss' => $datar->usernameBss,
                                'id' => $datar->idaccountzeboune,
                            ])->select('id', 'TotelBayMent', 'TotelDeyn')->first();
                            foreach (json_decode($datar->idprodectspay) as $index => $productId) {
                                $product = ProdectUser::find($productId);
                                if (!$product) {
                                    return response()->json([
                                        'message' => 'Sorry Error Not Found Semthing Error',
                                        'typAction' => 22,
                                        'data' => [],
                                    ], 200);
                                    continue;
                                }
                                $quantity = json_decode($datar->quantiteyprodectspay)[$index];
                                $product->increment('totaleinstorage', $quantity);
                            }
                            $MyZebounProfBss->decrement('TotelBayMent', 1);
                            if($datar->paymentmethod === 'Selefe') {
                                $MyZebounProfBss->decrement('TotelDeyn', $datar->totalpriceprodectspay);
                            }
                        }
                        $Deletdat = $datar->delete();
                            if($Deletdat) {
                                $datarUpd = $MyProfileNow->MyOrdersPayBss()->select('id', 'TypeOrder', 'currentPay','totalprodectspay', 'totalpriceprodectspay', 'typepayment', 'paymentmethod', 'namebss', 'numberpaymentmethod')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "get all my calyane Prodects User",
                                    'typAction' => 1,
                                    'data' => $datarUpd,
                                ], 200);
                            } else {
                                $datarUpd = $MyProfileNow->MyOrdersPayBss()->select('id', 'TypeOrder', 'currentPay','totalprodectspay', 'totalpriceprodectspay', 'typepayment', 'paymentmethod', 'namebss', 'numberpaymentmethod')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "get all my calyane Prodects User",
                                    'typAction' => 6,
                                    'data' => $datarUpd,
                                ], 200);
                            }
                    } else {
                        $datarUpd = $MyProfileNow->MyOrdersPayBss()->select('id', 'TypeOrder', 'currentPay','totalprodectspay', 'totalpriceprodectspay', 'typepayment', 'paymentmethod', 'namebss', 'numberpaymentmethod')->latest()->paginate(10);
                        return response()->json([
                            'message' => "get all my calyane Prodects User Bss",
                            'typAction' => 3,
                            'data' => $datarUpd,
                        ], 200);
                    }
                } else {
                    return response()->json([
                        'message' => "get all my calyane Prodects User Bss",
                        'data' => 2,
                    ], 200);
                }
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 99,
            ], 200);
        }
    } //== End To Delete My Order From User ==//

    // Start To Confirmed Payment Order My Zeboune
    function HandleConfirmedPaymentOrderZ(Request $request, $OrderID) {
        try {
            if(Auth::check()){
                $MyProfileNow = Auth::user();
                $idProfileBssNow = $MyProfileNow->curret_profile_id_Bss;
                $SheckMyProfIDBssTv = $MyProfileNow->current_my_travel;
                $OrderIsdSpmpl = strip_tags($OrderID);
                $ProfileBssLoginNow = $MyProfileNow->ProfileUserBss()->where('id', $idProfileBssNow)->select('id')->first();
                $SheckSlahya = $MyProfileNow->EdaretMewevin()->where([
                    'confirmUser' => 1,
                    'confirmBss' => 1,
                    'typerelation' => 1,
                    'idbss' => $SheckMyProfIDBssTv,
                ])->select('id', 'edartOreders', 'PaymentEcteronect', 'totaledartPayEct', 'totalorders')->first();
                if($ProfileBssLoginNow) {
                    $request->validate([
                        'passwordSetting' => 'required|string',
                    ]);
                    $shekpas = $MyProfileNow->UserPasswordStting()->where('idbss', $idProfileBssNow)->select('id', 'password')->first();
                    $passwordStingHa = strip_tags($request->passwordSetting);
                    $passwordStingHaahing = Hash::make($passwordStingHa);
                    $passwordHash = $shekpas->password;
                    $redPassword = request('passwordSetting');
                    if($passwordHash) {
                        if(Hash::check($redPassword, $passwordHash)) {
                            $OrderToPayProd = MyOrdersPayBss::where([
                                'usernameBss' => $MyProfileNow->curret_profile_id_Bss,
                                'id' => $OrderIsdSpmpl,
                            ])->select('id', 'typeorderforzeboune', 'typepayment', 'paymentmethod', 'idprodectspay', 'quantiteyprodectspay', 
                            'totalpriceprodectspay', 'idaccountzeboune')->first();
                            if($OrderToPayProd) {
                                if($OrderToPayProd->typeorderforzeboune == 2) {
                                    $OrderToPayProdUpd = MyOrdersPayBss::where('usernameBss', $idProfileBssNow)->select('id', 'TypeOrder', 'currentPay','totalprodectspay', 'totalpriceprodectspay', 'typepayment', 'paymentmethod', 'namezeboune', 'numberzeboune', 'numberpaymentmethod')->latest()->paginate(10);
                                    return response()->json([
                                        'message' => "Sorry Zeboune On Stop This Order",
                                        'typAction' => 3,
                                        'data' => $OrderToPayProdUpd,
                                    ], 200);
                                } else if($OrderToPayProd->typepayment == 2) {
                                    $OrderToPayProdUpd = MyOrdersPayBss::where('usernameBss', $idProfileBssNow)->select('id', 'TypeOrder', 'currentPay','totalprodectspay', 'totalpriceprodectspay', 'typepayment', 'paymentmethod', 'namezeboune', 'numberzeboune', 'numberpaymentmethod')->latest()->paginate(10);
                                    return response()->json([
                                        'message' => "Sorry Your Are On Realy Dsc Confirmed Payment Order",
                                        'typAction' => 4,
                                        'data' => $OrderToPayProdUpd,
                                    ], 200);
                                } else if($OrderToPayProd->typepayment == 1) {
                                    $OrderToPayProdUpd = MyOrdersPayBss::where('usernameBss', $idProfileBssNow)->select('id', 'TypeOrder', 'currentPay','totalprodectspay', 'totalpriceprodectspay', 'typepayment', 'paymentmethod', 'namezeboune', 'numberzeboune', 'numberpaymentmethod')->latest()->paginate(10);
                                    return response()->json([
                                        'message' => "Sorry Your Are On Confirmed Payment Order",
                                        'typAction' => 5,
                                        'data' => $OrderToPayProdUpd,
                                    ], 200);
                                }
                                $MyZeboune = ZebouneForUser::where([
                                    'usernameBss' => $idProfileBssNow,
                                    'id' => $OrderToPayProd->idaccountzeboune,
                                ])->select('id', 'HaletDeyn', 'TotelBayMent', 'TotelDeyn')->first();
                                
                                if($OrderToPayProd->paymentmethod === 'CASH' ||
                                    $OrderToPayProd->paymentmethod === 'Selefe' 
                                    ) {
                                        $UpdateDat = [
                                            'typepayment' => 1,
                                            'typMeshole' => 1,
                                            'idMeshole' => $idProfileBssNow,
                                        ];
                                    if($OrderToPayProd->paymentmethod === 'Selefe') {
                                        if($MyZeboune->HaletDeyn != 1) {
                                                foreach (json_decode($OrderToPayProd->idprodectspay) as $index => $productId) {
                                                    $product = ProdectUser::find($productId);
                                                    if (!$product) {
                                                        return response()->json([
                                                            'message' => 'Sorry Error Not Found Semthing Error',
                                                            'typAction' => 22,
                                                            'data' => [],
                                                        ], 200);
                                                        continue;
                                                    }
                                                    $quantity = json_decode($OrderToPayProd->quantiteyprodectspay)[$index];
                                                    $product->increment('totaleinstorage', $quantity);
                                                }
                                                $UpdateDat = [
                                                    'typepayment' => 2,
                                                    'TypeOrder' => 4,
                                                    'typMeshole' => 1,
                                                    'idMeshole' => $idProfileBssNow,
                                                ];
                                                $UpdaOrderConf = $OrderToPayProd->update($UpdateDat);
                                                $OrderToPayProd->update($UpdateDat);
                                                $MyZeboune->decrement('TotelDeyn', $OrderToPayProd->totalpriceprodectspay);
                                                $OrderToPayProdUpd = MyOrdersPayBss::where('usernameBss', $idProfileBssNow)->select('id', 'TypeOrder', 'currentPay','totalprodectspay', 'totalpriceprodectspay', 'typepayment', 'paymentmethod', 'namezeboune', 'numberzeboune', 'numberpaymentmethod')->latest()->paginate(10);
                                                $UpdateDat = [
                                                    'typepayment' => 2,
                                                    'TypeOrder' => 4,
                                                    'typMeshole' => 2,
                                                    'idMeshole' => $idProfileBssNow,
                                                ];
                                                return response()->json([
                                                    'message' => 'Sorry Your Not Have This Slahie Payment Elect',
                                                    'typAction' => 12,
                                                    'data' => $OrderToPayProdUpd,
                                                ]);
                                            } else if($MyZeboune->HaletDeyn == 1) {
                                                $MyZeboune->increment('TotelDeyn', $MyProfileNow->totalpriceprodectspay);
                                            }
                                    }
                                } else {
                                    $UpdateDat = [
                                        'typepayment' => 1,
                                        'typMeshole' => 1,
                                        'TypeOrder' => 3,
                                        'idMeshole' => $idProfileBssNow,
                                    ];
                                }
                                
                                $UpdaOrderConf = $OrderToPayProd->update($UpdateDat);
                                if($UpdaOrderConf) {
                                    $OrderToPayProdUpd = MyOrdersPayBss::where('usernameBss', $idProfileBssNow)->select('id', 'TypeOrder', 'currentPay','totalprodectspay', 'totalpriceprodectspay', 'typepayment', 'paymentmethod', 'namezeboune', 'numberzeboune', 'numberpaymentmethod')->latest()->paginate(10);
                                    return response()->json([
                                        'message' => "SuccesFuly Confirmed Payment Order Zeboune",
                                        'typAction' => 1,
                                        'data' => $OrderToPayProdUpd,
                                    ], 200);
                                } else {
                                    $OrderToPayProdUpd = MyOrdersPayBss::where('usernameBss', $idProfileBssNow)->select('id', 'TypeOrder', 'currentPay','totalprodectspay', 'totalpriceprodectspay', 'typepayment', 'paymentmethod', 'namezeboune', 'numberzeboune', 'numberpaymentmethod')->latest()->paginate(10);
                                    return response()->json([
                                        'message' => "Error To Confirmed Payment Order Zeboune",
                                        'typAction' => 2,
                                        'data' => $OrderToPayProdUpd,
                                    ], 200);
                                }

                            } else {
                                $OrderToPayProdUpd = MyOrdersPayBss::where('usernameBss', $idProfileBssNow)->select('id', 'TypeOrder', 'TypeOrder', 'currentPay', 'currentPay','totalprodectspay', 'totalpriceprodectspay', 'typepayment', 'paymentmethod', 'namezeboune', 'numberzeboune', 'numberpaymentmethod')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "Sorry Zeboune Has On Delete This Order Or This Order Not Found",
                                    'typAction' => 9,
                                    'data' => $OrderToPayProdUpd,
                                ], 200);
                            }
                        } else {
                            return response()->json([
                                'message' => "Sorry Your Password Setting Is Not Correct",
                                'typAction' => 7,
                                'data' => [],
                            ], 200);
                        }
                    } else {
                        return response()->json([
                            'message' => "Sorry Your Are Not Have Password Setting Plz Created",
                            'typAction' => [],
                            'data' => 8,
                        ], 200);
                    }
                } else if($SheckSlahya) {
                    if($SheckSlahya->edartOreders === 1) {
                        $OrderToPayProd = MyOrdersPayBss::where([
                            'usernameBss' => $SheckMyProfIDBssTv,
                            'id' => $OrderIsdSpmpl,
                        ])->select('id', 'typeorderforzeboune', 'typepayment', 'paymentmethod', 'idprodectspay', 'quantiteyprodectspay', 
                        'totalpriceprodectspay', 'idaccountzeboune')->first();

                        if($OrderToPayProd) {
                            if($OrderToPayProd->typeorderforzeboune == 2) {
                                $OrderToPayProdUpd = MyOrdersPayBss::where('usernameBss', $SheckMyProfIDBssTv)->select('id', 'TypeOrder', 'currentPay','totalprodectspay', 'totalpriceprodectspay', 'typepayment', 'paymentmethod', 'namezeboune', 'numberzeboune', 'numberpaymentmethod')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "Sorry Zeboune On Stop This Order",
                                    'typAction' => 3,
                                    'data' => $OrderToPayProdUpd
                                ], 200);
                            } else if($OrderToPayProd->typepayment == 2) {
                                $OrderToPayProdUpd = MyOrdersPayBss::where('usernameBss', $SheckMyProfIDBssTv)->select('id', 'TypeOrder', 'currentPay','totalprodectspay', 'totalpriceprodectspay', 'typepayment', 'paymentmethod', 'namezeboune', 'numberzeboune', 'numberpaymentmethod')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "Sorry Your Are On Realy Dsc Confirmed Payment Order",
                                    'data' => $OrderToPayProdUpd,
                                    'typAction' => 4,
                                ], 200);
                            } else if($OrderToPayProd->typepayment == 1) {
                                $OrderToPayProdUpd = MyOrdersPayBss::where('usernameBss', $SheckMyProfIDBssTv)->select('id', 'TypeOrder', 'currentPay','totalprodectspay', 'totalpriceprodectspay', 'typepayment', 'paymentmethod', 'namezeboune', 'numberzeboune', 'numberpaymentmethod')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "Sorry Your Are On Confirmed Payment Order",
                                    'typAction' => 5,
                                    'data' => $OrderToPayProdUpd,
                                ], 200);
                            }
                            if($OrderToPayProd->paymentmethod === 'CASH' ||
                                $OrderToPayProd->paymentmethod === 'Selefe' 
                                ) {
                                    $MyZeboune = ZebouneForUser::where([
                                        'usernameBss' => $SheckMyProfIDBssTv,
                                        'id' => $OrderToPayProd->idaccountzeboune,
                                    ])->select('id', 'HaletDeyn', 'TotelBayMent', 'TotelDeyn')->first();
                                    if($OrderToPayProd->paymentmethod === 'Selefe') {
                                        if($MyZeboune->HaletDeyn != 1) {
                                            foreach (json_decode($OrderToPayProd->idprodectspay) as $index => $productId) {
                                                $product = ProdectUser::find($productId);
                                                if (!$product) {
                                                    return response()->json([
                                                        'message' => 'Sorry Error Not Found Semthing Error',
                                                        'typAction' => 22,
                                                        'data' => [],
                                                    ], 200);
                                                    continue;
                                                }
                                                $quantity = json_decode($OrderToPayProd->quantiteyprodectspay)[$index];
                                                $product->increment('totaleinstorage', $quantity);
                                            }
                                            $UpdateDat = [
                                                'typepayment' => 2,
                                                'TypeOrder' => 4,
                                                'typMeshole' => 2,
                                                'idMeshole' => $SheckSlahya->id,
                                            ];
                                            $OrderToPayProd->update($UpdateDat);
                                            $MyZeboune->decrement('TotelDeyn', $OrderToPayProd->totalpriceprodectspay);
                                            $OrderToPayProdUpd = MyOrdersPayBss::where('usernameBss', $SheckMyProfIDBssTv)->select('id', 'TypeOrder', 'currentPay','totalprodectspay', 'totalpriceprodectspay', 'typepayment', 'paymentmethod', 'namezeboune', 'numberzeboune', 'numberpaymentmethod')->latest()->paginate(10);
                                            return response()->json([
                                                'message' => 'Sorry Your Not Have This Slahie Payment Elect',
                                                'typAction' => 12,
                                                'data' => $OrderToPayProdUpd,
                                            ]);
                                        }
                                    }
                                    $UpdateDat = [
                                        'typepayment' => 1,
                                        'typMeshole' => 2,
                                        'TypeOrder' => 3,
                                        'idMeshole' => $SheckSlahya->id,
                                    ];
                                    $MyZeboune->increment('TotelDeyn', $OrderToPayProd->totalpriceprodectspay);
                            } else {
                                if ($SheckSlahya->PaymentEcteronect != 1) {
                                    return response()->json([
                                        'message' => 'Sorry YOU dont Have Relation slahiyate Payment Elect',
                                        'typAction' => 17,
                                        'data' => [],
                                    ], 200);
                                }
                                $SheckSlahya->increment('totaledartPayEct', 1);
                                $UpdateDat = [
                                    'typepayment' => 1,
                                    'typMeshole' => 2,
                                    'TypeOrder' => 3,
                                    'idMeshole' => $SheckSlahya->id,
                                ];
                            }
                            $UpdaOrderConf = $OrderToPayProd->update($UpdateDat);
                            if($UpdaOrderConf) {
                                $OrderToPayProdUpd = MyOrdersPayBss::where('usernameBss', $SheckMyProfIDBssTv)->select('id', 'TypeOrder', 'currentPay','totalprodectspay', 'totalpriceprodectspay', 'typepayment', 'paymentmethod', 'namezeboune', 'numberzeboune', 'numberpaymentmethod')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "SuccesFuly Confirmed Payment Order Zeboune",
                                    'typAction' => 1,
                                    'data' => $OrderToPayProdUpd,
                                ], 200);
                            } else {
                                $OrderToPayProdUpd = MyOrdersPayBss::where('usernameBss', $SheckMyProfIDBssTv)->select('id', 'TypeOrder', 'currentPay','totalprodectspay', 'totalpriceprodectspay', 'typepayment', 'paymentmethod', 'namezeboune', 'numberzeboune', 'numberpaymentmethod')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "Error To Confirmed Payment Order Zeboune",
                                    'typAction' => 2,
                                    'data' => $OrderToPayProdUpd,
                                ], 200);
                            }
                        } else {
                            $OrderToPayProdUpd = MyOrdersPayBss::where('usernameBss', $idProfileBssNow)->select('id', 'TypeOrder', 'currentPay','totalprodectspay', 'totalpriceprodectspay', 'typepayment', 'paymentmethod', 'namezeboune', 'numberzeboune', 'numberpaymentmethod')->latest()->paginate(10);
                            return response()->json([
                                'message' => "Sorry Zeboune Has On Delete This Order Or This Order Not Found",
                                'typAction' => 9,
                                'data' => $OrderToPayProdUpd,
                            ], 200);
                        }
                    } else {
                        return response()->json([
                            'message' => "Sorry Error  In Server Or Semthing Error Not Found",
                            'data' => [],
                            'typAction' => 11
                        ], 200);
                    }
                } else {
                    return response()->json([
                        'message' => "Sorry Error  In Server Or Semthing Error Not Found",
                        'typAction' => 19,
                        'data' => [],
                    ], 200);
                }
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 99,
            ], 200);
        }
    } //== End To Confirmed Payment Order My Zeboune ==//

    // Start To Dsc Confirmed Payment Order My Zeboune
    function HandleDscConfirmedPaymentOrderZ(Request $request, $OrderID) {
        try {
            if(Auth::check()){
                $MyProfileNow = Auth::user();
                $idProfileBssNow = $MyProfileNow->curret_profile_id_Bss;
                $SheckMyProfIDBssTv = $MyProfileNow->current_my_travel;
                $OrderIsdSpmpl = strip_tags($OrderID);
                $ProfileBssLoginNow = $MyProfileNow->ProfileUserBss()->where('id', $idProfileBssNow)->select('id')->first();
                $SheckSlahya = $MyProfileNow->EdaretMewevin()->where([
                    'confirmUser' => 1,
                    'confirmBss' => 1,
                    'typerelation' => 1,
                    'idbss' => $SheckMyProfIDBssTv,
                ])->select('id', 'edartOreders', 'PaymentEcteronect', 'totaledartPayEct', 'totalorders')->first();
                if($ProfileBssLoginNow) {
                    $request->validate([
                        'passwordSetting' => 'required|string',
                    ]);
                    $passwordStingHa = strip_tags($request->passwordSetting);
                    $shekpas = $MyProfileNow->UserPasswordStting()->where('idbss', $idProfileBssNow)->select('id', 'password')->first();
                    $passwordStingHaahing = Hash::make($passwordStingHa);
                    $passwordHash = $shekpas->password;
                    $redPassword = request('passwordSetting');
                    if($passwordHash) {
                        if(Hash::check($redPassword, $passwordHash)) {
                            $OrderToPayProd = MyOrdersPayBss::where([
                                'usernameBss' => $idProfileBssNow,
                                'id' => $OrderIsdSpmpl,
                            ])->select('id', 'typeorderforzeboune', 'typepayment', 'paymentmethod', 'idprodectspay', 'quantiteyprodectspay', 
                            'totalpriceprodectspay', 'idaccountzeboune')->first();
                            if($OrderToPayProd) {
                                if($OrderToPayProd->typeorderforzeboune == 2) {
                                    $OrderToPayProdUpd = MyOrdersPayBss::where('usernameBss', $idProfileBssNow)->select('id', 'TypeOrder', 'currentPay','totalprodectspay', 'totalpriceprodectspay', 'typepayment', 'paymentmethod', 'namezeboune', 'numberzeboune', 'numberpaymentmethod')->latest()->paginate(10);
                                    return response()->json([
                                        'message' => "Sorry Zeboune On Stop This Order",
                                        'typAction' => 3,
                                        'data' => $OrderToPayProdUpd,
                                    ], 200);
                                } else if($OrderToPayProd->typepayment == 2) {
                                    $OrderToPayProdUpd = MyOrdersPayBss::where('usernameBss', $idProfileBssNow)->select('id', 'TypeOrder', 'currentPay','totalprodectspay', 'totalpriceprodectspay', 'typepayment', 'paymentmethod', 'namezeboune', 'numberzeboune', 'numberpaymentmethod')->latest()->paginate(10);
                                    return response()->json([
                                        'message' => "Sorry Your Are On Realy Dsc Confirmed Payment Order",
                                        'data' => $OrderToPayProdUpd,
                                        'typAction' => 4,
                                    ], 200);
                                } else if($OrderToPayProd->typepayment == 1) {
                                    $OrderToPayProdUpd = MyOrdersPayBss::where('usernameBss', $idProfileBssNow)->select('id', 'TypeOrder', 'currentPay','totalprodectspay', 'totalpriceprodectspay', 'typepayment', 'paymentmethod', 'namezeboune', 'numberzeboune', 'numberpaymentmethod')->latest()->paginate(10);
                                    return response()->json([
                                        'message' => "Sorry Your Are On Confirmed Payment Order",
                                        'data' => $OrderToPayProdUpd,
                                        'typAction' => 5,
                                    ], 200);
                                }
                                $MyZeboune = ZebouneForUser::where([
                                    'usernameBss' => $MyProfileNow->curret_profile_id_Bss,
                                    'id' => $OrderToPayProd->idaccountzeboune,
                                ])->select('id', 'HaletDeyn', 'TotelBayMent', 'TotelDeyn')->first();
                                if($OrderToPayProd->paymentmethod === 'Selefe') {
                                    $MyZeboune->decrement('TotelDeyn', $OrderToPayProd->totalpriceprodectspay);
                                }
                                foreach (json_decode($OrderToPayProd->idprodectspay) as $index => $productId) {
                                    $product = ProdectUser::find($productId);
                                    if (!$product) {
                                        return response()->json([
                                            'message' => 'Sorry Error Not Found Semthing Error',
                                            'typAction' => 22,
                                            'data' => [],
                                        ], 200);
                                        continue;
                                    }
                                    $quantity = json_decode($OrderToPayProd->quantiteyprodectspay)[$index];
                                    $product->increment('totaleinstorage', $quantity);
                                }
                                $UpdateDat = [
                                    'typepayment' => 2,
                                    'typMeshole' => 1,
                                    'TypeOrder' => 2,
                                    'idMeshole' => $MyProfileNow->curret_profile_id_Bss,
                                ];                 
                                $UpdaOrderConf = $OrderToPayProd->update($UpdateDat);
                                if($UpdaOrderConf) {
                                    $MyZeboune->decrement('TotelBayMent', 1);
                                    $OrderToPayProdUpd = MyOrdersPayBss::where('usernameBss', $idProfileBssNow)->select('id', 'TypeOrder', 'currentPay','totalprodectspay', 'totalpriceprodectspay', 'typepayment', 'paymentmethod', 'namezeboune', 'numberzeboune', 'numberpaymentmethod')->latest()->paginate(10);
                                    return response()->json([
                                        'message' => "SuccesFuly Confirmed Payment Order Zeboune",
                                        'typAction' => 1,
                                        'data' => $OrderToPayProdUpd,
                                    ], 200);
                                } else {
                                    $OrderToPayProdUpd = MyOrdersPayBss::where('usernameBss', $idProfileBssNow)->select('id', 'TypeOrder', 'currentPay','totalprodectspay', 'totalpriceprodectspay', 'typepayment', 'paymentmethod', 'namezeboune', 'numberzeboune', 'numberpaymentmethod')->latest()->paginate(10);
                                    return response()->json([
                                        'message' => "Error To Confirmed Payment Order Zeboune",
                                        'typAction' => 2,
                                        'data' => $OrderToPayProdUpd,
                                    ], 200);
                                }
                            } else {
                                $OrderToPayProdUpd = MyOrdersPayBss::where('usernameBss', $idProfileBssNow)->select('id', 'TypeOrder', 'currentPay','totalprodectspay', 'totalpriceprodectspay', 'typepayment', 'paymentmethod', 'namezeboune', 'numberzeboune', 'numberpaymentmethod')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "Sorry Zeboune Has On Delete This Order Or This Order Not Found",
                                    'typAction' => 9,
                                    'data' => $OrderToPayProdUpd,
                                ], 200);
                            }
                        } else {
                            return response()->json([
                                'message' => "Sorry Your Password Setting Is Not Correct",
                                'typAction' => 7,
                                'data' => [],
                            ], 200);
                        }
                    } else {
                        return response()->json([
                            'message' => "Sorry Your Are Not Have Password Setting Plz Created",
                            'data' => [],
                            'typAction' => 8,
                        ], 200);
                    }
                } else if($SheckSlahya) {
                    if($SheckSlahya->edartOreders === 1) {
                        $OrderToPayProd = MyOrdersPayBss::where([
                            'usernameBss' => $SheckMyProfIDBssTv,
                            'id' => $OrderIsdSpmpl,
                        ])->select('id', 'typeorderforzeboune', 'typepayment', 'paymentmethod', 'idprodectspay', 'quantiteyprodectspay', 
                            'totalpriceprodectspay', 'idaccountzeboune')->first();
                        if($OrderToPayProd) {
                            $MyZeboune = ZebouneForUser::where([
                                'usernameBss' => $SheckMyProfIDBssTv,
                                'id' => $OrderToPayProd->idaccountzeboune,
                            ])->select('id', 'HaletDeyn', 'TotelBayMent', 'TotelDeyn')->first();
                            if($OrderToPayProd->typeorderforzeboune == 2) {
                                $OrderToPayProdUpd = MyOrdersPayBss::where('usernameBss', $SheckMyProfIDBssTv)->select('id', 'TypeOrder', 'currentPay','totalprodectspay', 'totalpriceprodectspay', 'typepayment', 'paymentmethod', 'namezeboune', 'numberzeboune', 'numberpaymentmethod')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "Sorry Zeboune On Stop This Order",
                                    'data' => $OrderToPayProdUpd,
                                    'typAction' => 3,
                                ], 200);
                            } else if($OrderToPayProd->typepayment == 2) {
                                $OrderToPayProdUpd = MyOrdersPayBss::where('usernameBss', $SheckMyProfIDBssTv)->select('id', 'TypeOrder', 'currentPay','totalprodectspay', 'totalpriceprodectspay', 'typepayment', 'paymentmethod', 'namezeboune', 'numberzeboune', 'numberpaymentmethod')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "Sorry Your Are On Realy Dsc Confirmed Payment Order",
                                    'data' => $OrderToPayProdUpd,
                                    'typAction' => 4,
                                ], 200);
                            } else if($OrderToPayProd->typepayment == 1) {
                                $OrderToPayProdUpd = MyOrdersPayBss::where('usernameBss', $SheckMyProfIDBssTv)->select('id', 'TypeOrder', 'currentPay','totalprodectspay', 'totalpriceprodectspay', 'typepayment', 'paymentmethod', 'namezeboune', 'numberzeboune', 'numberpaymentmethod')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "Sorry Your Are On Confirmed Payment Order",
                                    'typAction' => 5,
                                    'data' => $OrderToPayProdUpd,
                                ], 200);
                            } 
                            if($OrderToPayProd->paymentmethod === 'CASH' ||
                                $OrderToPayProd->paymentmethod === 'Selefe' 
                                ) {
                                if($OrderToPayProd->paymentmethod === 'Selefe') {
                                    $MyZeboune->decrement('TotelDeyn', $OrderToPayProd->totalpriceprodectspay);
                                }
                            } else {

                                if ($SheckSlahya->PaymentEcteronect != 1) {
                                    return response()->json([
                                        'message' => 'Sorry YOU dont Have Relation slahiyate Payment Elect',
                                        'typAction' => 13,
                                        'data' => [],
                                    ], 200);
                                }
                                $SheckSlahya->increment('totaledartPayEct', 1);
                            }
                            
                            foreach (json_decode($OrderToPayProd->idprodectspay) as $index => $productId) {
                                $product = ProdectUser::find($productId);
                                if (!$product) {
                                    return response()->json([
                                        'message' => 'Sorry Error Not Found Semthing Error',
                                        'typAction' => 22,
                                        'data' => [],
                                    ], 200);
                                    continue;
                                }
                                $quantity = json_decode($OrderToPayProd->quantiteyprodectspay)[$index];
                                $product->increment('totaleinstorage', $quantity);
                            }
                            $UpdateDat = [
                                'typepayment' => 2,
                                'TypeOrder' => 2,
                                'typMeshole' => 2,
                                'idMeshole' => $SheckSlahya->id,
                            ];
                            $UpdaOrderConf = $OrderToPayProd->update($UpdateDat);
                            if($UpdaOrderConf) {
                                $MyZeboune->decrement('TotelBayMent', 1);
                                $SheckSlahya->increment('totalorders', 1);
                                $OrderToPayProdUpd = MyOrdersPayBss::where('usernameBss', $SheckMyProfIDBssTv)->select('id', 'TypeOrder', 'currentPay','totalprodectspay', 'totalpriceprodectspay', 'typepayment', 'paymentmethod', 'namezeboune', 'numberzeboune', 'numberpaymentmethod')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "SuccesFuly Confirmed Payment Order Zeboune",
                                    'typAction' => 1,
                                    'data' => $OrderToPayProdUpd,
                                ], 200);
                            } else {
                                $OrderToPayProdUpd = MyOrdersPayBss::where('usernameBss', $SheckMyProfIDBssTv)->select('id', 'TypeOrder', 'currentPay','totalprodectspay', 'totalpriceprodectspay', 'typepayment', 'paymentmethod', 'namezeboune', 'numberzeboune', 'numberpaymentmethod')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "Error To Confirmed Payment Order Zeboune",
                                    'data' => $OrderToPayProdUpd,
                                    'typAction' => 2,
                                ], 200);
                            }

                        } else {
                            $OrderToPayProdUpd = MyOrdersPayBss::where('usernameBss', $SheckMyProfIDBssTv)->select('id', 'TypeOrder', 'currentPay','totalprodectspay', 'totalpriceprodectspay', 'typepayment', 'paymentmethod', 'namezeboune', 'numberzeboune', 'numberpaymentmethod')->latest()->paginate(10);
                            return response()->json([
                                'message' => "Sorry Zeboune Has On Delete This Order Or This Order Not Found",
                                'data' => $$OrderToPayProdUpd,
                                'typAction' => 9
                            ], 200);
                        }
                    } else {
                        $OrderToPayProdUpd = MyOrdersPayBss::where('usernameBss', $SheckMyProfIDBssTv)->select('id', 'TypeOrder', 'currentPay','totalprodectspay', 'totalpriceprodectspay', 'typepayment', 'paymentmethod', 'namezeboune', 'numberzeboune', 'numberpaymentmethod')->latest()->paginate(10);
                        return response()->json([
                            'message' => "Sorry Error  In Server Or Semthing Error Not Found",
                            'data' => $OrderToPayProdUpd,
                            'typAction' => 11
                        ], 200);
                    }
                } else {
                    return response()->json([
                        'message' => "Sorry Error  In Server Or Semthing Error Not Found",
                        'data' => 17,
                    ], 200);
                }
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 99,
            ], 200);
        }
    } // End To Dsc Confirmed Payment Order My Zeboune ==//

    // Start To Confirmed Order My Zeboune
    function HandleConfirmedOrderMyZeboune(Request $request, $OrderID) {
        try {
            if(Auth::check()){
                $ProfileData = Auth::user();
                $IdOrderPml = strip_tags($OrderID);
                $idProfileBssNow = $ProfileData->curret_profile_id_Bss;
                $SheckMyProfIDBssTv = $ProfileData->current_my_travel;

                $ProfileBssLoginNow = $ProfileData->ProfileUserBss()->where('id', $idProfileBssNow)->first();
                $SheckSlahya = $ProfileData->EdaretMewevin()->where([
                    'confirmUser' => 1,
                    'confirmBss' => 1,
                    'typerelation' => 1,
                    'idbss' => $SheckMyProfIDBssTv,
                ])->select('id', 'edartOreders', 'totalorders')->first();

                if($ProfileBssLoginNow) {
                    $request->validate([
                        'passwordSetting' => 'required|string|max:10|unique:user_password_sttings,password'
                    ]);
                    $passwordStingHa = strip_tags($request->passwordSetting);
                    $shekpas = $ProfileData->UserPasswordStting()->where('idbss', $idProfileBssNow)->select('id', 'password')->first();
                    $passwordStingHaahing = Hash::make($passwordStingHa);
                    $passwordHash = $shekpas->password;
                    $categorName = strip_tags($request->category);
                    $redPassword = request('passwordSetting');
                    if($passwordHash) {
                        if(Hash::check($redPassword, $passwordHash)) {
                            $datar = MyOrdersPayBss::where([
                                'usernameBss' => $idProfileBssNow,
                                'id' => $IdOrderPml,
                            ])->select('id', 'TypeOrder', 'typepayment', 'typeorderforzeboune')->first();
                            if($datar) {
                                if($datar->typeorderforzeboune == 2) {
                                    $datarUpd = MyOrdersPayBss::where([
                                        'usernameBss' => $idProfileBssNow,
                                    ])->select('id', 'TypeOrder', 'currentPay','totalprodectspay', 'totalpriceprodectspay', 'typepayment', 'paymentmethod', 'namezeboune', 'numberzeboune', 'numberpaymentmethod')->latest()->paginate(10);
                                    return response()->json([
                                        'message' => "Sorry Zeboune On Stop This Order",
                                        'typAction' => 3,
                                        'data' => $datarUpd,
                                    ], 200);
                                } else if($datar->TypeOrder == 1) {
                                    $datarUpd = MyOrdersPayBss::where([
                                        'usernameBss' => $idProfileBssNow,
                                    ])->select('id', 'TypeOrder', 'currentPay','totalprodectspay', 'totalpriceprodectspay', 'typepayment', 'paymentmethod', 'namezeboune', 'numberzeboune', 'numberpaymentmethod')->latest()->paginate(10);
                                    return response()->json([
                                        'message' => "Sorry Your Are On Realy Confirmed Order",
                                        'typAction' => 6,
                                        'data' => $datarUpd,
                                    ], 200);
                                } else if($datar->TypeOrder == 2) {
                                    $datarUpd = MyOrdersPayBss::where([
                                        'usernameBss' => $idProfileBssNow,
                                    ])->select('id', 'TypeOrder', 'currentPay','totalprodectspay', 'totalpriceprodectspay', 'typepayment', 'paymentmethod', 'namezeboune', 'numberzeboune', 'numberpaymentmethod')->latest()->paginate(10);
                                    return response()->json([
                                        'message' => "Sorry Your Are On Realy Desc Confirmed Payment Order",
                                        'typAction' => 10,
                                        'data' => $datarUpd,
                                    ], 200);
                                } else if($datar->typepayment == 0) {
                                    $datarUpd = MyOrdersPayBss::where([
                                        'usernameBss' => $idProfileBssNow,
                                    ])->select('id', 'TypeOrder', 'currentPay','totalprodectspay', 'totalpriceprodectspay', 'typepayment', 'paymentmethod', 'namezeboune', 'numberzeboune', 'numberpaymentmethod')->latest()->paginate(10);
                                    return response()->json([
                                        'message' => "Sorry If You Want To Confirmed Order Plz Secke For Pyment Pay Order",
                                        'typAction' => 11,
                                        'data' => $datarUpd,
                                    ], 200);
                                } else if($datar->typepayment == 2) {
                                    $datarUpd = MyOrdersPayBss::where([
                                        'usernameBss' => $idProfileBssNow,
                                    ])->select('id', 'TypeOrder', 'currentPay','totalprodectspay', 'totalpriceprodectspay', 'typepayment', 'paymentmethod', 'namezeboune', 'numberzeboune', 'numberpaymentmethod')->latest()->paginate(10);
                                    return response()->json([
                                        'message' => "Sorry If Your Are One Realy Confirmed PayMent Order So Dot Have Relation To Dsc Confirmed Order",
                                        'typAction' => 12,
                                        'data' => $datarUpd,
                                    ], 200);
                                }
                                
                                $UpdatData = $datar->update([
                                    'typepayment' => 1,
                                    'TypeOrder' => 1,
                                ]);

                                if($UpdatData) {
                                    $SheckSlahya->increment('totalorders', 1);
                                    $datarUpd = MyOrdersPayBss::where([
                                        'usernameBss' => $idProfileBssNow,
                                    ])->select('id', 'TypeOrder', 'currentPay','totalprodectspay', 'totalpriceprodectspay', 'typepayment', 'paymentmethod', 'namezeboune', 'numberzeboune', 'numberpaymentmethod')->latest()->paginate(10);
                                    return response()->json([
                                        'message' => "SuccesFuly Confirmed Order Zeboune",
                                        'data' => $datarUpd,
                                        'typAction' => 1,
                                    ], 200);
                                }else {
                                    $datarUpd = MyOrdersPayBss::where([
                                        'usernameBss' => $idProfileBssNow,
                                    ])->select('id', 'TypeOrder', 'currentPay','totalprodectspay', 'totalpriceprodectspay', 'typepayment', 'paymentmethod', 'namezeboune', 'numberzeboune', 'numberpaymentmethod')->latest()->paginate(10);
                                    return response()->json([
                                        'message' => "Sorry Zeboune Has On Delete This Order Or This Order Not Found",
                                        'typAction' => 2,
                                        'data' => $datarUpd,
                                    ], 200);
                                }

                            } else {
                                $datarUpd = MyOrdersPayBss::where([
                                    'usernameBss' => $idProfileBssNow,
                                ])->select('id', 'TypeOrder', 'currentPay','totalprodectspay', 'totalpriceprodectspay', 'typepayment', 'paymentmethod', 'namezeboune', 'numberzeboune', 'numberpaymentmethod')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "Sorry Zeboune Has On Delete This Order Or This Order Not Found",
                                    'data' => $datarUpd,
                                    'typAction' => 9
                                ], 200);
                            }

                        } else {
                            return response()->json([
                                'message' => "Sorry Your Password Setting Is Not Correct",
                                'data' => [],
                                'typAction' => 7,
                            ], 200);
                        }
                    } else {
                        return response()->json([
                            'message' => "Sorry Your Are Not Have Password Setting Plz Created",
                            'data' => [],
                            'typAction' => 8,
                        ], 200);
                    }
                } else if($SheckSlahya) {
                    if($SheckSlahya->edartOreders == 1) {
                        $datar = MyOrdersPayBss::where([
                            'usernameBss' => $SheckMyProfIDBssTv,
                            'id' => $IdOrderPml,
                        ])->select('id', 'TypeOrder', 'typepayment', 'typeorderforzeboune')->first();
                        if($datar) {
                            if($datar->typeorderforzeboune == 2) {
                                $datarUpd = MyOrdersPayBss::where([
                                    'usernameBss' => $SheckMyProfIDBssTv,
                                ])->select('id', 'TypeOrder', 'currentPay','totalprodectspay', 'totalpriceprodectspay', 'typepayment', 'paymentmethod', 'namezeboune', 'numberzeboune', 'numberpaymentmethod')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "Sorry Zeboune On Stop This Order",
                                    'data' => $datarUpd,
                                    'typAction' => 3,
                                ], 200);
                            } else if($datar->TypeOrder == 1) {
                                $datarUpd = MyOrdersPayBss::where([
                                    'usernameBss' => $SheckMyProfIDBssTv,
                                ])->select('id', 'TypeOrder', 'currentPay','totalprodectspay', 'totalpriceprodectspay', 'typepayment', 'paymentmethod', 'namezeboune', 'numberzeboune', 'numberpaymentmethod')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "Sorry Your Are On Realy Confirmed Order",
                                    'data' => $datarUpd,
                                    'typAction' => 6
                                ], 200);
                            } else if($datar->TypeOrder == 2) {
                                $datarUpd = MyOrdersPayBss::where([
                                    'usernameBss' => $SheckMyProfIDBssTv,
                                ])->select('id', 'TypeOrder', 'currentPay','totalprodectspay', 'totalpriceprodectspay', 'typepayment', 'paymentmethod', 'namezeboune', 'numberzeboune', 'numberpaymentmethod')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "Sorry Your Are On Realy Desc Confirmed Payment Order",
                                    'typAction' => 10,
                                    'data' => $datarUpd,
                                ], 200);
                            } else if($datar->typepayment == 0) {
                                $datarUpd = MyOrdersPayBss::where([
                                    'usernameBss' => $SheckMyProfIDBssTv,
                                ])->select('id', 'TypeOrder', 'currentPay','totalprodectspay', 'totalpriceprodectspay', 'typepayment', 'paymentmethod', 'namezeboune', 'numberzeboune', 'numberpaymentmethod')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "Sorry If You Want To Confirmed Order Plz Secke For Pyment Pay Order",
                                    'typAction' => 11,
                                    'data' => $datarUpd,
                                ], 200);
                            } else if($datar->typepayment == 2) {
                                $datarUpd = MyOrdersPayBss::where([
                                    'usernameBss' => $SheckMyProfIDBssTv,
                                ])->select('id', 'TypeOrder', 'currentPay','totalprodectspay', 'totalpriceprodectspay', 'typepayment', 'paymentmethod', 'namezeboune', 'numberzeboune', 'numberpaymentmethod')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "Sorry If Your Are One Realy Confirmed PayMent Order So Dot Have Relation To Dsc Confirmed Order",
                                    'typAction' => 12,
                                    'data' => $datarUpd,
                                ], 200);
                            }
                            
                            $UpdatData = $datar->update([
                                'TypeOrder' => 1,
                            ]);

                            if($UpdatData) {
                                $datarUpd = MyOrdersPayBss::where([
                                    'usernameBss' => $SheckMyProfIDBssTv,
                                ])->select('id', 'TypeOrder', 'currentPay','totalprodectspay', 'totalpriceprodectspay', 'typepayment', 'paymentmethod', 'namezeboune', 'numberzeboune', 'numberpaymentmethod')->latest()->paginate(10);
                                $SheckSlahya->increment('totalorders', 1);
                                return response()->json([
                                    'message' => "SuccesFuly Confirmed Order Zeboune",
                                    'typAction' => 1,
                                    'data' => $datarUpd,
                                ], 200);
                            } else {
                                $datarUpd = MyOrdersPayBss::where([
                                    'usernameBss' => $SheckMyProfIDBssTv,
                                ])->select('id', 'TypeOrder', 'currentPay','totalprodectspay', 'totalpriceprodectspay', 'typepayment', 'paymentmethod', 'namezeboune', 'numberzeboune', 'numberpaymentmethod')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "Sorry Zeboune Has On Delete This Order Or This Order Not Found",
                                    'typAction' => 2,
                                    'data' => $datarUpd,
                                ], 200);
                            }

                        } else {
                            $datarUpd = MyOrdersPayBss::where([
                                'usernameBss' => $SheckMyProfIDBssTv,
                            ])->select('id', 'TypeOrder', 'currentPay','totalprodectspay', 'totalpriceprodectspay', 'typepayment', 'paymentmethod', 'namezeboune', 'numberzeboune', 'numberpaymentmethod')->latest()->paginate(10);
                            return response()->json([
                                'message' => "Sorry Zeboune Has On Delete This Order Or This Order Not Found",
                                'typAction' => 9,
                                'data' => $datarUpd,
                            ], 200);
                        }
                    } else {
                        return response()->json([
                            'message' => "Sorry Error In Server Or Semthing Error Not Found",
                            'typAction' => 18,
                            'data' => [],
                        ], 200);
                    }

                } else {
                    return response()->json([
                        'message' => "Sorry Error  In Server Or Semthing Error Not Found",
                        'data' => [],
                        'typAction' => 5
                    ], 200);
                }
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 99,
            ], 200);
        }
    } //== End To Confirmed Order My Zeboune ==//

    // Start To Dsc Confirmed Order My Zeboune
    function HandleDscConfirmedOrderMyZeboune(Request $request, $OrderID) {
        try {
            if(Auth::check()){
                $IdOrderPml = strip_tags($OrderID);
                $ProfileData = Auth::user();
                $idProfileBssNow = $ProfileData->curret_profile_id_Bss;
                $SheckMyProfIDBssTv = $ProfileData->current_my_travel;

                $ProfileBssLoginNow = $ProfileData->ProfileUserBss()->where('id', $idProfileBssNow)->first();
                $SheckSlahya = $ProfileData->EdaretMewevin()->where([
                    'confirmUser' => 1,
                    'confirmBss' => 1,
                    'typerelation' => 1,
                    'idbss' => $SheckMyProfIDBssTv,
                ])->first();
                $MyProfileTrav = $ProfileData->EdaretMewevin()->where([
                    'confirmUser' => 1,
                    'confirmBss' => 1,
                    'typerelation' => 1,
                    'idbss' => $SheckMyProfIDBssTv,
                ])
                ->join('profile_user_bsses', 'edaret_mewevins.idbss', 'profile_user_bsses.id')
                ->select('profile_user_bsses.*')
                ->latest()->first();

                if($ProfileBssLoginNow) {
                    $request->validate([
                        'passwordSetting' => 'required|string|max:10|unique:user_password_sttings,password'
                    ]);
                    $passwordStingHa = strip_tags($request->passwordSetting);

                    $shekpas = $ProfileData->UserPasswordStting()->where('idbss', $idProfileBssNow)->select('id', 'password')->first();
                    $passwordStingHaahing = Hash::make($passwordStingHa);
                    $passwordHash = $shekpas->password;
                    $categorName = strip_tags($request->category);
                    $redPassword = request('passwordSetting');
                    if($passwordHash) {
                        if(Hash::check($redPassword, $passwordHash)) {

                            $datar = MyOrdersPayBss::where('usernameBss', $idProfileBssNow)->where('id', $OrderID)->first();

                            if($datar) {
                                if($datar->typeorderforzeboune == 2) {
                                    return response()->json([
                                        'message' => "Sorry Zeboune On Stop This Order",
                                        'data' => 3,
                                    ], 200);
                                } else if($datar->TypeOrder == 1) {
                                    return response()->json([
                                        'message' => "Sorry Your Are On Realy Confirmed Order",
                                        'data' => 6,
                                    ], 200);
                                } else if($datar->TypeOrder == 2) {
                                    return response()->json([
                                        'message' => "Sorry Your Are On Realy Desc Confirmed Payment Order",
                                        'data' => 10,
                                    ], 200);
                                } else if($datar->typepayment == 0) {
                                    return response()->json([
                                        'message' => "Sorry If You Want To Confirmed Order Plz Secke For Pyment Pay Order",
                                        'data' => 11,
                                    ], 200);
                                } else if($datar->typepayment == 1) {
                                    return response()->json([
                                        'message' => "Sorry If Your Are One Realy Dsc Confirmed PayMent Order So Dot Have Relation To Dsc Confirmed Order",
                                        'data' => 12,
                                    ], 200);
                                }
                                
                                $UpdatData = $datar->update([
                                    'TypeOrder' => 2,
                                ]);

                                if($UpdatData) {
                                    return response()->json([
                                        'message' => "SuccesFuly Confirmed Order Zeboune",
                                        'data' => 1,
                                    ], 200);
                                }else {
                                    return response()->json([
                                        'message' => "Sorry Zeboune Has On Delete This Order Or This Order Not Found",
                                        'data' => 2,
                                    ], 200);
                                }

                            } else {
                                return response()->json([
                                    'message' => "Sorry Zeboune Has On Delete This Order Or This Order Not Found",
                                    'data' => 9,
                                ], 200);
                            }

                        } else {
                            return response()->json([
                                'message' => "Sorry Your Password Setting Is Not Correct",
                                'data' => 7,
                            ], 200);
                        }
                    } else {
                        return response()->json([
                            'message' => "Sorry Your Are Not Have Password Setting Plz Created",
                            'data' => 8,
                        ], 200);
                    }
                } else if($MyProfileTrav) {
                    if($SheckSlahya->edartOreders == 1) {
                        $datar = MyOrdersPayBss::where([
                            'usernameBss' => $SheckMyProfIDBssTv,
                            'id' => $IdOrderPml,
                        ])->first();

                        if($datar) {
                            if($datar->typeorderforzeboune == 2) {
                                return response()->json([
                                    'message' => "Sorry Zeboune On Stop This Order",
                                    'data' => 3,
                                ], 200);
                            } else if($datar->TypeOrder == 1) {
                                return response()->json([
                                    'message' => "Sorry Your Are On Realy Confirmed Order",
                                    'data' => 6,
                                ], 200);
                            } else if($datar->TypeOrder == 2) {
                                return response()->json([
                                    'message' => "Sorry Your Are On Realy Desc Confirmed Payment Order",
                                    'data' => 10,
                                ], 200);
                            } else if($datar->typepayment == 0) {
                                return response()->json([
                                    'message' => "Sorry If You Want To Confirmed Order Plz Secke For Pyment Pay Order",
                                    'data' => 11,
                                ], 200);
                            } else if($datar->typepayment == 1) {
                                return response()->json([
                                    'message' => "Sorry If Your Are One Realy Confirmed PayMent Order So Dot Have Relation To Dsc Confirmed Order",
                                    'data' => 12,
                                ], 200);
                            }
                            
                            $UpdatData = $datar->update([
                                'TypeOrder' => 2,
                            ]);

                            if($UpdatData) {
                                return response()->json([
                                    'message' => "SuccesFuly Confirmed Order Zeboune",
                                    'data' => 1,
                                ], 200);
                            }else {
                                return response()->json([
                                    'message' => "Sorry Zeboune Has On Delete This Order Or This Order Not Found",
                                    'data' => 2,
                                ], 200);
                            }

                        } else {
                            return response()->json([
                                'message' => "Sorry Zeboune Has On Delete This Order Or This Order Not Found",
                                'data' => 9,
                            ], 200);
                        }
                    } else {
                        return response()->json([
                            'message' => "Sorry Error  In Server Or Semthing Error Not Found",
                            'data' => 18,
                        ], 200);
                    }

                } else {
                    return response()->json([
                        'message' => "Sorry Error  In Server Or Semthing Error Not Found",
                        'data' => 5,
                    ], 200);
                }
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 99,
            ], 200);
        }
    } //== End To Dsc Confirmed Order My Zeboune ==//
    // End Alls Action For Edart Order User And Bss ==//

    // Start To Send Message For User To Add Trave For Semthing Bss
    function SendMyMessageToAddUserMewve(Request $request, $UserID) {
        try {
            $MyProfileNow = Auth::user();
            $idProfileBssNow = $MyProfileNow->curret_profile_id_Bss;
            $ProfileBssLoginNow = $MyProfileNow->ProfileUserBss()->where('id', $idProfileBssNow)->select('id', 'usernameBss', 'image',)->first();
            if($ProfileBssLoginNow) {
                $SpmlUserID = strip_tags($UserID);
                $request->validate([
                    'passwordSetting' => 'required|string|max:10|unique:user_password_sttings,password',
                    'ratibeMeweve' => 'required|string',
                ]);
                $passwordStingHa = strip_tags($request->passwordSetting);
                $shekpas = $MyProfileNow->UserPasswordStting()->where('idbss', $idProfileBssNow)->select('id', 'password')->first();
                $passwordStingHaahing = Hash::make($passwordStingHa);
                $passwordHash = $shekpas->password;
                $redPassword = request('passwordSetting');
                if($passwordHash) {
                    if(Hash::check($redPassword, $passwordHash)) {
                        $ratibeUserTrave = strip_tags($request->ratibeMeweve);
                        $UserToSendMessage = ProfileUser::where('user_id', $SpmlUserID)->select('user_id', 'name', 'NumberPhone', 'image')->first();
                        $SheckUserTraveForBss = EdaretMewevin::where([
                            'idbss' => $idProfileBssNow,
                            'user_id' => $UserToSendMessage->user_id,
                        ])->select('id', 'typerelation')->first();
                        if ($SheckUserTraveForBss != null && $SheckUserTraveForBss->typerelation == 0) {
                            return response()->json([
                                'message' => 'Sorry Plze Wailte Response For User',
                                'typAction' => 5,
                                'data' => [],
                            ]);
                        }
                        $CurrentPayForBssNow = Auth::user()->CurrentPaymentForUseBss()->where('usernameBss', $ProfileBssLoginNow->id)->first();
                        $dataToSendAndCreateMyMowve = [
                            'user_id' => $UserToSendMessage->user_id,
                            'idbss' => $idProfileBssNow,
                            'NameUserSendMessage' => $ProfileBssLoginNow->usernameBss,
                            'image' => $ProfileBssLoginNow->image,
                            'titel' => " $ProfileBssLoginNow->usernameBss  طلب توضيفك من التاجر ",
                            'message' => "يرغب تاحر $ProfileBssLoginNow->usernameBss في توضيفك مما سيتيح لك مجموع من صلاحيات كما حدد الراتبا مقدر بي $ratibeUserTrave $CurrentPayForBssNow->currentCantry",
                            'TypeAccountSendMessage' => 'bss',
                            'sheckMessage' => 'tewve',
                            'TypeRelationMessageUserSend' => 1
                        ];
                        $ConfirmCreateMesaage= MessageEghar::create($dataToSendAndCreateMyMowve);
                        if(!$SheckUserTraveForBss) {
                            $CreateMyMewve = [
                                'user_id' => $UserToSendMessage->user_id,
                                'idbss' => $idProfileBssNow,
                                'nameMewve' => $UserToSendMessage->name,
                                'numberMewve' => $UserToSendMessage->NumberPhone,
                                'img' => $UserToSendMessage->image,
                                'typerelation' => 0,
                                'edartPaymentProdects' => 1,
                                'edartOreders' => 1,
                                'edartemaney' => 1,
                                'PaymentEcteronect' => 2,
                                'confirmBss' => 1,
                                'Ratibe' => $ratibeUserTrave,
                                'curent' => $CurrentPayForBssNow->currentCantry,
                                'totalMenths' => 0,
                                'totaledartmaney' => 0,
                                'totaledartPayEct' => 0,
                                'totalorders' => 0,
                                'idmessagreRel' => $ConfirmCreateMesaage->id
                            ];
                            $AddUserTew = EdaretMewevin::create($CreateMyMewve);
                        }
                        if($ConfirmCreateMesaage) {

                            return response()->json([
                                'message' => 'SuccessFuly Create Message For User Add Mewve',
                                'typAction' => 1,
                                'data' => [],
                            ]);

                        } else {
                            return response()->json([
                                'message' => 'Error Semthing Has One Error',
                                'typAction' => 2,
                                'data' => [],
                            ]);
                        }
                    } else {
                        return response()->json([
                            'message' => "Sorry Your Password Setting Is Not Correct",
                            'typAction' => 7,
                            'data' => [],
                        ], 200);
                    }
                } else {
                    return response()->json([
                        'message' => "Sorry Your Are Not Have Password Setting Plz Created",
                        'typAction' => 8,
                        'data' => [],
                    ], 200);
                }
            } else {
                return response()->json([
                    'message' => 'from Send Get Message',
                    'data' => 5
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 99,
            ], 200);
        }
    } //== End To Send Message For User To Add Trave For Semthing Bss ==//

    // Start Alls Actions For Edart Meweves Bss

     // Start Show Data From Edart Meweve Bss
    function IndexEdarteMyTewiveBss() {
        try {
            $MyProfileNow = Auth::user()->curret_profile_id_Bss;
            if($MyProfileNow) {
                $Data = EdaretMewevin::where('idbss', $MyProfileNow)->select('id', 'numberMewve', 'nameMewve', 'typerelation', 'edartPaymentProdects', 'edartOreders', 'edartemaney', 'PaymentEcteronect', 'Ratibe', 'curent')->latest()->paginate(10);
                return response()->json([
                    'message' => 'From Show All My Message',
                    'data' => $Data
                ]);
            } else {
                return response()->json([
                    'message' => 'From Show All My Message',
                    'data' => 5
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 99,
            ], 200);
        }
    } //=== End Show All Data From Edart Meweve Bss ===//

    // Start To Add User In Teweve For Semthing User Bss
    function AddAygenMeweveToTrave(Request $request, $IdOrderMewev) {
        try {
            $SplIdOrderMewev = strip_tags($IdOrderMewev);
            $MyProfileNow =  Auth::user();
            $idProfileBssNow = $MyProfileNow->curret_profile_id_Bss;
            $SheckMyProfIDBssTv = $MyProfileNow->current_my_travel;
            $ProfileBssLoginNow = $MyProfileNow->ProfileUserBss()->where('id', $idProfileBssNow)->select('id', 'usernameBss', 'image', 'image')->first();
            if($ProfileBssLoginNow) {
                $request->validate([
                    'passwordSetting' => 'required|string'
                ]);
                $passwordStingHa = strip_tags($request->passwordSetting);
                $shekpas = $MyProfileNow->UserPasswordStting()->where('idbss', $idProfileBssNow)->select('id', 'password')->first();
                $passwordStingHaahing = Hash::make($passwordStingHa);
                $passwordHash = $shekpas->password;
                $redPassword = request('passwordSetting');
                if($passwordHash) {
                    if(Hash::check($redPassword, $passwordHash)) {
                    $SheckMyMeweve = EdaretMewevin::where([
                            'idbss' => $idProfileBssNow,
                            'id' => $SplIdOrderMewev,
                        ])->first();
                        if($SheckMyMeweve) {
                            $RatibeNowIs = $SheckMyMeweve->Ratibe;
                            if($SheckMyMeweve->typerelation == 0) {
                                $DatMessageNow = MessageEghar::where([
                                    'user_id' => $SheckMyMeweve->user_id,
                                    'idbss' => $ProfileBssLoginNow->id,
                                    'id' => $SheckMyMeweve->idmessagreRel,
                                ])->select('id', 'TypeMessage')->first()->update(['TypeMessage' => 'Stop']);
                            } else if($SheckMyMeweve->typerelation == 1) {
                                $datupdat = EdaretMewevin::where([
                                    'idbss' => $idProfileBssNow,
                                ])->select('id', 'numberMewve', 'nameMewve', 'typerelation', 'edartPaymentProdects', 'edartOreders', 'edartemaney', 'PaymentEcteronect', 'Ratibe', 'curent')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "Sorry Your Are On Realy Confirmed This Realtion",
                                    'data' => $datupdat,
                                    'typAction' => 12,
                                ], 200);
                            }
                            $dataToSendAndCreateMyMowve = [
                                    'user_id' => $SheckMyMeweve->user_id,
                                    'idbss' => $ProfileBssLoginNow->id,
                                    'NameUserSendMessage' => $ProfileBssLoginNow->usernameBss,
                                    'image' => $ProfileBssLoginNow->image,
                                    'titel' => "  طلب توضيفك من التاجر $ProfileBssLoginNow->usernameBss",
                                    'message' => "يرغب تاحر $ProfileBssLoginNow->usernameBss في توضيفك مما سيتيح لك مجموع من صلاحيات كما حدد الراتبا مقدر بي $SheckMyMeweve->Ratibe $SheckMyMeweve->curent",
                                    'TypeAccountSendMessage' => 'bss',
                                    'sheckMessage' => 'tewve',
                                    'TypeRelationMessageUserSend' => 1
                                ];
                                $ConfirmCreateMesaage = MessageEghar::create($dataToSendAndCreateMyMowve);
                                $UpdatNoww = $SheckMyMeweve->update([
                                    'typerelation' => 0,
                                    'confirmUser' => 0,
                                    'edartPaymentProdects' => 1,
                                    'edartOreders' => 1,
                                    'edartemaney' => 2,
                                    'PaymentEcteronect' => 2,
                                    'confirmBss' => 1,
                                    'totaledartPayProds' => 0,
                                    'totalorders' => 0,
                                    'totaledartmaney' => 0,
                                    'totaledartPayEct' => 0,
                                    'totalMenths' => 0,
                                    'idmessagreRel' => $ConfirmCreateMesaage->id,
                                ]);
                                if($UpdatNoww || $ConfirmCreateMesaage) {
                                    $datupdat = EdaretMewevin::where([
                                        'idbss' => $idProfileBssNow,
                                    ])->select('id', 'numberMewve', 'nameMewve', 'typerelation', 'edartPaymentProdects', 'edartOreders', 'edartemaney', 'PaymentEcteronect', 'Ratibe', 'curent')->latest()->paginate(10);
                                    return response()->json([
                                        'message' => "SuccessFuly Send Again Message For Add User One Teweve",
                                        'typAction' => 1,
                                        'data' => $datupdat,
                                    ], 200);
                                } else {
                                    $datupdat = EdaretMewevin::where([
                                        'idbss' => $idProfileBssNow,
                                    ])->select('id', 'numberMewve', 'nameMewve', 'typerelation', 'edartPaymentProdects', 'edartOreders', 'edartemaney', 'PaymentEcteronect', 'Ratibe', 'curent')->latest()->paginate(10);
                                    return response()->json([
                                        'message' => "Sorry Zeboune Has On Delete This Order Or This Order Not Found",
                                        'typAction' => 13,
                                        'data' => $datupdat,
                                    ], 200);
                                }
                        } else {
                            $datupdat = EdaretMewevin::where([
                                'idbss' => $idProfileBssNow,
                            ])->select('id', 'numberMewve', 'nameMewve', 'typerelation', 'edartPaymentProdects', 'edartOreders', 'edartemaney', 'PaymentEcteronect', 'Ratibe', 'curent')->latest()->paginate(10);
                            return response()->json([
                                'message' => "Sorry Zeboune Has On Delete This Order Or This Order Not Found",
                                'data' => $datupdat,
                                'typAction' => 2,
                            ], 200);
                        }
                    } else {
                        return response()->json([
                            'message' => "Sorry Your Password Setting Is Not Correct",
                            'typAction' => 7,
                            'data' => []
                        ], 200);
                    }
                } else {
                    return response()->json([
                        'message' => "Sorry Your Are Not Have Password Setting Plz Created",
                        'typAction' => 8,
                        'data' => [],
                    ], 200);
                }
            } else if($SheckMyProfIDBssTv ) {
                return response()->json([
                        'message' => "Sorry You Dont Have Realayion Fro Edart Maleya",
                        'data' => 10,
                    ], 200);
            } else {
                return response()->json([
                    'message' => "Sorry Error  In Server Or Semthing Error Not Found",
                    'data' => 5,
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 99,
            ], 200);
        }
    } //== End To Add User In Teweve For Semthing User Bss ==//

    // Start To Stop User In Teweve For Semthing User Bss
    function StopAddMeweveToTraveForBss(Request $request, $IdOrderMewev) {
        try {
            $SplIdOrderMewev = strip_tags($IdOrderMewev);
            $MyProfileNow = Auth::user();
            $idProfileBssNow = $MyProfileNow->curret_profile_id_Bss;
            $SheckMyProfIDBssTv = $MyProfileNow->current_my_travel;
            $ProfileBssLoginNow = $MyProfileNow->ProfileUserBss()->where('id', $idProfileBssNow)->select('id', 'usernameBss', 'image', 'image')->first();
            if($ProfileBssLoginNow) {
                $request->validate([
                    'passwordSetting' => 'required|string'
                ]);
                $passwordStingHa = strip_tags($request->passwordSetting);
                $shekpas = $MyProfileNow->UserPasswordStting()->where('idbss', $idProfileBssNow)->select('id', 'password')->first();
                $passwordStingHaahing = Hash::make($passwordStingHa);
                $passwordHash = $shekpas->password;
                $redPassword = request('passwordSetting');
                if($passwordHash) {
                    if(Hash::check($redPassword, $passwordHash)) {
                    $SheckMyMeweve = EdaretMewevin::where([
                            'idbss' => $idProfileBssNow,
                            'id' => $SplIdOrderMewev,
                        ])->select('id', 'user_id', 'confirmUser', 'typerelation', 'confirmBss', 'edartPaymentProdects', "edartemaney", 'edartOreders', 'PaymentEcteronect', 'idmessagreRel')->first();
                        if($SheckMyMeweve) {
                            if($SheckMyMeweve->typerelation == 2) {
                                $dataupd = EdaretMewevin::where('idbss', $idProfileBssNow)->select('id', 'numberMewve', 'nameMewve', 'typerelation', 'edartPaymentProdects', 'edartOreders', 'edartemaney', 'PaymentEcteronect', 'Ratibe', 'curent')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "Sorry Your Are On Realy Stopp This Realtion",
                                    'data' => $dataupd,
                                    'typAction' => 12,
                                ], 200);
                            } 

                            if($SheckMyMeweve->typerelation == 1) {
                                $dataToSendAndCreateMyMowve = [
                                    'user_id' => $SheckMyMeweve->user_id,
                                    'idbss' => $ProfileBssLoginNow->id,
                                    'NameUserSendMessage' => $ProfileBssLoginNow->usernameBss,
                                    'image' => $ProfileBssLoginNow->image,
                                    'titel' => "  تنبيه لقد قرر التاجر $ProfileBssLoginNow->usernameBss فصلك عن لعمل",
                                    'message' => "لقد قرر تاجر $ProfileBssLoginNow->usernameBss لذي كنت تعمل معه بفصلك عن لعمل مما يعني سحب صلاحية دخول حسابه تجاري منك للمزيد تواصل مع تاجر",
                                    'TypeAccountSendMessage' => 'bss',
                                    'sheckMessage' => 'StopTewve',
                                    'TypeMessage' => "Confirmed",
                                    'TypeRelationMessageUserSend' => 1,
                                ];
                                $ConfirmCreateMesaage = MessageEghar::create($dataToSendAndCreateMyMowve);
                            } else if($SheckMyMeweve->typerelation == 0) {
                                $ConfirmCreateMesaage = MessageEghar::where([
                                    'user_id' => $SheckMyMeweve->user_id,
                                    'idbss' => $ProfileBssLoginNow->id,
                                    'id' => $SheckMyMeweve->idmessagreRel,
                                ])->first()->update(['TypeMessage' => "Stop", 'TypeAccountSendMessage' => 2]);
                            }
                            $UpdatNoww = $SheckMyMeweve->update([
                                'typerelation' => 2,
                                'edartPaymentProdects' => 0,
                                'edartOreders' => 0,
                                'edartemaney' => 0,
                                'PaymentEcteronect' => 0,
                                'confirmBss' => 2,
                                'confirmUser' => 0,
                            ]);
                            if($UpdatNoww) {
                                $dataupd = EdaretMewevin::where('idbss', $idProfileBssNow)->select('id', 'numberMewve', 'nameMewve', 'typerelation', 'edartPaymentProdects', 'edartOreders', 'edartemaney', 'PaymentEcteronect', 'Ratibe', 'curent')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "SuccesFuly Confirmed Payment Order Zeboune",
                                    'data' => $dataupd,
                                    'typAction' => 1,
                                ], 200);
                            } else {
                                $dataupd = EdaretMewevin::where('idbss', $idProfileBssNow)->select('id', 'numberMewve', 'nameMewve', 'typerelation', 'edartPaymentProdects', 'edartOreders', 'edartemaney', 'PaymentEcteronect', 'Ratibe', 'curent')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "Sorry Zeboune Has On Delete This Order Or This Order Not Found",
                                    'data' => $dataupd,
                                    'typAction' => 13,
                                ], 200);
                            }
                        } else {
                            $dataupd = EdaretMewevin::where('idbss', $idProfileBssNow)->select('id', 'numberMewve', 'nameMewve', 'typerelation', 'edartPaymentProdects', 'edartOreders', 'edartemaney', 'PaymentEcteronect', 'Ratibe', 'curent')->latest()->paginate(10);
                            return response()->json([
                                'message' => "Sorry Zeboune Has On Delete This Order Or This Order Not Found",
                                'data' => $dataupd,
                                'typAction' => 2,
                            ], 200);
                        }
                    } else {
                        return response()->json([
                            'message' => "Sorry Your Password Setting Is Not Correct",
                            'typAction' => 7,
                            'data' => [],
                        ], 200);
                    }
                } else {
                    return response()->json([
                        'message' => "Sorry Your Are Not Have Password Setting Plz Created",
                        'typAction' => 8,
                        'data' => [],
                    ], 200);
                }
            } else if($SheckMyProfIDBssTv ) {
                return response()->json([
                        'message' => "Sorry You Dont Have Realayion Fro Edart Maleya",
                        'data' => 10,
                    ], 200);
            } else {
                return response()->json([
                    'message' => "Sorry Error  In Server Or Semthing Error Not Found",
                    'data' => 5,
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 99,
            ], 200);
        }
    } //== End To Add User In Teweve For Semthing User Bss ==//

    // Start To Active Slahiyet Edart Maneye
    function ActiveEdartManyBssForMeweveToTrave(Request $request, $IdOrderMewev) {
        try {
            $SplIdOrderMewev = strip_tags($IdOrderMewev);
            $MyProfileNow= Auth::user();
            $idProfileBssNow = $MyProfileNow->curret_profile_id_Bss;
            $SheckMyProfIDBssTv = $MyProfileNow->current_my_travel;
            $ProfileBssLoginNow = $MyProfileNow->ProfileUserBss()->where('id', $idProfileBssNow)->select('id')->select('id')->first();
            if($ProfileBssLoginNow) {
                $request->validate([
                    'passwordSetting' => 'required|string'
                ]);
                $passwordStingHa = strip_tags($request->passwordSetting);
                $shekpas = $MyProfileNow->UserPasswordStting()->where('idbss', $idProfileBssNow)->select('id', 'password')->first();
                $passwordStingHaahing = Hash::make($passwordStingHa);
                $passwordHash = $shekpas->password;
                $redPassword = request('passwordSetting');
                if($passwordHash) {
                    if(Hash::check($redPassword, $passwordHash)) {
                    $SheckMyMeweve = EdaretMewevin::where([
                            'idbss' => $idProfileBssNow,
                            'id' => $SplIdOrderMewev,
                        ])->select('id', 'confirmUser', 'typerelation', 'edartemaney')->first();
                        if($SheckMyMeweve) {
                            if($SheckMyMeweve->confirmUser == 0) {
                                $DataUpdat = EdaretMewevin::where('idbss', $idProfileBssNow)->select('id', 'numberMewve', 'nameMewve', 'typerelation', 'edartPaymentProdects', 'edartOreders', 'edartemaney', 'PaymentEcteronect', 'Ratibe', 'curent')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "Plz Waite For User Send Relation Trave",
                                    'typAction' => 3,
                                    'data' => $DataUpdat,
                                ], 200);
                            } else if($SheckMyMeweve->typerelation == 2) {
                                $DataUpdat = EdaretMewevin::where('idbss', $idProfileBssNow)->select('id', 'numberMewve', 'nameMewve', 'typerelation', 'edartPaymentProdects', 'edartOreders', 'edartemaney', 'PaymentEcteronect', 'Ratibe', 'curent')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "Sorry User Dont Have Content Trave For You",
                                    'typAction' => 9,
                                    'data' => $DataUpdat,
                                ], 200);
                            } else if($SheckMyMeweve->confirmUser == 2) {
                                $DataUpdat = EdaretMewevin::where('idbss', $idProfileBssNow)->select('id', 'numberMewve', 'nameMewve', 'typerelation', 'edartPaymentProdects', 'edartOreders', 'edartemaney', 'PaymentEcteronect', 'Ratibe', 'curent')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "Sorry User Dont Have Content Trave For You",
                                    'typAction' => 6,
                                    'data' => $DataUpdat,
                                ], 200);
                            } else if($SheckMyMeweve->edartemaney == 1) {
                                $DataUpdat = EdaretMewevin::where('idbss', $idProfileBssNow)->select('id', 'numberMewve', 'nameMewve', 'typerelation', 'edartPaymentProdects', 'edartOreders', 'edartemaney', 'PaymentEcteronect', 'Ratibe', 'curent')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "Sorry Your Are On Realy Confirmed This Realtion",
                                    'typAction' => 12,
                                    'data' => $DataUpdat,
                                ], 200);
                            }
                            $UpdatNoww = $SheckMyMeweve->update([
                                'edartemaney' => 1,
                            ]);
                            if($UpdatNoww) {
                                $DataUpdat = EdaretMewevin::where('idbss', $idProfileBssNow)->select('id', 'numberMewve', 'nameMewve', 'typerelation', 'edartPaymentProdects', 'edartOreders', 'edartemaney', 'PaymentEcteronect', 'Ratibe', 'curent')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "SuccesFuly Confirmed Edart Many",
                                    'typAction' => 1,
                                    'data' => $DataUpdat,
                                ], 200);
                            } else {
                                $DataUpdat = EdaretMewevin::where('idbss', $idProfileBssNow)->select('id', 'numberMewve', 'nameMewve', 'typerelation', 'edartPaymentProdects', 'edartOreders', 'edartemaney', 'PaymentEcteronect', 'Ratibe', 'curent')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "Sorry Zeboune Has On Delete This Order Or This Order Not Found",
                                    'typAction' => 13,
                                    'data' => $DataUpdat,
                                ], 200);
                            }
                        } else {
                            $DataUpdat = EdaretMewevin::where('idbss', $idProfileBssNow)->select('id', 'numberMewve', 'nameMewve', 'typerelation', 'edartPaymentProdects', 'edartOreders', 'edartemaney', 'PaymentEcteronect', 'Ratibe', 'curent')->latest()->paginate(10);
                            return response()->json([
                                'message' => "Sorry Zeboune Has On Delete This Order Or This Order Not Found",
                                'data' => $DataUpdat,
                                'typAction' => 2,
                            ], 200);
                        }
                    } else {
                        return response()->json([
                            'message' => "Sorry Your Password Setting Is Not Correct",
                            'data' => [],
                            'typAction' => 7,
                        ], 200);
                    }
                } else {
                    return response()->json([
                        'message' => "Sorry Your Are Not Have Password Setting Plz Created",
                        'typAction' => 8,
                        'data' => [],
                    ], 200);
                }
            } else if($SheckMyProfIDBssTv ) {
                return response()->json([
                        'message' => "Sorry You Dont Have Realayion Fro Edart Maleya",
                        'data' => 10,
                    ], 200);
            } else {
                return response()->json([
                    'message' => "Sorry Error  In Server Or Semthing Error Not Found",
                    'data' => 5,
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 99,
            ], 200);
        }
    } //== End To Active Slahiyet Edart Maneye ==//

    // Start To Stop Slahiyet Edart Maneye
    function DscActiveEdartManyBssForMeweveToTrave(Request $request, $IdOrderMewev) {
        try {
            $SplIdOrderMewev = strip_tags($IdOrderMewev);
            $MyProfileNow= Auth::user();
            $idProfileBssNow = $MyProfileNow->curret_profile_id_Bss;
            $SheckMyProfIDBssTv = $MyProfileNow->current_my_travel;
            $ProfileBssLoginNow = $MyProfileNow->ProfileUserBss()->where('id', $idProfileBssNow)->select('id')->first();
            if($ProfileBssLoginNow) {
                $request->validate([
                    'passwordSetting' => 'required|string'
                ]);
                $passwordStingHa = strip_tags($request->passwordSetting);
                $shekpas = $MyProfileNow->UserPasswordStting()->where('idbss', $idProfileBssNow)->select('id', 'password')->first();
                $passwordStingHaahing = Hash::make($passwordStingHa);
                $passwordHash = $shekpas->password;
                $redPassword = request('passwordSetting');
                if($passwordHash) {
                    if(Hash::check($redPassword, $passwordHash)) {
                    $SheckMyMeweve = EdaretMewevin::where([
                            'idbss' => $idProfileBssNow,
                            'id' => $SplIdOrderMewev,
                        ])->select('id', 'confirmUser', 'typerelation', 'edartemaney')->first();
                        if($SheckMyMeweve) {
                            if($SheckMyMeweve->confirmUser == 0) {
                                $DataUpdat = EdaretMewevin::where('idbss', $idProfileBssNow)->select('id', 'numberMewve', 'nameMewve', 'typerelation', 'edartPaymentProdects', 'edartOreders', 'edartemaney', 'PaymentEcteronect', 'Ratibe', 'curent')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "Plz Waite For User Send Relation Trave",
                                    'typAction' => 3,
                                    'data' => $DataUpdat,
                                ], 200);
                            } else if($SheckMyMeweve->typerelation == 2) {
                                $DataUpdat = EdaretMewevin::where('idbss', $idProfileBssNow)->select('id', 'numberMewve', 'nameMewve', 'typerelation', 'edartPaymentProdects', 'edartOreders', 'edartemaney', 'PaymentEcteronect', 'Ratibe', 'curent')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "Sorry User Dont Have Content Trave For You",
                                    'typAction' => 9,
                                    'data' => $DataUpdat,
                                ], 200);
                            } else if($SheckMyMeweve->confirmUser == 2) {
                                $DataUpdat = EdaretMewevin::where('idbss', $idProfileBssNow)->select('id', 'numberMewve', 'nameMewve', 'typerelation', 'edartPaymentProdects', 'edartOreders', 'edartemaney', 'PaymentEcteronect', 'Ratibe', 'curent')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "Sorry User Dont Have Content Trave For You",
                                    'typAction' => 6,
                                    'data' => $DataUpdat,
                                ], 200);
                            } else if($SheckMyMeweve->edartemaney == 2) {
                                $DataUpdat = EdaretMewevin::where('idbss', $idProfileBssNow)->select('id', 'numberMewve', 'nameMewve', 'typerelation', 'edartPaymentProdects', 'edartOreders', 'edartemaney', 'PaymentEcteronect', 'Ratibe', 'curent')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "Sorry Your Are On Realy Confirmed This Realtion",
                                    'typAction' => 12,
                                    'data' => $DataUpdat,
                                ], 200);
                            }

                            $UpdatNoww = $SheckMyMeweve->update([
                                'edartemaney' => 2,
                            ]);

                            if($UpdatNoww) {
                                $DataUpdat = EdaretMewevin::where('idbss', $idProfileBssNow)->select('id', 'numberMewve', 'nameMewve', 'typerelation', 'edartPaymentProdects', 'edartOreders', 'edartemaney', 'PaymentEcteronect', 'Ratibe', 'curent')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "SuccesFuly Confirmed Edart Many",
                                    'typAction' => 1,
                                    'data' => $DataUpdat,
                                ], 200);
                            } else {
                                $DataUpdat = EdaretMewevin::where('idbss', $idProfileBssNow)->select('id', 'numberMewve', 'nameMewve', 'typerelation', 'edartPaymentProdects', 'edartOreders', 'edartemaney', 'PaymentEcteronect', 'Ratibe', 'curent')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "Sorry Zeboune Has On Delete This Order Or This Order Not Found",
                                    'typAction' => 13,
                                    'data' => $DataUpdat,
                                ], 200);
                            }
                        } else {
                            $DataUpdat = EdaretMewevin::where('idbss', $idProfileBssNow)->select('id', 'numberMewve', 'nameMewve', 'typerelation', 'edartPaymentProdects', 'edartOreders', 'edartemaney', 'PaymentEcteronect', 'Ratibe', 'curent')->latest()->paginate(10);
                            return response()->json([
                                'message' => "Sorry Zeboune Has On Delete This Order Or This Order Not Found",
                                'data' => $DataUpdat,
                                'typAction' => 2,
                            ], 200);
                        }
                    } else {
                        return response()->json([
                            'message' => "Sorry Your Password Setting Is Not Correct",
                            'data' => [],
                            'typAction' => 7,
                        ], 200);
                    }
                } else {
                    return response()->json([
                        'message' => "Sorry Your Are Not Have Password Setting Plz Created",
                        'typAction' => 8,
                        'data' => [],
                    ], 200);
                }
            } else if($SheckMyProfIDBssTv ) {
                return response()->json([
                        'message' => "Sorry You Dont Have Realayion Fro Edart Maleya",
                        'data' => 10,
                    ], 200);
            } else {
                return response()->json([
                    'message' => "Sorry Error  In Server Or Semthing Error Not Found",
                    'data' => 5,
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 99,
            ], 200);
        }
    } //== End To Stop Slahiyet Edart Maneye ==//

    // Start To Active Slahiyet Edart Pay Prodects
    function ActiveEdartPayProdectsBssForMeweveToTrave(Request $request, $IdOrderMewev) {
        try {
            $SplIdOrderMewev = strip_tags($IdOrderMewev);
            $ProfileLoginNow = Auth::user();
            $idProfileBssNow = $ProfileLoginNow->curret_profile_id_Bss;
            $SheckMyProfIDBssTv = $ProfileLoginNow->current_my_travel;
            $ProfileBssLoginNow = $ProfileLoginNow->ProfileUserBss()->where('id', $idProfileBssNow)->select('id')->first();
            if($ProfileBssLoginNow) {
                $request->validate([
                    'passwordSetting' => 'required|string'
                ]);
                $passwordStingHa = strip_tags($request->passwordSetting);
                $shekpas = $ProfileLoginNow->UserPasswordStting()->where('idbss', $idProfileBssNow)->select('id', 'password')->first();
                $passwordStingHaahing = Hash::make($passwordStingHa);
                $passwordHash = $shekpas->password;
                $redPassword = request('passwordSetting');
                if($passwordHash) {
                    if(Hash::check($redPassword, $passwordHash)) {
                        $SheckMyMeweve = EdaretMewevin::where([
                            'idbss' => $idProfileBssNow,
                            'id' => $SplIdOrderMewev,
                        ])->select('id', 'confirmUser', 'typerelation', 'edartPaymentProdects')->first();
                        if($SheckMyMeweve) {
                            if($SheckMyMeweve->confirmUser == 0) {
                                $DataUpdt = EdaretMewevin::where('idbss', $idProfileBssNow)->select('id', 'numberMewve', 'nameMewve', 'typerelation', 'edartPaymentProdects', 'edartOreders', 'edartemaney', 'PaymentEcteronect', 'Ratibe', 'curent')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "Plz Waite For User Send Relation Trave",
                                    'typAction' => 3,
                                    'data' => $DataUpdt,
                                ], 200);
                            } else if($SheckMyMeweve->typerelation == 2) {
                                $DataUpdt = EdaretMewevin::where('idbss', $idProfileBssNow)->select('id', 'numberMewve', 'nameMewve', 'typerelation', 'edartPaymentProdects', 'edartOreders', 'edartemaney', 'PaymentEcteronect', 'Ratibe', 'curent')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "Sorry User Dont Have Content Trave For You",
                                    'typAction' => 9,
                                    'data' => $DataUpdt,
                                ], 200);
                            } else if($SheckMyMeweve->confirmUser == 2) {
                                $datUpdt = EdaretMewevin::where('idbss', $idProfileBssNow)->select('id', 'numberMewve', 'nameMewve', 'typerelation', 'edartPaymentProdects', 'edartOreders', 'edartemaney', 'PaymentEcteronect', 'Ratibe', 'curent')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "Sorry User Dont Have Content Trave For You",
                                    'data' => $datUpdt,
                                    'typAction' => 7,
                                ], 200);
                            } else if($SheckMyMeweve->edartPaymentProdects == 1) {
                                $datUpdt = EdaretMewevin::where('idbss', $idProfileBssNow)->select('id', 'numberMewve', 'nameMewve', 'typerelation', 'edartPaymentProdects', 'edartOreders', 'edartemaney', 'PaymentEcteronect', 'Ratibe', 'curent')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "Sorry Your Are On Realy Confirmed This Realtion",
                                    'data' => $datUpdt,
                                    'typAction' => 12,
                                ], 200);
                            }
                            $UpdatNoww = $SheckMyMeweve->update([
                                'edartPaymentProdects' => 1,
                            ]);
                            if($UpdatNoww) {
                                $datUpdt = EdaretMewevin::where('idbss', $idProfileBssNow)->select('id', 'numberMewve', 'nameMewve', 'typerelation', 'edartPaymentProdects', 'edartOreders', 'edartemaney', 'PaymentEcteronect', 'Ratibe', 'curent')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "SuccesFuly Confirmed Edart Many",
                                    'data' => $datUpdt,
                                    'typAction' => 1,
                                ], 200);
                            } else {
                                $datUpdt = EdaretMewevin::where('idbss', $idProfileBssNow)->select('id', 'numberMewve', 'nameMewve', 'typerelation', 'edartPaymentProdects', 'edartOreders', 'edartemaney', 'PaymentEcteronect', 'Ratibe', 'curent')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "Sorry Zeboune Has On Delete This Order Or This Order Not Found",
                                    'data' => $datUpdt,
                                    'typAction' => 13,
                                ], 200);
                            }
                        } else {
                            $datUpdt = EdaretMewevin::where('idbss', $idProfileBssNow)->select('id', 'numberMewve', 'nameMewve', 'typerelation', 'edartPaymentProdects', 'edartOreders', 'edartemaney', 'PaymentEcteronect', 'Ratibe', 'curent')->latest()->paginate(10);
                            return response()->json([
                                'message' => "Sorry Zeboune Has On Delete This Order Or This Order Not Found",
                                'data' => $datUpdt,
                                'typAction' => 2,
                            ], 200);
                        }
                    } else {
                        return response()->json([
                            'message' => "Sorry Your Password Setting Is Not Correct",
                            'typAction' => 7,
                            'data' => [],
                        ], 200);
                    }
                } else {
                    return response()->json([
                        'message' => "Sorry Your Are Not Have Password Setting Plz Created",
                        'typAction' => 8,
                        'data' => [],
                    ], 200);
                }
            } else if($SheckMyProfIDBssTv ) {
                return response()->json([
                        'message' => "Sorry You Dont Have Realayion Fro Edart Maleya",
                        'data' => 10,
                    ], 200);
            } else {
                return response()->json([
                    'message' => "Sorry Error  In Server Or Semthing Error Not Found",
                    'data' => 5,
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 99,
            ], 200);
        }
    } //== End To Active Slahiyet Edart Pay Prodects ==//

    // Start To Dsc Active Slahiyet Edart Pay Prodects For Meweve
    function DscActiveEdartPayProdectsBssForMeweveToTrave(Request $request, $IdOrderMewev) {
        try {
            $SplIdOrderMewev = strip_tags($IdOrderMewev);
            $MyProfileNow = Auth::user();
            $idProfileBssNow = $MyProfileNow->curret_profile_id_Bss;
            $SheckMyProfIDBssTv = $MyProfileNow->current_my_travel;
            $ProfileBssLoginNow = $MyProfileNow->ProfileUserBss()->where('id', $idProfileBssNow)->select('id')->first();
            if($ProfileBssLoginNow) {
                $request->validate([
                    'passwordSetting' => 'required|string'
                ]);
                $passwordStingHa = strip_tags($request->passwordSetting);
                $shekpas = $MyProfileNow->UserPasswordStting()->where('idbss', $idProfileBssNow)->select('id', 'password')->first();
                $passwordStingHaahing = Hash::make($passwordStingHa);
                $passwordHash = $shekpas->password;
                $redPassword = request('passwordSetting');
                if($passwordHash) {
                    if(Hash::check($redPassword, $passwordHash)) {
                    $SheckMyMeweve = EdaretMewevin::where([
                            'idbss' => $idProfileBssNow,
                            'id' => $SplIdOrderMewev,
                        ])->select('id', 'confirmUser', 'typerelation', 'edartPaymentProdects')->first();
                        if($SheckMyMeweve) {
                            if($SheckMyMeweve->confirmUser == 0) {
                                $DataUpdt = EdaretMewevin::where('idbss', $idProfileBssNow)->select('id', 'numberMewve', 'nameMewve', 'typerelation', 'edartPaymentProdects', 'edartOreders', 'edartemaney', 'PaymentEcteronect', 'Ratibe', 'curent')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "Plz Waite For User Send Relation Trave",
                                    'typAction' => 3,
                                    'data' => $DataUpdt,
                                ], 200);
                            } else if($SheckMyMeweve->typerelation == 2 || $SheckMyMeweve->confirmUser == 2) {
                                $datyUpdt = EdaretMewevin::where('idbss', $idProfileBssNow)->select('id', 'numberMewve', 'nameMewve', 'typerelation', 'edartPaymentProdects', 'edartOreders', 'edartemaney', 'PaymentEcteronect', 'Ratibe', 'curent')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "Sorry User Dont Have Content Trave For You",
                                    'typAction' => 9,
                                    'data' => $datyUpdt,
                                ], 200);
                            } else if($SheckMyMeweve->edartPaymentProdects == 2) {
                                $datyUpdt = EdaretMewevin::where('idbss', $idProfileBssNow)->select('id', 'numberMewve', 'nameMewve', 'typerelation', 'edartPaymentProdects', 'edartOreders', 'edartemaney', 'PaymentEcteronect', 'Ratibe', 'curent')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "Sorry Your Are On Realy Confirmed This Realtion",
                                    'data' => $datyUpdt,
                                    'typAction' => 12,
                                ], 200);
                            }
                            $UpdatNoww = $SheckMyMeweve->update([
                                'edartPaymentProdects' => 2,
                            ]);
                            if($UpdatNoww) {
                                $datyUpdt = EdaretMewevin::where('idbss', $idProfileBssNow)->select('id', 'numberMewve', 'nameMewve', 'typerelation', 'edartPaymentProdects', 'edartOreders', 'edartemaney', 'PaymentEcteronect', 'Ratibe', 'curent')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "SuccesFuly Confirmed Edart Many",
                                    'typAction' => 1,
                                    'data' => $datyUpdt,
                                ], 200);
                            } else {
                                $datyUpdt = EdaretMewevin::where('idbss', $idProfileBssNow)->select('id', 'numberMewve', 'nameMewve', 'typerelation', 'edartPaymentProdects', 'edartOreders', 'edartemaney', 'PaymentEcteronect', 'Ratibe', 'curent')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "Sorry Zeboune Has On Delete This Order Or This Order Not Found",
                                    'data' => $datyUpdt,
                                    'typAction' => 13,
                                ], 200);
                            }
                        } else {
                            $datyUpdt = EdaretMewevin::where('idbss', $idProfileBssNow)->select('id', 'numberMewve', 'nameMewve', 'typerelation', 'edartPaymentProdects', 'edartOreders', 'edartemaney', 'PaymentEcteronect', 'Ratibe', 'curent')->latest()->paginate(10);
                            return response()->json([
                                'message' => "Sorry Zeboune Has On Delete This Order Or This Order Not Found",
                                'data' => $datyUpdt,
                                'typAction' => 2,
                            ], 200);
                        }
                    } else {
                        return response()->json([
                            'message' => "Sorry Your Password Setting Is Not Correct",
                            'typAction' => 7,
                            'data' => [],
                        ], 200);
                    }
                } else {
                    return response()->json([
                        'message' => "Sorry Your Are Not Have Password Setting Plz Created",
                        'data' => [],
                        'typAction' => 8,
                    ], 200);
                }
            } else if($SheckMyProfIDBssTv ) {
                return response()->json([
                        'message' => "Sorry You Dont Have Realayion Fro Edart Maleya",
                        'data' => 10,
                    ], 200);
            } else {
                return response()->json([
                    'message' => "Sorry Error  In Server Or Semthing Error Not Found",
                    'data' => 5,
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 99,
            ], 200);
        }
    } //== End To Dsc Active Slahiyet Edart Pay Prodects For Meweve ==//

    // Start To Active Slahiyet Edart Orders Bss
    function ActiveEdartOrdersBssForMeweveToTrave(Request $request, $IdOrderMewev) {
        try {
            $SplIdOrderMewev = strip_tags($IdOrderMewev);
            $MyProfileNow = Auth::user();
            $idProfileBssNow = $MyProfileNow->curret_profile_id_Bss;
            $SheckMyProfIDBssTv = $MyProfileNow->current_my_travel;
            $ProfileBssLoginNow = $MyProfileNow->ProfileUserBss()->where('id', $idProfileBssNow)->select('id')->first();
            if($ProfileBssLoginNow) {
                $request->validate([
                    'passwordSetting' => 'required|string'
                ]);
                $passwordStingHa = strip_tags($request->passwordSetting);
                $shekpas = $MyProfileNow->UserPasswordStting()->where('idbss', $idProfileBssNow)->select('id', 'password')->first();
                $passwordStingHaahing = Hash::make($passwordStingHa);
                $passwordHash = $shekpas->password;
                $redPassword = request('passwordSetting');
                if($passwordHash) {
                    if(Hash::check($redPassword, $passwordHash)) {
                    $SheckMyMeweve = EdaretMewevin::where([
                            'idbss' => $idProfileBssNow,
                            'id' => $SplIdOrderMewev,
                        ])->select('id', 'confirmUser', 'typerelation', 'edartOreders')->first();
                        if($SheckMyMeweve) {
                            if($SheckMyMeweve->confirmUser == 0) {
                                $datUpdt = EdaretMewevin::where('idbss', $idProfileBssNow)->select('id', 'numberMewve', 'nameMewve', 'typerelation', 'edartPaymentProdects', 'edartOreders', 'edartemaney', 'PaymentEcteronect', 'Ratibe', 'curent')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "Plz Waite For User Send Relation Trave",
                                    'data' => $datUpdt,
                                    'typAction' => 3,
                                ], 200);
                            } else if($SheckMyMeweve->typerelation == 2) {
                                $datUpdt = EdaretMewevin::where('idbss', $idProfileBssNow)->select('id', 'numberMewve', 'nameMewve', 'typerelation', 'edartPaymentProdects', 'edartOreders', 'edartemaney', 'PaymentEcteronect', 'Ratibe', 'curent')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "Sorry User Dont Have Content Trave For You",
                                    'typAction' => 9,
                                    'data' => $datUpdt,
                                ], 200);
                            } else if($SheckMyMeweve->confirmUser == 2) {
                                $datUpdt = EdaretMewevin::where('idbss', $idProfileBssNow)->select('id', 'numberMewve', 'nameMewve', 'typerelation', 'edartPaymentProdects', 'edartOreders', 'edartemaney', 'PaymentEcteronect', 'Ratibe', 'curent')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "Sorry User Dont Have Content Trave For You",
                                    'typAction' => 6,
                                    'data' => $datUpdt,
                                ], 200);
                            } else if($SheckMyMeweve->edartOreders == 1) {
                                $datUpdt = EdaretMewevin::where('idbss', $idProfileBssNow)->select('id', 'numberMewve', 'nameMewve', 'typerelation', 'edartPaymentProdects', 'edartOreders', 'edartemaney', 'PaymentEcteronect', 'Ratibe', 'curent')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "Sorry Your Are On Realy Confirmed This Realtion",
                                    'typAction' => 12,
                                    'data' => $datUpdt,
                                ], 200);
                            } 

                            $UpdatNoww = $SheckMyMeweve->update([
                                'edartOreders' => 1,
                            ]);

                            if($UpdatNoww) {
                                $datUpdt = EdaretMewevin::where('idbss', $idProfileBssNow)->select('id', 'numberMewve', 'nameMewve', 'typerelation', 'edartPaymentProdects', 'edartOreders', 'edartemaney', 'PaymentEcteronect', 'Ratibe', 'curent')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "SuccesFuly Confirmed Edart Many",
                                    'typAction' => 1,
                                    'data' => $datUpdt,
                                ], 200);
                            } else {
                                $datUpdt = EdaretMewevin::where('idbss', $idProfileBssNow)->select('id', 'numberMewve', 'nameMewve', 'typerelation', 'edartPaymentProdects', 'edartOreders', 'edartemaney', 'PaymentEcteronect', 'Ratibe', 'curent')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "Sorry Zeboune Has On Delete This Order Or This Order Not Found",
                                    'typAction' => 13,
                                    'data' => $datUpdt,
                                ], 200);
                            }
                        } else {
                            return response()->json([
                                'message' => "Sorry Zeboune Has On Delete This Order Or This Order Not Found",
                                'data' => 2,
                            ], 200);
                        }
                    } else {
                        $datUpdt = EdaretMewevin::where([
                            'idbss' => $idProfileBssNow,
                        ])->latest()->paginate(10);
                        return response()->json([
                            'message' => "Sorry Your Password Setting Is Not Correct",
                            'typAction' => 7,
                            'data' => $datUpdt,
                        ], 200);
                    }
                } else {
                    return response()->json([
                        'message' => "Sorry Your Are Not Have Password Setting Plz Created",
                        'typAction' => 8,
                        'data' => [],
                    ], 200);
                }
            } else if($SheckMyProfIDBssTv ) {
                return response()->json([
                        'message' => "Sorry You Dont Have Realayion Fro Edart Maleya",
                        'data' => 10,
                    ], 200);
            } else {
                return response()->json([
                    'message' => "Sorry Error  In Server Or Semthing Error Not Found",
                    'data' => 5,
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 99,
            ], 200);
        }
    } //== End To Active Slahiyet Edart Orders Bss ==//

    // Start To Dsc Slahiyet Edart Orders Bss
    function DscActiveEdartOrdersBssForMeweveToTrave(Request $request, $IdOrderMewev) {
        try {
            $SplIdOrderMewev = strip_tags($IdOrderMewev);
            $MyProfileNow = Auth::user();
            $idProfileBssNow = $MyProfileNow->curret_profile_id_Bss;
            $SheckMyProfIDBssTv = $MyProfileNow->current_my_travel;
            $ProfileBssLoginNow = $MyProfileNow->ProfileUserBss()->where('id', $idProfileBssNow)->select('id')->first();
            if($ProfileBssLoginNow) {
                $request->validate([
                    'passwordSetting' => 'required|string'
                ]);
                $passwordStingHa = strip_tags($request->passwordSetting);
                $shekpas = $MyProfileNow->UserPasswordStting()->where('idbss', $idProfileBssNow)->select('id', 'password')->first();
                $passwordStingHaahing = Hash::make($passwordStingHa);
                $passwordHash = $shekpas->password;
                $redPassword = request('passwordSetting');
                if($passwordHash) {
                    if(Hash::check($redPassword, $passwordHash)) {
                    $SheckMyMeweve = EdaretMewevin::where([
                            'idbss' => $idProfileBssNow,
                            'id' => $SplIdOrderMewev,
                        ])->select('id', 'confirmUser', 'typerelation', 'edartOreders')->first();
                        if($SheckMyMeweve) {
                            if($SheckMyMeweve->confirmUser == 0) {
                                $datUpdt = EdaretMewevin::where('idbss', $idProfileBssNow)->select('id', 'numberMewve', 'nameMewve', 'typerelation', 'edartPaymentProdects', 'edartOreders', 'edartemaney', 'PaymentEcteronect', 'Ratibe', 'curent')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "Plz Waite For User Send Relation Trave",
                                    'data' => $datUpdt,
                                    'typAction' => 3,
                                ], 200);
                            } else if($SheckMyMeweve->typerelation == 2) {
                                $datUpdt = EdaretMewevin::where('idbss', $idProfileBssNow)->select('id', 'numberMewve', 'nameMewve', 'typerelation', 'edartPaymentProdects', 'edartOreders', 'edartemaney', 'PaymentEcteronect', 'Ratibe', 'curent')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "Sorry User Dont Have Content Trave For You",
                                    'typAction' => 9,
                                    'data' => $datUpdt,
                                ], 200);
                            } else if($SheckMyMeweve->confirmUser == 2) {
                                $datUpdt = EdaretMewevin::where('idbss', $idProfileBssNow)->select('id', 'numberMewve', 'nameMewve', 'typerelation', 'edartPaymentProdects', 'edartOreders', 'edartemaney', 'PaymentEcteronect', 'Ratibe', 'curent')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "Sorry User Dont Have Content Trave For You",
                                    'typAction' => 6,
                                    'data' => $datUpdt,
                                ], 200);
                            } else if($SheckMyMeweve->edartOreders == 2) {
                                $datUpdt = EdaretMewevin::where('idbss', $idProfileBssNow)->select('id', 'numberMewve', 'nameMewve', 'typerelation', 'edartPaymentProdects', 'edartOreders', 'edartemaney', 'PaymentEcteronect', 'Ratibe', 'curent')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "Sorry Your Are On Realy Confirmed This Realtion",
                                    'typAction' => 12,
                                    'data' => $datUpdt,
                                ], 200);
                            } 
                            $UpdatNoww = $SheckMyMeweve->update([
                                'edartOreders' => 2,
                            ]);
                            if($UpdatNoww) {
                                $datUpdt = EdaretMewevin::where('idbss', $idProfileBssNow)->select('id', 'numberMewve', 'nameMewve', 'typerelation', 'edartPaymentProdects', 'edartOreders', 'edartemaney', 'PaymentEcteronect', 'Ratibe', 'curent')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "SuccesFuly Confirmed Edart Many",
                                    'typAction' => 1,
                                    'data' => $datUpdt,
                                ], 200);
                            } else {
                                $datUpdt = EdaretMewevin::where('idbss', $idProfileBssNow)->select('id', 'numberMewve', 'nameMewve', 'typerelation', 'edartPaymentProdects', 'edartOreders', 'edartemaney', 'PaymentEcteronect', 'Ratibe', 'curent')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "Sorry Zeboune Has On Delete This Order Or This Order Not Found",
                                    'typAction' => 13,
                                    'data' => $datUpdt,
                                ], 200);
                            }
                        } else {
                            return response()->json([
                                'message' => "Sorry Zeboune Has On Delete This Order Or This Order Not Found",
                                'data' => 2,
                            ], 200);
                        }
                    } else {
                        $datUpdt = EdaretMewevin::where([
                            'idbss' => $idProfileBssNow,
                        ])->latest()->paginate(10);
                        return response()->json([
                            'message' => "Sorry Your Password Setting Is Not Correct",
                            'typAction' => 7,
                            'data' => $datUpdt,
                        ], 200);
                    }
                } else {
                    return response()->json([
                        'message' => "Sorry Your Are Not Have Password Setting Plz Created",
                        'typAction' => 8,
                        'data' => [],
                    ], 200);
                }
            } else if($SheckMyProfIDBssTv ) {
                return response()->json([
                        'message' => "Sorry You Dont Have Realayion Fro Edart Maleya",
                        'data' => 10,
                    ], 200);
            } else {
                return response()->json([
                    'message' => "Sorry Error  In Server Or Semthing Error Not Found",
                    'data' => 5,
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 99,
            ], 200);
        }
    } //== End To Dsc Slahiyet Edart Orders Bss ==//

    // Start To Active Slahiyet Payment Electronect For Meweve
    function ActiveEdartPaymentEcteronectForMeweveToTrave(Request $request, $IdOrderMewev) {
        try {
            $SplIdOrderMewev = strip_tags($IdOrderMewev);
            $MyProfileNow = Auth::user();
            $idProfileBssNow = $MyProfileNow->curret_profile_id_Bss;
            $SheckMyProfIDBssTv = $MyProfileNow->current_my_travel;
            $ProfileBssLoginNow = $MyProfileNow->ProfileUserBss()->where('id', $idProfileBssNow)->select('id')->first();
            if($ProfileBssLoginNow) {
                $request->validate([
                    'passwordSetting' => 'required|string'
                ]);
                $passwordStingHa = strip_tags($request->passwordSetting);
                $shekpas = $MyProfileNow->UserPasswordStting()->where('idbss', $idProfileBssNow)->select('id', 'password')->first();
                $passwordStingHaahing = Hash::make($passwordStingHa);
                $passwordHash = $shekpas->password;
                $redPassword = request('passwordSetting');
                if($passwordHash) {
                    if(Hash::check($redPassword, $passwordHash)) {
                    $SheckMyMeweve = EdaretMewevin::where([
                            'idbss' => $idProfileBssNow,
                            'id' => $SplIdOrderMewev,
                        ])->select('id', 'confirmUser', 'typerelation', 'PaymentEcteronect')->first();
                        if($SheckMyMeweve) {
                            if($SheckMyMeweve->confirmUser == 0) {
                                $dataUpdt = EdaretMewevin::where('idbss', $idProfileBssNow)->select('id', 'numberMewve', 'nameMewve', 'typerelation', 'edartPaymentProdects', 'edartOreders', 'edartemaney', 'PaymentEcteronect', 'Ratibe', 'curent')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "Plz Waite For User Send Relation Trave",
                                    'typAction' => 3,
                                    'data' => $dataUpdt,
                                ], 200);
                            } else if($SheckMyMeweve->typerelation == 2) {
                                $dataUpdt = EdaretMewevin::where('idbss', $idProfileBssNow)->latest()->paginate(10);
                                return response()->json([
                                    'message' => "Sorry User Dont Have Content Trave For You",
                                    'typAction' => 9,
                                    'data' => $dataUpdt,
                                ], 200);
                            } else if($SheckMyMeweve->confirmUser == 2) {
                                $dataUpdt = EdaretMewevin::where('idbss', $idProfileBssNow)->select('id', 'numberMewve', 'nameMewve', 'typerelation', 'edartPaymentProdects', 'edartOreders', 'edartemaney', 'PaymentEcteronect', 'Ratibe', 'curent')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "Sorry User Dont Have Content Trave For You",
                                    'typAction' => 6,
                                    'data' => $dataUpdt,
                                ], 200);
                            } else if($SheckMyMeweve->PaymentEcteronect == 1) {
                                $dataUpdt = EdaretMewevin::where('idbss', $idProfileBssNow)->select('id', 'numberMewve', 'nameMewve', 'typerelation', 'edartPaymentProdects', 'edartOreders', 'edartemaney', 'PaymentEcteronect', 'Ratibe', 'curent')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "Sorry Your Are On Realy Confirmed This Realtion",
                                    'typAction' => 12,
                                    'data' => $dataUpdt,
                                ], 200);
                            } 

                            $UpdatNoww = $SheckMyMeweve->update([
                                'PaymentEcteronect' => 1,
                            ]);

                            if($UpdatNoww) {
                                $dataUpdt = EdaretMewevin::where('idbss', $idProfileBssNow)->select('id', 'numberMewve', 'nameMewve', 'typerelation', 'edartPaymentProdects', 'edartOreders', 'edartemaney', 'PaymentEcteronect', 'Ratibe', 'curent')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "SuccesFuly Confirmed Edart Many",
                                    'typAction' => 1,
                                    'data' => $dataUpdt,
                                ], 200);
                            } else {
                                $dataUpdt = EdaretMewevin::where('idbss', $idProfileBssNow)->select('id', 'numberMewve', 'nameMewve', 'typerelation', 'edartPaymentProdects', 'edartOreders', 'edartemaney', 'PaymentEcteronect', 'Ratibe', 'curent')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "Sorry Zeboune Has On Delete This Order Or This Order Not Found",
                                    'typAction' => 13,
                                    'data' => $dataUpdt,
                                ], 200);
                            }
                        } else {
                            $dataUpdt = EdaretMewevin::where('idbss', $idProfileBssNow)->select('id', 'numberMewve', 'nameMewve', 'typerelation', 'edartPaymentProdects', 'edartOreders', 'edartemaney', 'PaymentEcteronect', 'Ratibe', 'curent')->latest()->paginate(10);
                            return response()->json([
                                'message' => "Sorry Zeboune Has On Delete This Order Or This Order Not Found",
                                'typAction' => 2,
                                'data' => $dataUpdt,
                            ], 200);
                        }
                    } else {
                        return response()->json([
                            'message' => "Sorry Your Password Setting Is Not Correct",
                            'typAction' => 7,
                            'data' => [],
                        ], 200);
                    }
                } else {
                    return response()->json([
                        'message' => "Sorry Your Are Not Have Password Setting Plz Created",
                        'typAction' => 8,
                        'data' => [],
                    ], 200);
                }
            } else if($SheckMyProfIDBssTv ) {
                return response()->json([
                        'message' => "Sorry You Dont Have Realayion Fro Edart Maleya",
                        'data' => 10,
                    ], 200);
            } else {
                return response()->json([
                    'message' => "Sorry Error  In Server Or Semthing Error Not Found",
                    'data' => 5,
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 99,
            ], 200);
        }
    } //== End To Active Slahiyet Payment Electronect For Meweve ==//

    // Start To Stop Slahiyet Payment Electronect For Meweve
    function DscActiveEdartPaymentEcteronectForMeweveToTrave(Request $request, $IdOrderMewev) {
        try {
            $SplIdOrderMewev = strip_tags($IdOrderMewev);
            $MyProfileNow = Auth::user();
            $idProfileBssNow = $MyProfileNow->curret_profile_id_Bss;
            $SheckMyProfIDBssTv = $MyProfileNow->current_my_travel;
            $ProfileBssLoginNow = $MyProfileNow->ProfileUserBss()->where('id', $idProfileBssNow)->select('id')->select('id')->select('id')->first();
            if($ProfileBssLoginNow) {
                $request->validate([
                    'passwordSetting' => 'required|string'
                ]);
                $passwordStingHa = strip_tags($request->passwordSetting);
                $shekpas = $MyProfileNow->UserPasswordStting()->where('idbss', $idProfileBssNow)->select('id', 'password')->first();
                $passwordStingHaahing = Hash::make($passwordStingHa);
                $passwordHash = $shekpas->password;
                $redPassword = request('passwordSetting');
                if($passwordHash) {
                    if(Hash::check($redPassword, $passwordHash)) {
                    $SheckMyMeweve = EdaretMewevin::where([
                            'idbss' => $idProfileBssNow,
                            'id' => $SplIdOrderMewev,
                        ])->select('id', 'confirmUser', 'typerelation', 'PaymentEcteronect')->first();
                        if($SheckMyMeweve) {
                            if($SheckMyMeweve->confirmUser == 0) {
                                $datUpdt = EdaretMewevin::where('idbss', $idProfileBssNow)->select('id', 'numberMewve', 'nameMewve', 'typerelation', 'edartPaymentProdects', 'edartOreders', 'edartemaney', 'PaymentEcteronect', 'Ratibe', 'curent')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "Plz Waite For User Send Relation Trave",
                                    'typAction' => 3,
                                    'data' => $datUpdt,
                                ], 200);
                            } else if($SheckMyMeweve->typerelation == 2) {
                                $dataUpdt = EdaretMewevin::where('idbss', $idProfileBssNow)->select('id', 'numberMewve', 'nameMewve', 'typerelation', 'edartPaymentProdects', 'edartOreders', 'edartemaney', 'PaymentEcteronect', 'Ratibe', 'curent')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "Sorry User Dont Have Content Trave For You",
                                    'typAction' => 9,
                                    'data' => $dataUpdt,
                                ], 200);
                            } else if($SheckMyMeweve->confirmUser == 2) {
                                $dataUpdt = EdaretMewevin::where('idbss', $idProfileBssNow)->select('id', 'numberMewve', 'nameMewve', 'typerelation', 'edartPaymentProdects', 'edartOreders', 'edartemaney', 'PaymentEcteronect', 'Ratibe', 'curent')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "Sorry User Dont Have Content Trave For You",
                                    'typAction' => 6,
                                    'data' => $dataUpdt,
                                ], 200);
                            } else if($SheckMyMeweve->PaymentEcteronect == 2) {
                                $dataUpdt = EdaretMewevin::where('idbss', $idProfileBssNow)->select('id', 'numberMewve', 'nameMewve', 'typerelation', 'edartPaymentProdects', 'edartOreders', 'edartemaney', 'PaymentEcteronect', 'Ratibe', 'curent')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "Sorry Your Are On Realy Confirmed This Realtion",
                                    'typAction' => 12,
                                    'data' => $dataUpdt,
                                ], 200);
                            } 

                            $UpdatNoww = $SheckMyMeweve->update([
                                'PaymentEcteronect' => 2,
                            ]);

                            if($UpdatNoww) {
                                $dataUpdt = EdaretMewevin::where('idbss', $idProfileBssNow)->select('id', 'numberMewve', 'nameMewve', 'typerelation', 'edartPaymentProdects', 'edartOreders', 'edartemaney', 'PaymentEcteronect', 'Ratibe', 'curent')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "SuccesFuly Confirmed Edart Many",
                                    'typAction' => 1,
                                    'data' => $dataUpdt,
                                ], 200);
                            } else {
                                $dataUpdt = EdaretMewevin::where('idbss', $idProfileBssNow)->select('id', 'numberMewve', 'nameMewve', 'typerelation', 'edartPaymentProdects', 'edartOreders', 'edartemaney', 'PaymentEcteronect', 'Ratibe', 'curent')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "Sorry Zeboune Has On Delete This Order Or This Order Not Found",
                                    'data' => $dataUpdt,
                                    'typAction' => 13,
                                ], 200);
                            }
                        } else {
                            $dataUpdt = EdaretMewevin::where('idbss', $idProfileBssNow)->select('id', 'numberMewve', 'nameMewve', 'typerelation', 'edartPaymentProdects', 'edartOreders', 'edartemaney', 'PaymentEcteronect', 'Ratibe', 'curent')->latest()->paginate(10);
                            return response()->json([
                                'message' => "Sorry Zeboune Has On Delete This Order Or This Order Not Found",
                                'data' => $dataUpdt,
                                'typAction' => 2,
                            ], 200);
                        }
                    } else {
                        return response()->json([
                            'message' => "Sorry Your Password Setting Is Not Correct",
                            'typAction' => 7,
                            'data' => [],
                        ], 200);
                    }
                } else {
                    return response()->json([
                        'message' => "Sorry Your Are Not Have Password Setting Plz Created",
                        'typAction' => 8,
                        'data' => [],
                    ], 200);
                }
            } else if($SheckMyProfIDBssTv ) {
                return response()->json([
                        'message' => "Sorry You Dont Have Realayion Fro Edart Maleya",
                        'data' => 10,
                    ], 200);
            } else {
                return response()->json([
                    'message' => "Sorry Error  In Server Or Semthing Error Not Found",
                    'data' => 5,
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 99,
            ], 200);
        }
    } //== End To Stop Slahiyet Payment Electronect For Meweve ==//

    // Start To Update Ratibe Semthing Trave For Bss
    function StartUpdateRatibeMeweveForBss(Request $request, $IdOrderMewev) {
        try {
            $SplIdOrderMewev = strip_tags($IdOrderMewev);
            $MyProfileNow= Auth::user();
            $idProfileBssNow = $MyProfileNow->curret_profile_id_Bss;
            $SheckMyProfIDBssTv = $MyProfileNow->current_my_travel;
            $ProfileBssLoginNow = $MyProfileNow->ProfileUserBss()->where('id', $idProfileBssNow)->select('id', 'usernameBss', 'image')->first();
            if($ProfileBssLoginNow) {
                $request->validate([
                    'passwordSetting' => 'required|string',
                    'ratibeUodate' => 'required|string',
                ]);
                $passwordStingHa = strip_tags($request->passwordSetting);
                $ratibe = strip_tags($request->ratibeUodate);
                $shekpas = $MyProfileNow->UserPasswordStting()->where('idbss', $idProfileBssNow)->select('id', 'password')->first();
                $passwordStingHaahing = Hash::make($passwordStingHa);
                $passwordHash = $shekpas->password;
                $redPassword = request('passwordSetting');
                if($passwordHash) {
                    if(Hash::check($redPassword, $passwordHash)) {
                    $SheckMyMeweve = EdaretMewevin::where([
                            'idbss' => $idProfileBssNow,
                            'id' => $SplIdOrderMewev,
                        ])->select('id', 'user_id', 'confirmUser', 'typerelation', 'confirmBss', 'Ratibe')->first();
                        if($SheckMyMeweve) {
                            if($SheckMyMeweve->typerelation == 2 || $SheckMyMeweve->confirmUser == 2) {
                                $dataUpdt = EdaretMewevin::where('idbss', $idProfileBssNow)->select('id', 'numberMewve', 'nameMewve', 'typerelation', 'edartPaymentProdects', 'edartOreders', 'edartemaney', 'PaymentEcteronect', 'Ratibe', 'curent')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "Sorry User Dont Have Content Trave For You",
                                    'typAction' => 3,
                                    'data' => $dataUpdt,
                                ], 200);
                            } else if($SheckMyMeweve->typerelation == 3 || $SheckMyMeweve->confirmBss == 2) {
                                $dataUpdt = EdaretMewevin::where('idbss', $idProfileBssNow)->select('id', 'numberMewve', 'nameMewve', 'typerelation', 'edartPaymentProdects', 'edartOreders', 'edartemaney', 'PaymentEcteronect', 'Ratibe', 'curent')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "Sorry You Dont Have Content Teweve This User Bcs You Stop Demand",
                                    'typAction' => 9,
                                    'data' => $dataUpdt,
                                ], 200);
                            }
                            $dataToSendAndCreateMyMowve = [
                                'user_id' => $SheckMyMeweve->user_id,
                                'idbss' => $ProfileBssLoginNow->id,
                                'NameUserSendMessage' => $ProfileBssLoginNow->usernameBss,
                                'image' => $ProfileBssLoginNow->image,
                                'titel' => "  تنبيه لقد قرر التاجر $ProfileBssLoginNow->usernameBss تعديل راتبك",
                                'message' => "لقد قام تاجر $ProfileBssLoginNow->usernameBss بتعديل راتبك و لذي كان $SheckMyMeweve->Ratibe تم تغييره الى  $ratibe للمزيد تواصل مع تاجر",
                                'TypeAccountSendMessage' => 'bss',
                                'sheckMessage' => 'UpdateRatibe',
                                'TypeMessage' => "Confirmed",
                                'TypeRelationMessageUserSend' => 1,
                            ];
                            $ConfirmCreateMesaage = MessageEghar::create($dataToSendAndCreateMyMowve);
                            $UpdatNoww = $SheckMyMeweve->update([
                                'Ratibe' => $ratibe,
                            ]);
                            if($UpdatNoww && $ConfirmCreateMesaage) {
                            
                                $dataUpdt = EdaretMewevin::where('idbss', $idProfileBssNow)->select('id', 'numberMewve', 'nameMewve', 'typerelation', 'edartPaymentProdects', 'edartOreders', 'edartemaney', 'PaymentEcteronect', 'Ratibe', 'curent')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "SuccesFuly Update Ratibe Meweve",
                                    'typAction' => 1,
                                    'data' => $dataUpdt,
                                ], 200);
                            } else {
                                $dataUpdt = EdaretMewevin::where('idbss', $idProfileBssNow)->select('id', 'numberMewve', 'nameMewve', 'typerelation', 'edartPaymentProdects', 'edartOreders', 'edartemaney', 'PaymentEcteronect', 'Ratibe', 'curent')->latest()->paginate(10);
                                return response()->json([
                                    'message' => "Sorry Zeboune Has On Delete This Order Or This Order Not Found",
                                    'typAction' => 13,
                                    'data' => $dataUpdt,
                                ], 200);
                            }
                        } else {
                            $dataUpdt = EdaretMewevin::where('idbss', $idProfileBssNow)->select('id', 'numberMewve', 'nameMewve', 'typerelation', 'edartPaymentProdects', 'edartOreders', 'edartemaney', 'PaymentEcteronect', 'Ratibe', 'curent')->latest()->paginate(10);
                            return response()->json([
                                'message' => "Sorry Zeboune Has On Delete This Order Or This Order Not Found",
                                'typAction' => 2,
                                'data' => $dataUpdt,
                            ], 200);
                        }
                    } else {
                        return response()->json([
                            'message' => "Sorry Your Password Setting Is Not Correct",
                            'typAction' => 7,
                            'data' => [],
                        ], 200);
                    }
                } else {
                    return response()->json([
                        'message' => "Sorry Your Are Not Have Password Setting Plz Created",
                        'typAction' => 8,
                        'data' => [],
                    ], 200);
                }
            } else if($SheckMyProfIDBssTv ) {
                return response()->json([
                        'message' => "Sorry You Dont Have Realayion Fro Edart Maleya",
                        'data' => 10,
                    ], 200);
            } else {
                return response()->json([
                    'message' => "Sorry Error  In Server Or Semthing Error Not Found",
                    'data' => 5,
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 99,
            ], 200);
        }
    } //== End To Update Ratibe Semthing Trave For Bss ==//

    //== End To Get Ratibe Semthing Trave For Bss ==//
    function StartGetRatibeToMeweveForBss(Request $request, $IdOrderMewev) {
        try {
            $SplIdOrderMewev = strip_tags($IdOrderMewev);
            $MyProfileNow = Auth::user();
            $idProfileBssNow = $MyProfileNow->curret_profile_id_Bss;
            $SheckMyProfIDBssTv = $MyProfileNow->current_my_travel;
            $ProfileBssLoginNow = $MyProfileNow->ProfileUserBss()->where('id', $idProfileBssNow)->select('id', 'usernameBss', 'image')->first();
            if($ProfileBssLoginNow) {
                $request->validate([
                    'passwordSetting' => 'required|string',
                ]);
                $passwordStingHa = strip_tags($request->passwordSetting);
                $shekpas = $MyProfileNow->UserPasswordStting()->where('idbss', $idProfileBssNow)->select('id', 'password')->first();
                $passwordStingHaahing = Hash::make($passwordStingHa);
                $passwordHash = $shekpas->password;
                $categorName = strip_tags($request->category);
                $redPassword = request('passwordSetting');
                if($passwordHash) {
                    if(Hash::check($redPassword, $passwordHash)) {
                    $SheckMyMeweve = EdaretMewevin::where([
                            'idbss' => $idProfileBssNow,
                            'id' => $SplIdOrderMewev,
                        ])->select('id', 'user_id', 'confirmUser', 'typerelation', 'confirmBss', 'Ratibe')->first();

                        if($SheckMyMeweve) {
                            if($SheckMyMeweve->typerelation == 0 || $SheckMyMeweve->confirmUser == 0) {
                                return response()->json([
                                    'message' => "Plz Wailt Message User For Teweve",
                                    'typAction' => 17,
                                    'data' => [],
                                ], 200);
                            } else if($SheckMyMeweve->typerelation == 2 || $SheckMyMeweve->confirmUser == 2) {
                                $dataUpdt = EdaretMewevin::where('idbss', $idProfileBssNow)->latest()->paginate(10);
                                return response()->json([
                                    'message' => "Sorry User Dont Have Content Trave For You",
                                    'typAction' => 3,
                                    'data' => $dataUpdt,
                                ], 200);
                            } else if($SheckMyMeweve->typerelation == 3 || $SheckMyMeweve->confirmBss == 2) {
                                $dataUpdt = EdaretMewevin::where('idbss', $idProfileBssNow)->latest()->paginate(10);
                                return response()->json([
                                    'message' => "Sorry You Dont Have Content Teweve This User Bcs You Stop Demand",
                                    'typAction' => 9,
                                    'data' => $dataUpdt,
                                ], 200);
                            }

                            $dataToSendAndCreateMyMowve = [
                                'user_id' => $SheckMyMeweve->user_id,
                                'idbss' => $ProfileBssLoginNow->id,
                                'NameUserSendMessage' => $ProfileBssLoginNow->usernameBss,
                                'image' => $ProfileBssLoginNow->image,
                                'titel' => "  تاكيد الدفغ الراتبك الشهري من التاجر $ProfileBssLoginNow->usernameBss",
                                'message' => "قام تاحر $ProfileBssLoginNow->usernameBss بارسال تاكيد استلامك الراتب المقدر بي $SheckMyMeweve->Ratibe $SheckMyMeweve->curent بغد تاكيد اشنلام اموال سيبدا احتساب بدايت شهر جديد",
                                'TypeAccountSendMessage' => 'bss',
                                'sheckMessage' => 'Ratibe',
                                'TypeRelationMessageUserSend' => 1
                            ];

                            $ConfirmCreateMesaage = MessageEghar::create($dataToSendAndCreateMyMowve);

                            $UpdatNoww = $SheckMyMeweve->update([
                                'typRatibe' => 2,
                            ]);
                            if($UpdatNoww || $ConfirmCreateMesaage) {
                                $dataUpdt = EdaretMewevin::where('idbss', $idProfileBssNow)->latest()->paginate(10);
                                return response()->json([
                                    'message' => "SuccesFuly Send Message For User To Confirmed Get Ratibe Meweve",
                                    'typAction' => 1,
                                    'data' => $dataUpdt,
                                ], 200);
                            } else {
                                $dataUpdt = EdaretMewevin::where('idbss', $idProfileBssNow)->latest()->paginate(10);
                                return response()->json([
                                    'message' => "Sorry Zeboune Has On Delete This Order Or This Order Not Found",
                                    'typAction' => 13,
                                    'data' => $dataUpdt,
                                ], 200);
                            }
                        } else {
                            $dataUpdt = EdaretMewevin::where('idbss', $idProfileBssNow)->latest()->paginate(10);
                            return response()->json([
                                'message' => "Sorry Zeboune Has On Delete This Order Or This Order Not Found",
                                'typAction' => 2,
                                'data' => $dataUpdt,
                            ], 200);
                        }
                    } else {
                        return response()->json([
                            'message' => "Sorry Your Password Setting Is Not Correct",
                            'typAction' => 7,
                            'data' => [],
                        ], 200);
                    }
                } else {
                    return response()->json([
                        'message' => "Sorry Your Are Not Have Password Setting Plz Created",
                        'typAction' => 8,
                        'data' => [],
                    ], 200);
                }
            } else if($SheckMyProfIDBssTv ) {
                return response()->json([
                        'message' => "Sorry You Dont Have Realayion Fro Edart Maleya",
                        'data' => 10,
                    ], 200);
            } else {
                return response()->json([
                    'message' => "Sorry Error  In Server Or Semthing Error Not Found",
                    'data' => 5,
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 99,
            ], 200);
        }
    } //== End To Get Ratibe Semthing Trave For Bss ==//

    // Start Show My Payment Prodect For Id
    function ShowMyDataMeweveTraveForBss($IdMeweve) {
        try {
            $datProfile = Auth::user();
            $SheckMyProfIDBss = $datProfile->curret_profile_id_Bss;
            $MyProfileBss = $datProfile->ProfileUserBss()->where('id', $SheckMyProfIDBss)->select('id')->first();
            if($MyProfileBss) {
                $SpmlIdZ = strip_tags($IdMeweve);
                $DataShow = EdaretMewevin::where([
                    'id' => $SpmlIdZ,
                    'idbss' => $SheckMyProfIDBss,
                ])->select('id', 'user_id', 'curent', 'totalorders', 'totaledartmaney', 'totaledartPayEct', 'totalMenths', 'Ratibe', 'nameMewve', 'numberMewve', 'typRatibe', 'typerelation', 'PaymentEcteronect', 'edartPaymentProdects', 'edartOreders', 'edartemaney', 'edartemaney', 'created_at')->first();
                $ProfileUserNow = ProfileUser::where([
                    'user_id' => $DataShow->user_id,
                ])->select('image')->first();
                $datFirt = [
                    "id" => $DataShow->id,
                    "totaledartPayProds" => $DataShow->totaledartPayProds,
                    "totalorders" => $DataShow->totalorders,
                    "totaledartmaney" => $DataShow->totaledartmaney,
                    "totaledartPayEct" => $DataShow->totaledartPayEct,
                    "curent" => $DataShow->curent,
                    "totalMenths" => $DataShow->totalMenths,
                    "Ratibe" => $DataShow->Ratibe,
                    "nameMewve" => $DataShow->nameMewve,
                    "numberMewve" => $DataShow->numberMewve,
                    "img" => $ProfileUserNow->image,
                    "typRatibe" => $DataShow->typRatibe,
                    "typerelation" => $DataShow->typerelation,
                    "PaymentEcteronect" => $DataShow->PaymentEcteronect,
                    "edartPaymentProdects" => $DataShow->edartPaymentProdects,
                    "edartOreders" => $DataShow->edartOreders,
                    "edartemaney" => $DataShow->edartemaney,
                    "created_at" => $DataShow->created_at,
                ];
                $eseleRewateb = EdartPaymentRwatibeMeweves::where([
                    "user_id" => $DataShow->user_id,
                    "idbss" => $SheckMyProfIDBss,
                ])->select('id', 'Ratibe', 'curent', 'MentheNow', 'created_at')->latest()->get();
                $datRatibe= [];
                foreach($eseleRewateb as $dat) {
                    $datas = ProfileUser::where([
                        'user_id' => $DataShow->user_id,
                    ])->select('id', 'NumberPhone')->first();
                    
                    $datRatibe[] = [
                        "id" => $dat->id,
                        "Ratibe" => $dat->Ratibe,
                        "NumberPhone" => $datas->NumberPhone,
                        "curent" => $dat->curent,
                        "MentheNow" => $dat->MentheNow,
                        "created_at" => $dat->created_at,
                    ];
                }

                return response()->json([
                    'message' => 'Show My Payment Pethod Data',
                    'typAction' => 1,
                    'data' => $datFirt,
                    'eseleRewateb' => $datRatibe,
                ], 200);

            } else {
                return response()->json([
                    'message' => 'Error Semthing Not Found',
                    'data' => 3,
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 99,
            ], 200);
        }
    } // End Show My Payment Prodect For Id
    //== End Alls Actions For Edart Meweves Bss ==//

    // Start Alls Actions For Edart Maney Bss
    // Start Show Alls Data Bss From Edart Maney
    function ShowAllsDataEdartMane() {
        try {
            $ProfileData = Auth::user();
            $SheckMyProfIDBss = $ProfileData->curret_profile_id_Bss;
            $SheckMyProfIDBssTv = $ProfileData->current_my_travel;
            $SmpleIdBssLoginNow = $SheckMyProfIDBss ? $SheckMyProfIDBss : $SheckMyProfIDBssTv;
            $MyProfileBss = $ProfileData->ProfileUserBss()->where('id', $SheckMyProfIDBss)->select('id')->first();
            $MyProfileTrave = $ProfileData->EdaretMewevin()->where([
                'confirmUser' => 1,
                'confirmBss' => 1,
                'typerelation' => 1,
                'idbss' => $SheckMyProfIDBssTv,
            ])
            ->select('id')
            ->latest()->first();
            if($MyProfileBss || $MyProfileTrave) {
                $totalformothe = DB::table('profile_user_bsses')->where('id', $SmpleIdBssLoginNow)->select('CountEdartManye')->first();
                $dataEdartMany = EdartManey::where('idbss', $SmpleIdBssLoginNow)->select('id', 'NumberMeshol', 'totalEdichal', 'totalEstehlakat', 'TotalErbahe', 'descripctionforday', 'currentPay', 'created_at')->latest()->paginate(10);
                return response()->json([
                    'message' => " From Edart Mane teweve",
                    'data' => $dataEdartMany,
                    'totalformothe' => $totalformothe->CountEdartManye,
                ], 200);
            } else {
                return response()->json([
                    'message' => "Sorry Semthing Error",
                    'data' => 55,
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 99,
            ], 200);
        }
    } //== End Show Alls Data Bss From Edart Maney ==//

    // Start Create Now Edart Maney For bss
    function CreateOneEdartManeForDay(Request $request) {
        try {
            $MyData = Auth::user();
            $SheckMyProfIDBss = $MyData->curret_profile_id_Bss;
            $SheckMyProfIDBssTv = $MyData->current_my_travel;
            $SmpleIdBssLoginNow = $SheckMyProfIDBss ? $SheckMyProfIDBss : $SheckMyProfIDBssTv;
            $MyProfileBss = $MyData->ProfileUserBss()->where('id', $SmpleIdBssLoginNow)->select('id')->first();
            $MyProfileTrave = $MyData->EdaretMewevin()->where([
                'confirmUser' => 1,
                'confirmBss' => 1,
                'typerelation' => 1,
                'idbss' => $SheckMyProfIDBssTv,
            ])
            ->join('profile_user_bsses', 'edaret_mewevins.idbss', 'profile_user_bsses.id')
            ->select('profile_user_bsses.*')
            ->latest()->first(); //idTweve
            if($MyProfileBss || $MyProfileTrave) {
                $request->validate([
                    'totalePaye' => 'required|integer',
                    'totaleIpay' => 'required|integer',
                    'dscripctionday' => 'nullable|string',
                ]);
            }
            $MyProfilBss = ProfileUserBss::where('id', $SmpleIdBssLoginNow)->select('id', 'user_id', 'CountEdartManye')->first();
            $MyCurrentPayCant = CurrentPaymentForUseBss::where('usernameBss', $SmpleIdBssLoginNow)->select('id', 'currentCantry')->first();
            if($MyProfileBss) {
                if($MyProfilBss->CountEdartManye == 33 || $MyProfilBss->CountEdartManye > 33) {
                    $totalformothe = DB::table('profile_user_bsses')->where('id', $SmpleIdBssLoginNow)->select('CountEdartManye')->first();
                    $dataUpdat = EdartManey::where('idbss', $SheckMyProfIDBss)->select('id', 'NumberMeshol', 'totalEdichal', 'totalEstehlakat', 'TotalErbahe', 'descripctionforday', 'currentPay', 'created_at')->latest()->paginate(10);
                    return response()->json([
                        'message' => " Sorry Tou Are On Create ToTal Fo Manth",
                        'typAction' => 6,
                        'data' => $dataUpdat,
                        'totalformothe' => $totalformothe->CountEdartManye,
                    ], 200);
                }
                $smpltotalePaye = strip_tags($request->totalePaye);
                $smpltotaleIpay = strip_tags($request->totaleIpay);
                $smpldscripctionday = strip_tags($request->dscripctionday);

                $TotalErbahe = $smpltotalePaye - $smpltotaleIpay;
                $DataToCreate = [
                    'user_id' => $MyData->id,
                    'idbss' => $SheckMyProfIDBss,
                    'idTweve' => null,
                    'totalEdichal' => $smpltotalePaye,
                    'totalEstehlakat' => $smpltotaleIpay,
                    'TotalErbahe' => $TotalErbahe,
                    'NumberMeshol' => 1,
                    'currentPay' => $MyCurrentPayCant->currentCantry,
                    'descripctionforday' => $smpldscripctionday,
                ];
                $CreateEdartMany = EdartManey::create($DataToCreate);
                if($CreateEdartMany) {
                    $MyProfilBss->increment('CountEdartManye', 1);
                    $dataUpdat = EdartManey::where('idbss', $SheckMyProfIDBss)->select('id', 'NumberMeshol', 'totalEdichal', 'totalEstehlakat', 'TotalErbahe', 'descripctionforday', 'currentPay', 'created_at')->latest()->paginate(10);
                    $totalformothe = DB::table('profile_user_bsses')->where('id', $SmpleIdBssLoginNow)->select('CountEdartManye')->first();
                    return response()->json([
                        'message' => "From Create Edart Manye",
                        'data' => $dataUpdat,
                        'typAction' => 1,
                        'totalformothe' => $totalformothe->CountEdartManye,
                    ], 200);
                } else {
                    $totalformothe = DB::table('profile_user_bsses')->where('id', $SmpleIdBssLoginNow)->select('CountEdartManye')->first();
                    $dataUpdat = EdartManey::where('idbss', $SheckMyProfIDBss)->select('id', 'NumberMeshol', 'totalEdichal', 'totalEstehlakat', 'TotalErbahe', 'descripctionforday', 'currentPay', 'created_at')->latest()->paginate(10);
                    return response()->json([
                        'message' => "From Create Edart Manye",
                        'typAction' => 3,
                        'data' => $dataUpdat,
                        'totalformothe' => $totalformothe->CountEdartManye,
                    ], 200);
                }
                
            } else if($MyProfileTrave) {
                if($MyProfilBss->CountEdartManye == 33 || $MyProfilBss->CountEdartManye > 33) {
                $totalformothe = DB::table('profile_user_bsses')->where('id', $SmpleIdBssLoginNow)->select('CountEdartManye')->first();
                $dataUpdat = EdartManey::where('idbss', $SheckMyProfIDBssTv)->select('id', 'NumberMeshol', 'totalEdichal', 'totalEstehlakat', 'TotalErbahe', 'descripctionforday', 'currentPay', 'created_at')->latest()->paginate(10);
                    return response()->json([
                        'message' => " Sorry Tou Are On Create ToTal Fo Manth",
                        'typAction' => 6,
                        'data' => $dataUpdat,
                        'totalformothe' => $totalformothe->CountEdartManye,
                    ], 200);
                }
                $MyProfileTraveSmple = $MyData->EdaretMewevin()->where([
                    'confirmUser' => 1,
                    'confirmBss' => 1,
                    'typerelation' => 1,
                    'idbss' => $SheckMyProfIDBssTv,
                ])->select('id', 'edartemaney', 'totaledartmaney', 'numberMewve')->first();
                if($MyProfileTraveSmple->edartemaney != 1) {
                    $totalformothe = DB::table('profile_user_bsses')->where('id', $SmpleIdBssLoginNow)->select('CountEdartManye')->first();
                    $dataUpdat = EdartManey::where('idbss', $SheckMyProfIDBssTv)->select('id', 'NumberMeshol', 'totalEdichal', 'totalEstehlakat', 'TotalErbahe', 'descripctionforday', 'currentPay', 'created_at')->latest()->paginate(10);
                    return response()->json([
                        'message' => " Sorry Tou Are On Create ToTal Fo Manth",
                        'typAction' => 9,
                        'data' => $dataUpdat,
                        'totalformothe' => $totalformothe->CountEdartManye,
                    ], 200);
                }
                $smpltotalePaye = strip_tags($request->totalePaye);
                $smpltotaleIpay = strip_tags($request->totaleIpay);
                $smpldscripctionday = strip_tags($request->dscripctionday);
                $TotalErbahe = $smpltotalePaye - $smpltotaleIpay;
                $DataToCreate = [
                    'user_id' => $MyProfilBss->user_id,
                    'idbss' => $MyProfilBss->id,
                    'idTweve' => $MyProfileTraveSmple->id,
                    'totalEdichal' => $smpltotalePaye,
                    'totalEstehlakat' => $smpltotaleIpay,
                    'TotalErbahe' => $TotalErbahe,
                    'NumberMeshol' => $MyProfileTraveSmple->numberMewve,
                    'currentPay' => $MyCurrentPayCant->currentCantry,
                    'descripctionforday' => $smpldscripctionday,
                ];
                $CreateEdartMany = EdartManey::create($DataToCreate);
                if($CreateEdartMany) {
                    $MyProfilBss->increment('CountEdartManye', 1);
                    $MyProfileTraveSmple->increment('totaledartmaney', 1);
                    $totalformothe = DB::table('profile_user_bsses')->where('id', $SmpleIdBssLoginNow)->select('CountEdartManye')->first();
                    $dataUpdat = EdartManey::where('idbss', $SheckMyProfIDBssTv)->select('id', 'NumberMeshol', 'totalEdichal', 'totalEstehlakat', 'TotalErbahe', 'descripctionforday', 'currentPay', 'created_at')->latest()->paginate(10);
                    return response()->json([
                        'message' => "From Create Edart Manye",
                        'typAction' => 1,
                        'data' => $dataUpdat,
                        'totalformothe' => $totalformothe->CountEdartManye,
                    ], 200);
                } else {
                    $totalformothe = DB::table('profile_user_bsses')->where('id', $SmpleIdBssLoginNow)->select('CountEdartManye')->first();
                    $dataUpdat = EdartManey::where('idbss', $SheckMyProfIDBssTv)->select('id', 'NumberMeshol', 'totalEdichal', 'totalEstehlakat', 'TotalErbahe', 'descripctionforday', 'currentPay', 'created_at')->latest()->paginate(10);
                    return response()->json([
                        'message' => "From Create Edart Manye",
                        'typAction' => 3,
                        'data' => $dataUpdat,
                        'totalformothe' => $totalformothe->CountEdartManye,
                    ], 200);
                }
            } else {
                return response()->json([
                    'message' => "From Create Edart Manye",
                    'data' => 11,
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 99,
            ], 200);
        }
    } //== End Create Now Edart Maney For bss ==//
    
    // Start Update Menthing For Edart Maney bss
    function UpdateOneEdartMane(Request $request, $IdOneEdartMany) {
        try {
            $MyData = Auth::user();
            $SheckMyProfIDBss = $MyData->curret_profile_id_Bss;
            $SheckMyProfIDBssTv = $MyData->current_my_travel;
            $SmpleIdBssLoginNow = $SheckMyProfIDBss || $SheckMyProfIDBssTv;
            $MyProfileBss = $MyData->ProfileUserBss()->where('id', $SheckMyProfIDBss)->select('id')->first();
            $MyProfileTrave = $MyData->EdaretMewevin()->where([
                'confirmUser' => 1,
                'confirmBss' => 1,
                'typerelation' => 1,
                'idbss' => $SheckMyProfIDBssTv,
            ])
            ->join('profile_user_bsses', 'edaret_mewevins.idbss', 'profile_user_bsses.id')
            ->select('profile_user_bsses.*')
            ->latest()->first();
            if($MyProfileBss) {
                $request->validate([
                    'totalePaye' => 'nullable|integer',
                    'totaleIpay' => 'nullable|integer',
                    'dscripctionday' => 'nullable|string',
                ]);
                $DataToCreate = [];
                $SplPay = strip_tags($request->totaleIpay);
                $SplPayEdat = strip_tags($request->totalePaye);
                $spmaldescription = strip_tags($request->dscripctionday);
                $ThisEdartMany = EdartManey::where([
                    'id' => $IdOneEdartMany,
                    'idbss' => $SheckMyProfIDBss,
                ])->select('id', 'totalEdichal', 'TotalErbahe', 'totalEstehlakat', 'descripctionforday')->first();
                if($SplPayEdat != '') {
                    $DataToCreate['totalEdichal'] = $SplPayEdat;
                    $DataToCreate['TotalErbahe'] = $SplPayEdat - $ThisEdartMany->totalEstehlakat;
                }
                if($SplPay != '') {
                    $DataToCreate['totalEstehlakat'] = $SplPay;
                    $DataToCreate['TotalErbahe'] = $ThisEdartMany->totalEdichal - $SplPay;
                }
                if($spmaldescription != '') {
                    $DataToCreate['descripctionforday'] = $spmaldescription;
                    $DataToCreate['TotalErbahe'] = $ThisEdartMany->totalEdichal - $SplPay;
                }
                if($SplPay != '' && $SplPayEdat != '') {
                    $DataToCreate['totalEstehlakat'] = $SplPay;
                    $DataToCreate['totalEdichal'] = $SplPayEdat;
                    $DataToCreate['TotalErbahe'] = $SplPayEdat - $SplPay;
                } else if($SplPay != '' && $SplPayEdat != '' && $spmaldescription != '') {
                    $DataToCreate['totalEstehlakat'] = $SplPay;
                    $DataToCreate['totalEdichal'] = $SplPayEdat;
                    $DataToCreate['TotalErbahe'] = $SplPayEdat - $SplPay;
                    $DataToCreate['descripctionforday'] = $spmaldescription;
                }
                $UpdateEdartMany = $ThisEdartMany->update($DataToCreate);
                if($UpdateEdartMany) {
                    $totalformothe = DB::table('profile_user_bsses')->where('id', $SmpleIdBssLoginNow)->select('CountEdartManye')->first();
                    $DataUpdt = EdartManey::where('idbss', $SheckMyProfIDBss)->select('id', 'NumberMeshol', 'totalEdichal', 'totalEstehlakat', 'TotalErbahe', 'descripctionforday', 'currentPay', 'created_at')->latest()->paginate(10);
                    return response()->json([
                        'message' => "SuccessFuly Update This Edart Many",
                        'typAction' => 1,
                        'data' => $DataUpdt,
                        'totalformothe' => $totalformothe->CountEdartManye,
                    ], 200);
                } else {
                    $DataUpdt = EdartManey::where('idbss', $SheckMyProfIDBss)->select('id', 'NumberMeshol', 'totalEdichal', 'totalEstehlakat', 'TotalErbahe', 'descripctionforday', 'currentPay', 'created_at')->latest()->paginate(10);
                    $totalformothe = DB::table('profile_user_bsses')->where('id', $SmpleIdBssLoginNow)->select('CountEdartManye')->first();
                    return response()->json([
                        'message' => "From Create Edart Manye",
                        "typAction" => 3,
                        'data' => $DataUpdt,
                        'totalformothe' => $totalformothe->CountEdartManye,
                    ], 200);
                }
            } else if($MyProfileTrave) {
                $DataUpdt = EdartManey::where('idbss', $SheckMyProfIDBssTv)->select('id', 'NumberMeshol', 'totalEdichal', 'totalEstehlakat', 'TotalErbahe', 'descripctionforday', 'currentPay', 'created_at')->latest()->paginate(10);
                $totalformothe = DB::table('profile_user_bsses')->where('id', $SmpleIdBssLoginNow)->select('CountEdartManye')->first();
                return response()->json([
                    'message' => " Sorry Your Dot Have Releaction  To Update",
                    'typAction' => 9,
                    'data' => $DataUpdt,
                    'totalformothe' => $totalformothe->CountEdartManye,
                ], 200);
            } else {
                return response()->json([
                    'message' => "From Create Edart Manye",
                    'data' => 12,
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 99,
            ], 200);
        }
    } //== End Update Menthing For Edart Maney bss ==//
    // End Alls Actions For Edart Maney Bss ==//

    // Start function logout //
    public function logoutUser(Request $request) {
        try {
            if(Auth::check()) {
                $typeLogout = $request->user()->currentAccessToken()->delete();
                $cookie = Cookie::forget('user_token');    
                return response()->json([
                    'message' => 'seccess Action Logout',
                    'data' => 1
                ]);
            } else {
                return response()->json([
                'message' => 'Sorey Semthing Are In Error To Do Logout',
                'data' => 0
            ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sorry Not Can Send Message For Email And Is Found',
                'data' => [],
                'error' => $e,
                'typAction' => 99,
            ], 200);
        }
    }//=== Start function logout ===//

}
