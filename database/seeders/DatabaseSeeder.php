<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call(PackageSeeder::class);

        User::updateOrCreate(
            ['employee_id' => 'SYS-ADMIN1'],
            [
                'name' => 'System Admin',
                'email' => 'system@smartattend.co.tz',
                'phone' => '+255 700 000 000',
                'password' => 'Password@123',
                'role' => 'superadmin',
                'active' => true,
            ]
        );

        $org = Organization::firstOrCreate(
            ['name' => 'Nakuru Medical Centre'],
            [
                'contact_email' => 'admin@nakurumedical.co.tz',
                'contact_phone' => '+255 712 000 000',
                'address' => 'Mikocheni, Dar es Salaam',
                'website' => 'https://nakurumedical.co.tz',
                'tin' => '123-456-789',
                'employee_id_prefix' => 'NMC',
                'plan' => 'business',
                'status' => 'active',
            ]
        );

        if (! $org->trial_ends_at) {
            $org->startTrial(30);
        }

        $branch = Branch::firstOrCreate(
            ['org_id' => $org->id, 'name' => 'Main Office'],
            [
                'lat' => -6.7924,
                'lng' => 39.2083,
                'radius_meters' => 150,
            ]
        );

        User::updateOrCreate(
            ['employee_id' => 'SA-10024'],
            [
                'name' => 'Jane Doe',
                'email' => 'admin@nakurumedical.co.tz',
                'phone' => '+255 712 000 000',
                'password' => 'Password@123',
                'role' => 'admin',
                'org_id' => $org->id,
                'branch_id' => $branch->id,
                'active' => true,
            ]
        );

        User::updateOrCreate(
            ['employee_id' => 'EMP-20011'],
            [
                'name' => 'John Otieno',
                'email' => 'john.otieno@nakurumedical.co.tz',
                'phone' => '+255 713 111 222',
                'password' => 'Password@123',
                'role' => 'employee',
                'org_id' => $org->id,
                'branch_id' => $branch->id,
                'active' => true,
            ]
        );
    }
}
