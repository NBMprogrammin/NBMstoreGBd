<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\ProfileUserBss;
use App\Models\ProfileUser;
use App\Models\CurrentPaymentForUseBss;
use Illuminate\Support\Facades\Hash;
use Faker\Factory;

class UsersWithCommercialAccountsSeeder extends Seeder
{
    public function run()
    {
        $faker = Factory::create('ar_SA');
        
        $this->command->info('🚀 بدء إضافة الحسابات التجارية الجديدة...');
        
        // عداد للحسابات الجديدة
        $newAccounts = 0;
        
        for ($i = 1; $i <= 7; $i++) {
            $email = 'testnewacount' . $i . '@business.com';
            
            // تحقق إذا المستخدم موجود مسبقًا
            if (User::where('email', $email)->exists()) {
                $this->command->info("⏩ تخطي: البريد {$email} موجود مسبقًا");
                continue;
            }
            
            // 1. إنشاء المستخدم الأساسي (بدون user_type)
            $user = User::create([
                'username' => $faker->name(),
                'email' => $email,
                'password' => Hash::make('Password@123'),
                'NumberPhone' => '05' . rand(10000000, 99999999),
                'country_code' => '222',
            ]);

            // 2. إنشاء البروفايل الشخصي
            $profile = ProfileUser::create([
                'user_id' => $user->id,
                'name' => $user->username,
                'email' => $user->email,
                'city' => 'arafet nouk',
                'Gender' => rand('1', '2'),
                'NumberPhone' => $user->NumberPhone,
                'cantry' => 'Mouritan',
            ]);

            // 3. إنشاء الحساب التجاري
            $profileBss = ProfileUserBss::create([
                'user_id' => $user->id,
                'email' => $user->email,
                'usernameBss' => 'شركة ' . $faker->company(),
                'Numberphone' => $user->NumberPhone,
                'megaleBss' => $this->getRandomBusinessType(),
                'address' => $faker->address(),
                'country' => 'السعودية',
                'cantryBss' => 'موريتانيا',
                'discription' => $faker->realText(150),
                'gbsbss' =>  'kmkmk',
            ]);

            // 4. إنشاء عملة الدفع
            CurrentPaymentForUseBss::create([
                'user_id' => $user->id,
                'usernameBss' => $profileBss->id,
                'currentCantry' => $this->getRandomCurrency(),
            ]);
            
            $newAccounts++;
            $this->command->info("✅ تم إنشاء حساب تجاري جديد #{$i}: {$user->email}");
        }

        $this->command->info("🎉 تم إضافة {$newAccounts} حساب تجاري جديد");
    }

    private function getRandomBusinessType()
    {
        $types = ['تقنية المعلومات', 'التجارة الإلكترونية', 'المقاولات', 'الخدمات الاستشارية', 'التصنيع', 'التجزئة', 'الخدمات اللوجستية', 'السياحة والسفر', 'التعليم', 'الصحة'];
        return $types[array_rand($types)];
    }

    private function getRandomCurrency()
    {
        $currencies = [
            'SAR',
            'USD',
            'EUR',
            'MRU',
            'AED',
        ];
        $key = array_rand($currencies);
        return $currencies[$key] . " ($key)";
    }
}