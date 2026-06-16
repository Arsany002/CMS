<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;
use Laravel\Passport\ClientRepository;

class PassportClientSeeder extends Seeder
{
    public function run(): void
    {
        $existingClient = DB::table('oauth_clients')
            ->where('revoked', false)
            ->where('grant_types', 'like', '%personal_access%')
            ->first();

        if ($existingClient) {
            $this->command?->info("Passport personal access client already exists: {$existingClient->id}");

            return;
        }

        $repository = app(ClientRepository::class);

        $client = $repository->createPersonalAccessGrantClient(
            'CMS API',
            null
        );

        $this->command?->info("Created Passport personal access client: {$client->id}");
    }
}
