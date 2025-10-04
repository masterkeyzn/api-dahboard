<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ApiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('api_games')->insert([
            'agent_code' => null,
            'agent_token' => null,
            'api_url' => 'https://api.nexusggr.com',
        ]);
    }
}
