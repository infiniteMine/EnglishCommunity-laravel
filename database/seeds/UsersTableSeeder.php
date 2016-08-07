<?php

use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->insert([
            'nickname' => '管理员',
            'say' => '好好学习天天向上',
            'avatar' => 'uploads/user/avatar.jpg',
            'mobile' => '15626427299',
            'email' => 'admin@6ag.cn',
            'phone_binding' => 1,
            'email_binding' => 1,
            'sex' => 1,
            'is_admin' => 1,
        ]);
    }
}
