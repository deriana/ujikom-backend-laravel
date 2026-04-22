<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\SatisfactionRating;
use App\Models\Ticket;
use App\Models\TicketResponse;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TicketSeeder extends Seeder
{
    public function run(): void
    {
        // Ambil semua data yang dibutuhkan agar tidak query berulang kali di dalam loop
        $employees = Employee::all();
        $helpdesks = User::whereHas('roles', function ($q) {
            $q->where('name', UserRole::ADMIN->value);
        })->get();

        if ($employees->isEmpty() || $helpdesks->isEmpty()) {
            $this->command->warn('Pastikan tabel Employees dan Users (Role Helpdesk) sudah ada isinya!');
            return;
        }

        // Tentukan berapa banyak tiket yang ingin dibuat
        $totalTickets = 50; 

        for ($i = 0; $i < $totalTickets; $i++) {
            $reporter = $employees->random();
            $operator = (collect([true, false])->random()) ? $helpdesks->random() : null;
            
            // Randomize Status & Priority
            $status = collect(['open', 'in progress', 'closed'])->random();
            $priority = collect(['low', 'mid', 'high'])->random();

            // 1. Create Ticket
            $ticket = Ticket::create([
                'uuid' => (string) Str::uuid(),
                'reporter_id' => $reporter->id,
                'operator_id' => ($status === 'open') ? null : ($operator->id ?? $helpdesks->random()->id),
                'subject' => $this->getRandomSubject(),
                'description' => 'Ini adalah deskripsi otomatis untuk testing kendala ' . $i,
                'priority' => $priority,
                'status' => $status,
            ]);

            // 2. Jika status bukan 'open', buat response (percakapan)
            if ($status !== 'open') {
                $numResponses = rand(1, 3);
                $operatorId = $ticket->operator_id ?? $helpdesks->random()->id;
                $reporterUserId = $reporter->user_id;

                for ($j = 0; $j < $numResponses; $j++) {
                    // Tentukan siapa yang membalas: Helpdesk -> User -> Helpdesk
                    $isHelpdeskTurn = ($j % 2 === 0);
                    $responderId = $isHelpdeskTurn ? $operatorId : $reporterUserId;

                    $helpdeskMessages = [
                        'Terima kasih atas laporannya. Sedang kami cek ke lokasi.',
                        'Kami sudah memperbaiki kendala tersebut. Silakan dicoba kembali.',
                        'Mohon ditunggu sebentar, kami sedang berkoordinasi dengan tim terkait.',
                        'Bisa tolong lampirkan screenshot errornya?',
                    ];
                    
                    $userMessages = [
                        'Baik, terima kasih. Saya tunggu updatenya ya.',
                        'Ini screenshot-nya sudah saya lampirkan di chat ya mas/mbak.',
                        'Oke sudah bisa, mantap!',
                        'Masih belum bisa nih, tolong dicek ulang ya.',
                    ];

                    $responseMsg = $isHelpdeskTurn ? collect($helpdeskMessages)->random() : collect($userMessages)->random();

                    TicketResponse::create([
                        'uuid' => (string) Str::uuid(),
                        'ticket_id' => $ticket->id,
                        'responder_id' => $responderId,
                        'response' => $responseMsg,
                        'is_auto_reply' => false,
                    ]);
                }
            }

            // 3. Jika status 'closed', buat Rating (acak, tidak semua user kasih rating)
            if ($status === 'closed' && rand(0, 1)) {
                SatisfactionRating::create([
                    'uuid' => (string) Str::uuid(),
                    'ticket_id' => $ticket->id,
                    'employee_id' => $reporter->id,
                    'rating' => rand(3, 5),
                    'feedback' => collect([
                        'Sangat membantu!', 
                        'Cepat responnya.', 
                        'Masalah selesai, terima kasih.', 
                        'Pelayanan oke.'
                    ])->random(),
                ]);
            }
        }

        $this->command->info("$totalTickets tiket berhasil di-generate secara acak.");
    }

    /**
     * Kumpulan subjek random agar data tidak membosankan
     */
    private function getRandomSubject(): string
    {
        $subjects = [
            'Lupa password email',
            'Printer macet di lantai 3',
            'Internet lambat di jam siang',
            'Request install Software Adobe',
            'Mouse rusak minta ganti',
            'VPN tidak bisa konek',
            'Layar monitor berkedip',
            'Keyboard ketumpahan kopi',
            'Pengajuan akun aplikasi baru',
            'Masalah sinkronisasi OneDrive'
        ];

        return collect($subjects)->random();
    }
}