<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use \Illuminate\Database\Console\Seeds\WithoutModelEvents;

    public function run(): void
    {
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
}
