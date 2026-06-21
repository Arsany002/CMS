<?php

namespace Tests\Feature;

use App\Models\Clinic;
use App\Models\User;
use Database\Seeders\LocalClinicSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Symfony\Component\Console\Command\Command;
use Tests\TestCase;

class LocalSetupCommandTest extends TestCase
{
    use DatabaseTransactions;

    public function test_local_setup_ensures_super_admin_and_active_demo_clinic(): void
    {
        Clinic::query()->update(['is_active' => false]);

        $this->artisan('cms:local-setup')
            ->assertExitCode(Command::SUCCESS);

        $this->assertDatabaseHas('users', [
            'email' => 'arsany.ayman02@gmail.com',
            'role' => 'super_admin',
            'is_active' => 1,
        ]);

        $this->assertDatabaseHas('clinics', [
            'name' => LocalClinicSeeder::NAME,
            'is_active' => 1,
        ]);
    }

    public function test_local_setup_is_idempotent_and_does_not_wipe_existing_data(): void
    {
        $existingClinic = Clinic::factory()->create(['name' => 'Existing Local Clinic']);
        $existingUser = User::factory()->doctor()->forClinic($existingClinic)->create([
            'email' => 'existing.local@clinic.test',
        ]);
        $initialDemoClinicCount = Clinic::where('name', LocalClinicSeeder::NAME)->count();
        $initialClinicCount = Clinic::count();
        $initialUserCount = User::count();
        $hadSeedAdmin = User::where('email', 'arsany.ayman02@gmail.com')->exists();

        $this->artisan('cms:local-setup')
            ->assertExitCode(Command::SUCCESS);
        $this->artisan('cms:local-setup')
            ->assertExitCode(Command::SUCCESS);

        $this->assertDatabaseHas('clinics', [
            'id' => $existingClinic->id,
            'name' => 'Existing Local Clinic',
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $existingUser->id,
            'email' => 'existing.local@clinic.test',
        ]);

        $this->assertSame($initialDemoClinicCount, Clinic::where('name', LocalClinicSeeder::NAME)->count());
        $this->assertSame($initialClinicCount, Clinic::count());
        $this->assertSame($initialUserCount + ($hadSeedAdmin ? 0 : 1), User::count());
    }

    public function test_local_setup_does_not_duplicate_super_admin_when_run_twice(): void
    {
        $this->artisan('cms:local-setup')
            ->assertExitCode(Command::SUCCESS);
        $this->artisan('cms:local-setup')
            ->assertExitCode(Command::SUCCESS);

        $this->assertSame(
            1,
            User::where('email', 'arsany.ayman02@gmail.com')->count()
        );
    }

    public function test_local_setup_does_not_overwrite_existing_super_admin(): void
    {
        $admin = User::updateOrCreate(['email' => 'arsany.ayman02@gmail.com'], [
            'email' => 'arsany.ayman02@gmail.com',
            'name' => 'Custom Local Admin',
            'password' => 'custom-password',
            'role' => 'super_admin',
            'is_active' => false,
            'clinic_id' => null,
        ]);

        $this->artisan('cms:local-setup')
            ->assertExitCode(Command::SUCCESS);

        $admin->refresh();

        $this->assertSame('Custom Local Admin', $admin->name);
        $this->assertFalse($admin->is_active);
    }

    public function test_local_setup_refuses_to_run_outside_local_or_testing(): void
    {
        $this->app['env'] = 'production';
        $initialDemoClinicCount = Clinic::where('name', LocalClinicSeeder::NAME)->count();

        $this->artisan('cms:local-setup')
            ->assertExitCode(Command::FAILURE);

        $this->assertSame($initialDemoClinicCount, Clinic::where('name', LocalClinicSeeder::NAME)->count());
    }
}
