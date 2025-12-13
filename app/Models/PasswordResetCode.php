<?php
// app/Models/PasswordResetCode.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PasswordResetCode extends Model
{
    use HasFactory;

    protected $fillable = ['email', 'code', 'created_at'];

    public $timestamps = false;

    // إنشاء رمز جديد
    public static function generateCode($email)
    {
        // حذف أي رموز موجودة لهذا البريد
        self::where('email', $email)->delete();

        // إنشاء رمز عشوائي مكون من 6 أرقام
        $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

        // حفظ الرمز في قاعدة البيانات
        self::create([
            'email' => $email,
            'code' => $code,
            'created_at' => now()
        ]);

        return $code;
    }

    // التحقق من صحة الرمز (دون حذفه)
    public static function isValidCode($email, $code)
    {
        return self::where('email', $email)
                 ->where('code', $code)
                 ->where('created_at', '>=', now()->subMinutes(30))
                 ->exists();
    }

    // حذف الرمز بعد استخدامه
    public static function deleteCode($email, $code)
    {
        self::where('email', $email)
            ->where('code', $code)
            ->delete();
    }
}
