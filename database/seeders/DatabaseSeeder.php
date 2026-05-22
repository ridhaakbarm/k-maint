<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Ticket;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // Create users
        $admin = User::create([
            'username' => 'admin',
            'name' => 'Administrator',
            'email' => 'admin@etiket.com',
            'password' => Hash::make('password'),
            'department' => 'IT',
            'role' => 'admin',
        ]);

        $ga = User::create([
            'username' => 'ga',
            'name' => 'General Affair',
            'email' => 'ga@etiket.com',
            'password' => Hash::make('password'),
            'department' => 'GA',
            'role' => 'ga',
        ]);

        $user1 = User::create([
            'username' => 'user1',
            'name' => 'Regular User 1',
            'email' => 'user1@etiket.com',
            'password' => Hash::make('password'),
            'department' => 'HR',
            'role' => 'user',
        ]);

        $user2 = User::create([
            'username' => 'user2',
            'name' => 'Regular User 2',
            'email' => 'user2@etiket.com',
            'password' => Hash::make('password'),
            'department' => 'Finance',
            'role' => 'user',
        ]);

        // Create sample tickets
        Ticket::create([
            'ticket_no' => Ticket::generateTicketNo(),
            'request_date' => now(),
            'requester_id' => $user1->id,
            'subject' => 'Permintaan Perbaikan AC',
            'description' => 'AC di ruangan HR tidak berfungsi dengan baik, perlu perbaikan segera.',
            'status' => 'open',
        ]);

        Ticket::create([
            'ticket_no' => Ticket::generateTicketNo(),
            'request_date' => now()->subDays(1),
            'requester_id' => $user2->id,
            'subject' => 'Penggantian Lampu',
            'description' => 'Lampu di area pantry sudah mati, mohon diganti.',
            'status' => 'onprogress',
            'assigned_to' => 'Teknisi GA',
        ]);

        Ticket::create([
            'ticket_no' => Ticket::generateTicketNo(),
            'request_date' => now()->subDays(2),
            'requester_id' => $user1->id,
            'subject' => 'Request to Close Sample',
            'description' => 'Ini adalah sample ticket untuk testing approve/reject.',
            'status' => 'request_to_close',
            'assigned_to' => 'Teknisi GA',
            'ga_notes' => 'Pekerjaan sudah selesai sesuai permintaan.',
        ]);

        Ticket::create([
            'ticket_no' => Ticket::generateTicketNo(),
            'request_date' => now()->subDays(3),
            'requester_id' => $user2->id,
            'subject' => 'Pembersihan Ruangan',
            'description' => 'Ruangan meeting perlu dibersihkan untuk acara besok.',
            'status' => 'closed',
            'assigned_to' => 'Cleaning Service',
        ]);
    }
}