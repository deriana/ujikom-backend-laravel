<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Ticket;
use App\Models\TicketResponse;
use App\Models\SatisfactionRating;
use DomainException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Class TicketService
 *
 * Menangani logika bisnis untuk sistem tiket bantuan,
 * termasuk pembuatan tiket, balasan, penilaian, dan manajemen status.
 */
class TicketService
{
    /**
     * Mengambil daftar semua tiket dengan filter berdasarkan peran pengguna.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function index()
    {
        $user = Auth::user();
        $query = Ticket::with([
            'reporter.user',
            'operator',
            'responses.responder',
            'rating'
        ]);

        // Apply role-based filtering
        if ($user->hasRole(UserRole::EMPLOYEE->value)) {
            // Karyawan hanya bisa melihat tiket mereka sendiri
            $query->where('reporter_id', $user->employee?->id);
        }
        // Role lain (Helpdesk, Admin, Director, dll) bisa melihat semua tiket 
        // atau bisa disesuaikan lagi jika Helpdesk hanya melihat tiket yang di-assign ke dia

        return $query->latest()->get();
    }

    /**
     * Menampilkan detail lengkap dari satu tiket tertentu.
     *
     * @param Ticket $ticket
     * @return Ticket
     */
    public function show(Ticket $ticket)
    {
        $ticket->load([
            'reporter.user',
            'operator',
            'responses.responder',
            'rating'
        ]);

        // Tambahkan atribut custom secara runtime
        $ticket->response_time = $this->calculateResponseTime($ticket);

        return $ticket;
    }

    /**
     * Menyimpan data tiket baru ke dalam database.
     *
     * @param array $data
     * @return Ticket
     */
    public function store(array $data)
    {
        return DB::transaction(function () use ($data) {
            $reporter = Auth::user()->employee;

            $isForcing = isset($data['force']) && $data['force'] == true;

            if (!$isForcing) {
                $duplicate = Ticket::where('reporter_id', $reporter->id)
                    ->where(function ($q) use ($data) {
                        $q->where('subject', 'LIKE', '%' . $data['subject'] . '%')
                            ->orWhere('description', 'LIKE', substr($data['description'], 0, 20) . '%');
                    })
                    ->where('status', 'open')
                    ->latest()
                    ->first();

                if ($duplicate) {
                    throw new \Illuminate\Http\Exceptions\HttpResponseException(
                        response()->json([
                            'message' => 'We Found Similiar Issue',
                            'is_duplicate' => true,
                            'existing_ticket' => new \App\Http\Resources\TicketResource($duplicate),
                        ], 409) // 409 Conflict
                    );
                }
            }

            // Jika lolos cek atau user pilih 'force', buat tiket baru
            return Ticket::create([
                'reporter_id' => $reporter->id,
                'subject'     => $data['subject'],
                'description' => $data['description'],
                'priority'    => $data['priority'] ?? 'low',
                'status'      => 'open',
            ]);
        });
    }

    /**
     * Memperbarui data tiket yang sudah ada (Syarat: Masih Open).
     *
     * @param Ticket $ticket
     * @param array $data
     * @return Ticket
     */
    public function update(Ticket $ticket, array $data)
    {
        if ($ticket->status !== 'open') {
            throw new DomainException('Hanya tiket dengan status Open yang dapat diedit.');
        }

        return DB::transaction(function () use ($ticket, $data) {
            $ticket->update([
                'subject' => $data['subject'] ?? $ticket->subject,
                'description' => $data['description'] ?? $ticket->description,
                'priority' => $data['priority'] ?? $ticket->priority,
            ]);

            return $ticket;
        });
    }

    /**
     * Menghapus tiket (Syarat: Masih Open).
     *
     * @param Ticket $ticket
     * @return bool
     */
    public function delete(Ticket $ticket): bool
    {
        if ($ticket->status !== 'open') {
            throw new DomainException('Hanya tiket dengan status Open yang dapat dihapus.');
        }

        return DB::transaction(function () use ($ticket) {
            // Relasi (responses dan rating) otomatis terhapus berkat onDelete('cascade') di migration
            return $ticket->delete();
        });
    }

    /**
     * Menambahkan balasan/chat ke tiket.
     *
     * @param Ticket $ticket
     * @param array $data (Berisi 'response')
     * @return TicketResponse
     */
    public function reply(Ticket $ticket, array $data)
    {
        return DB::transaction(function () use ($ticket, $data) {
            $response = TicketResponse::create([
                'ticket_id' => $ticket->id,
                'responder_id' => Auth::id(),
                'response' => $data['response'],
                'is_auto_reply' => false,
            ]);

            // Jika status tiket masih 'open' dan yang membalas adalah Helpdesk/Admin, ubah ke 'in progress'
            $user = Auth::user();
            if ($ticket->status === 'open' && !$user->hasRole(UserRole::EMPLOYEE->value)) {
                $ticket->update([
                    'status' => 'in progress',
                    'operator_id' => $ticket->operator_id ?? Auth::id() // Auto-assign jika belum ada operator
                ]);
            }

            return $response;
        });
    }

