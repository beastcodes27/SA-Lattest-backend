<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Organization;
use App\Models\Package;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use \Illuminate\Database\Console\Seeds\WithoutModelEvents;

    public function run(): void
    {
        foreach ($this->packages() as $p) {
            Package::updateOrCreate(['code' => $p['code']], $p);
        }

        User::create([
            'name' => 'System Admin',
            'email' => 'system@smartattend.co.tz',
            'employee_id' => 'SYS-ADMIN1',
            'phone' => '+255 700 000 000',
            'password' => 'Password@123',
            'role' => 'superadmin',
            'active' => true,
        ]);

        $org = Organization::create([
            'name' => 'Nakuru Medical Centre',
            'contact_email' => 'admin@nakurumedical.co.tz',
            'contact_phone' => '+255 712 000 000',
            'address' => 'Mikocheni, Dar es Salaam',
            'website' => 'https://nakurumedical.co.tz',
            'tin' => '123-456-789',
            'employee_id_prefix' => 'NMC',
            'plan' => 'business',
            'status' => 'active',
        ]);

        $org->startTrial(30);

        $branch = Branch::create([
            'org_id' => $org->id,
            'name' => 'Main Office',
            'lat' => -6.7924,
            'lng' => 39.2083,
            'radius_meters' => 150,
        ]);

        User::create([
            'name' => 'Jane Doe',
            'email' => 'admin@nakurumedical.co.tz',
            'employee_id' => 'SA-10024',
            'phone' => '+255 712 000 000',
            'password' => 'Password@123',
            'role' => 'admin',
            'org_id' => $org->id,
            'branch_id' => $branch->id,
            'active' => true,
        ]);

        User::create([
            'name' => 'John Otieno',
            'email' => 'john.otieno@nakurumedical.co.tz',
            'employee_id' => 'EMP-20011',
            'phone' => '+255 713 111 222',
            'password' => 'Password@123',
            'role' => 'employee',
            'org_id' => $org->id,
            'branch_id' => $branch->id,
            'active' => true,
        ]);
    }

    private function packages(): array
    {
        return [
            [
                'code' => 'starter',
                'name' => 'Starter',
                'tagline' => 'For small teams getting started',
                'price_label' => null,
                'features' => ['1 active branch', 'Up to 50 employees', 'Check-ins & reports'],
                'employee_limit' => 50,
                'branch_limit' => 1,
                'active' => true,
                'position' => 1,
            ],
            [
                'code' => 'business',
                'name' => 'Business',
                'tagline' => 'For growing organizations',
                'price_label' => null,
                'features' => ['Up to 5 branches', 'Up to 500 employees', 'Attendance analytics', 'Priority support'],
                'employee_limit' => 500,
                'branch_limit' => 5,
                'active' => true,
                'position' => 2,
            ],
            [
                'code' => 'enterprise',
                'name' => 'Enterprise',
                'tagline' => 'For large institutions',
                'price_label' => null,
                'features' => ['Unlimited branches', 'Unlimited employees', 'SSO & API access', 'Dedicated manager'],
                'employee_limit' => null,
                'branch_limit' => null,
                'active' => true,
                'position' => 3,
            ],
        ];
    }
}
