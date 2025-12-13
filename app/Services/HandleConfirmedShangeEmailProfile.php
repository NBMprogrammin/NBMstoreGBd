<?php
// app/Services/VerificationService.php

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;

class HandleConfirmedShangeEmailProfile
{
    public function sendVerificationCode($email, $code)
    {
        try {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new \Exception('صيغة البريد الإلكتروني غير صحيحة');
            }
            
            Mail::send([], [], function ($message) use ($email, $code) {
                if($code == 'bss') {
                    $message->to($email)
                            ->subject('تم تغيير البريد الاكتروني للحسابك تجاري بنجاح')
                            ->html("لبريج الاكتروني الجديد هو : <strong>{$email}</strong>");
                } else {
                       $message->to($email)
                            ->subject('تم تغيير البريد الاكتروني للحسابك شخصي بنجاح')
                            ->html("لبريج الاكتروني الجديد هو : <strong>{$email}</strong>");
                }
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