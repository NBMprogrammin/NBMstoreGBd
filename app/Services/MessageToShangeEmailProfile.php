<?php
// app/Services/VerificationService.php

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;

class MessageToShangeEmailProfile
{
    public function sendVerificationCode($email, $code)
    {
        try {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new \Exception('صيغة البريد الإلكتروني غير صحيحة');
            }
            
            Mail::send([], [], function ($message) use ($email, $code) {
                $message->to($email)
                        ->subject('كود تأكيد تغيير لبريد الاكتروني')
                        ->html("كود التأكيد: <strong>{$code}</strong>");
            });
            
            Cache::put("verification_code_{$email}", $code, 600);
            
            return [
                'success' => true,
                'message' => 'تم إرسال الكود بنجاح'
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}