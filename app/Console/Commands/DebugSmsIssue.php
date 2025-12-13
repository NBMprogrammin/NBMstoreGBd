<?php
// app/Console/Commands/DebugSmsIssue.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class DebugSmsIssue extends Command
{
    protected $signature = 'sms:debug {phone} {country_code}';
    protected $description = 'تتبع مشكلة SMS بدقة';

    public function handle()
    {
        $phone = $this->argument('phone');
        $countryCode = $this->argument('country_code');
        $fullNumber = $countryCode . $phone;

        $this->info("🔍 بدء تتبع مشكلة SMS للرقم: {$fullNumber}");

        // 1. التحقق من البيانات الأساسية
        $this->checkBasicData($phone, $countryCode);

        // 2. اختبار Twilio مباشرة
        $this->testTwilioDirectly($fullNumber);

        // 3. التحقق من السجلات
        $this->checkLogs();

        // 4. اختبار بديل
        $this->testAlternativeMethods($fullNumber);
    }

    protected function checkBasicData($phone, $countryCode)
    {
        $this->info("\n1. 📋 التحقق من البيانات الأساسية:");
        
        $checks = [
            'رقم الهاتف' => $phone,
            'رمز الدولة' => $countryCode,
            'الرقم الكامل' => $countryCode . $phone,
            'TWILIO_SID' => substr(env('TWILIO_SID', ''), 0, 10) . '...',
            'TWILIO_FROM' => env('TWILIO_FROM', '')
        ];

        foreach ($checks as $label => $value) {
            $status = !empty($value) ? '✅' : '❌';
            $this->line("{$status} {$label}: {$value}");
        }

        // التحقق من صحة الرقم
        if (!preg_match('/^\+?[0-9]{8,15}$/', $countryCode . $phone)) {
            $this->error("❌ تنسيق الرقم غير صحيح");
        }
    }

    protected function testTwilioDirectly($fullNumber)
    {
        $this->info("\n2. 🌐 اختبار Twilio مباشرة:");

        $accountSid = env('TWILIO_SID');
        $authToken = env('TWILIO_TOKEN');
        $fromNumber = env('TWILIO_FROM');

        if (empty($accountSid) || empty($authToken)) {
            $this->error("❌ إعدادات Twilio غير مكتملة");
            return;
        }

        try {
            // اختبار بسيط للاتصال
            $url = "https://api.twilio.com/2010-04-01/Accounts/{$accountSid}.json";
            
            $response = Http::withBasicAuth($accountSid, $authToken)
                ->timeout(10)
                ->get($url);

            if ($response->successful()) {
                $this->info("✅ الاتصال بـ Twilio ناجح");
                $accountStatus = $response->json()['status'] ?? 'unknown';
                $this->info("📊 حالة الحساب: {$accountStatus}");
            } else {
                $this->error("❌ فشل الاتصال: " . $response->status());
                $this->line("تفاصيل الخطأ: " . $response->body());
            }

        } catch (\Exception $e) {
            $this->error("❌ استثناء: " . $e->getMessage());
        }
    }

    protected function checkLogs()
    {
        $this->info("\n3. 📝 التحقق من السجلات:");

        $logPath = storage_path('logs/laravel.log');
        if (!file_exists($logPath)) {
            $this->warn("⚠️ ملف السجلات غير موجود");
            return;
        }

        // البحث عن سجلات Twilio
        $twilioLogs = shell_exec("grep -i 'twilio\\|sms' {$logPath} | tail -5") ?: 'لا توجد سجلات حديثة';
        $this->line("آخر سجلات SMS/Twilio:");
        $this->line($twilioLogs);
    }

    protected function testAlternativeMethods($fullNumber)
    {
        $this->info("\n4. 🔄 اختبار طرق بديلة:");

        // اختبار cURL مباشرة
        $this->testCurlDirectly($fullNumber);

        // اختبار الاتصال بالإنترنت
        $this->testInternetConnection();
    }

    protected function testCurlDirectly($fullNumber)
    {
        $this->info("   - اختبار cURL مباشرة:");

        $testUrl = "https://webhook.site/unique-url"; // URL اختبار
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $testUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false
        ]);

        $result = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            $this->info("     ✅ cURL يعمل بشكل صحيح");
        } else {
            $this->error("     ❌ cURL فشل: {$error} (كود: {$httpCode})");
        }
    }

    protected function testInternetConnection()
    {
        $this->info("   - اختبار الاتصال بالإنترنت:");

        $hosts = [
            'api.twilio.com' => 'Twilio API',
            'google.com' => 'الاتصال العام'
        ];

        foreach ($hosts as $host => $label) {
            $connected = $this->pingHost($host);
            $status = $connected ? '✅' : '❌';
            $this->line("     {$status} {$label}: " . ($connected ? 'متصل' : 'غير متصل'));
        }
    }

    protected function pingHost($host)
    {
        try {
            $output = [];
            $result = null;
            exec("ping -c 1 -W 3 {$host} 2>&1", $output, $result);
            return $result === 0;
        } catch (\Exception $e) {
            return false;
        }
    }
}