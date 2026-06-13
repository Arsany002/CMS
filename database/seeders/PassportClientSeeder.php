<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Laravel\Passport\ClientRepository;

class PassportClientSeeder extends Seeder
{
    public function run(): void
    {
        $repository = app(ClientRepository::class);

        // USING THE NEW METHOD NAME FOR PASSPORT V12+
        $client = $repository->createPersonalAccessGrantClient(
            'Clinic API Personal Client',
            null
        );

        $this->command->info("Client ID: " . $client->id);
        $this->command->info("Client Secret: " . $client->plainSecret);
        $this->command->info("Copy these into your .env file!");
    }
}
