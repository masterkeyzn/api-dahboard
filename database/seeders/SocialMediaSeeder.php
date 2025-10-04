<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SocialMediaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $socialMedias = [
            [
                'id' => 1,
                'title' => 'Obrolan Langsung',
                'link' => null,
                'icon' => 'icon-comment',
                'description' => null,
            ],
            [
                'id' => 2,
                'title' => 'HOTLINE',
                'link' => null,
                'icon' => 'icon-phone',
                'description' => null,
            ],
            [
                'id' => 3,
                'title' => 'WHATSAPP',
                'link' => null,
                'icon' => 'icon-whatsapp',
                'description' => null,
            ],
            [
                'id' => 4,
                'title' => 'FACEBOOK',
                'link' => null,
                'icon' => 'icon-facebook',
                'description' => null,
            ],
            [
                'id' => 5,
                'title' => 'TWITTER',
                'link' => null,
                'icon' => 'icon-twitter',
                'description' => null,
            ],
            [
                'id' => 6,
                'title' => 'INSTAGRAM',
                'link' => null,
                'icon' => 'icon-instagram',
                'description' => null,
            ],
            [
                'id' => 7,
                'title' => 'GOOGLE',
                'link' => null,
                'icon' => 'icon-chrome',
                'description' => null,
            ],
            [
                'id' => 8,
                'title' => 'TELEGRAM',
                'link' => null,
                'icon' => 'icon-telegram',
                'description' => null,
            ],
        ];

        DB::table('social_media')->insert($socialMedias);
    }
}
