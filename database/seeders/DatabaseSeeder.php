<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * The seed accounts of the shared preview interface (tasks/T0/contract.json).
     *
     * @var list<array{name: string, email: string, password: string}>
     */
    public const array USERS = [
        ['name' => 'Alice', 'email' => 'alice@example.test', 'password' => 'Correct-Horse-1'],
        ['name' => 'Bob', 'email' => 'bob@example.test', 'password' => 'Battery-Staple-2'],
    ];

    /**
     * Seed the application's database. Idempotent: runs on every container start.
     */
    public function run(): void
    {
        foreach (self::USERS as $user) {
            $model = User::query()->firstOrNew(['email' => $user['email']]);

            if (! $model->exists) {
                $model->forceFill([
                    'name' => $user['name'],
                    'password' => Hash::make($user['password']),
                    'email_verified_at' => now(),
                ])->save();
            }
        }
    }
}