    /**
     * Memberikan penilaian (rating & feedback) setelah tiket ditutup.
     *
     * @param Ticket $ticket
     * @param array $data
     * @return SatisfactionRating
     */
    public function rate(Ticket $ticket, array $data)
    {
        if ($ticket->status !== 'closed') {
            throw new DomainException('Rating hanya dapat diberikan pada tiket yang sudah ditutup (Closed).');
        }

        $reporter = Auth::user()->employee;
        if (!$reporter || $ticket->reporter_id !== $reporter->id) {
            throw new DomainException('Hanya pembuat tiket yang dapat memberikan rating.');
        }

        // Cek apakah sudah memberikan rating
        $exists = SatisfactionRating::where('ticket_id', $ticket->id)->exists();
        if ($exists) {
            throw new DomainException('Anda sudah memberikan rating untuk tiket ini.');
        }

        return DB::transaction(function () use ($ticket, $data) {
            return SatisfactionRating::create([
                'ticket_id' => $ticket->id,
                'employee_id' => Auth::user()->employee->id,
                'rating' => $data['rating'],
                'feedback' => $data['feedback'] ?? null,
            ]);
        });
    }

    /**
     * Memperbarui status tiket (misal dari open -> in progress -> closed).
     *
     * @param Ticket $ticket
     * @param array $data
     * @return Ticket
     */
    public function updateStatus(Ticket $ticket, array $data)
    {
        return DB::transaction(function () use ($ticket, $data) {
            $ticket->update([
                'status' => $data['status'],
                // Set operator secara otomatis jika diubah ke in progress tapi operator masih kosong
                'operator_id' => ($data['status'] === 'in progress' && !$ticket->operator_id)
                    ? Auth::id()
                    : $ticket->operator_id
            ]);

            return $ticket;
        });
    }

    /**
     * Mendapatkan data rekapan untuk dashboard ticketing.
     *
     * @return array
     */
    public function getDashboardData(): array
    {
        $user = Auth::user();
        $query = Ticket::query();

        // Filter jika employee hanya melihat datanya sendiri
        if ($user->hasRole(UserRole::EMPLOYEE->value)) {
            $query->where('reporter_id', $user->employee?->id);
        }

        $totalTickets = (clone $query)->count();
        $openTickets = (clone $query)->where('status', 'open')->count();
        $inProgressTickets = (clone $query)->where('status', 'in progress')->count();
        $resolvedTickets = (clone $query)->where('status', 'resolved')->count();
        $closedTickets = (clone $query)->where('status', 'closed')->count();

        // Menghitung rata-rata rating
        $averageRating = 0;
        if ($user->hasRole(UserRole::EMPLOYEE->value)) {
            $ticketIds = (clone $query)->pluck('id');
            $averageRating = SatisfactionRating::whereIn('ticket_id', $ticketIds)->avg('rating') ?? 0;
        } else {
            $averageRating = SatisfactionRating::avg('rating') ?? 0;
        }

        // 5 Tiket terbaru
        $recentTickets = (clone $query)->with(['reporter.user', 'operator'])
            ->latest()
            ->take(5)
            ->get();

        return [
            'summary' => [
                'total' => $totalTickets,
                'open' => $openTickets,
                'in_progress' => $inProgressTickets,
                'resolved' => $resolvedTickets,
                'closed' => $closedTickets,
                'average_rating' => round($averageRating, 1),
            ],
            'recent_tickets' => \App\Http\Resources\TicketResource::collection($recentTickets),
        ];
    }

    /**
     * Menghitung durasi antara tiket dibuat dan balasan pertama dari operator.
     * * @param Ticket $ticket
     * @return string|null
     */
    public function calculateResponseTime(Ticket $ticket)
    {
        // 1. Ambil balasan pertama yang BUKAN dari reporter (berarti dari Helpdesk/Admin)
        $firstResponse = $ticket->responses()
            ->where('responder_id', '!=', $ticket->reporter->user_id)
            ->oldest()
            ->first();

        if (!$firstResponse) {
            return null; // Belum ada respon
        }

        // 2. Hitung selisih waktu
        $startTime = $ticket->created_at;
        $endTime = $firstResponse->created_at;

        // Menggunakan helper Carbon untuk format yang mudah dibaca (e.g., "15 minutes after")
        return $startTime->diffForHumans($endTime, true);
    }
}
