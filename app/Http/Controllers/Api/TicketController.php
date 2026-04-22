<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Http\Requests\ReplyTicketRequest;
use App\Http\Requests\RateTicketRequest;
use App\Http\Requests\UpdateTicketStatusRequest;
use App\Http\Resources\TicketResource;
use App\Http\Resources\TicketDetailResource;
use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Class TicketController
 *
 * Controller untuk mengelola tiket bantuan (Ticketing),
 * mencakup pembuatan, pembaruan, penghapusan, balasan, penilaian, dan pengambilan data.
 */
class TicketController extends Controller
{
    protected TicketService $ticketService;

    /**
     * Membuat instance TicketController baru.
     *
     * @param TicketService $ticketService
     */
    public function __construct(TicketService $ticketService)
    {
        $this->ticketService = $ticketService;
    }

    /**
     * Menampilkan daftar semua tiket.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Ticket::class);

        $tickets = $this->ticketService->index();

        return $this->successResponse(
            TicketResource::collection($tickets),
            'Tickets fetched successfully'
        );
    }

    /**
     * Menyimpan data tiket baru ke database.
     *
     * @param StoreTicketRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreTicketRequest $request): JsonResponse
    {
        $this->authorize('create', Ticket::class);

        $ticket = $this->ticketService->store($request->validated());

        return $this->successResponse(
            new TicketResource($ticket),
            'Ticket created successfully',
            201
        );
    }

    /**
     * Menampilkan detail data tiket tertentu.
     *
     * @param Ticket $ticket
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Ticket $ticket): JsonResponse
    {
        $this->authorize('view', $ticket);

        $ticket = $this->ticketService->show($ticket);

        return $this->successResponse(
            new TicketDetailResource($ticket),
            'Ticket fetched successfully'
        );
    }

    /**
     * Memperbarui data tiket yang sudah ada (Hanya jika status open).
     *
     * @param UpdateTicketRequest $request
     * @param Ticket $ticket
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateTicketRequest $request, Ticket $ticket): JsonResponse
    {
        $this->authorize('update', $ticket);

        $updated = $this->ticketService->update($ticket, $request->validated());

        return $this->successResponse(
            new TicketResource($updated),
            'Ticket updated successfully'
        );
    }

    /**
     * Menghapus data tiket dari database (Hanya jika status open).
     *
     * @param Ticket $ticket
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Ticket $ticket): JsonResponse
    {
        $this->authorize('delete', $ticket);

        $this->ticketService->delete($ticket);

        return $this->successResponse(null, 'Ticket deleted successfully');
    }

    /**
     * Menambahkan balasan ke tiket.
     *
     * @param ReplyTicketRequest $request
     * @param Ticket $ticket
     * @return \Illuminate\Http\JsonResponse
     */
    public function reply(ReplyTicketRequest $request, Ticket $ticket): JsonResponse
    {
        $this->authorize('reply', $ticket);

        $this->ticketService->reply($ticket, $request->validated());

        // Reload data untuk menampilkan response terbaru
        $ticket = $this->ticketService->show($ticket);

        return $this->successResponse(
            new TicketDetailResource($ticket),
            'Reply added successfully'
        );
    }

    /**
     * Memberikan rating dan feedback pada tiket yang sudah closed.
     *
     * @param RateTicketRequest $request
     * @param Ticket $ticket
     * @return \Illuminate\Http\JsonResponse
     */
    public function rate(RateTicketRequest $request, Ticket $ticket): JsonResponse
    {
        $this->authorize('rate', $ticket);

        $this->ticketService->rate($ticket, $request->validated());

        // Reload data
        $ticket = $this->ticketService->show($ticket);

        return $this->successResponse(
            new TicketDetailResource($ticket),
            'Rating submitted successfully'
        );
    }

    /**
     * Mengubah status tiket.
     *
     * @param \App\Http\Requests\UpdateTicketStatusRequest $request
     * @param Ticket $ticket
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus(UpdateTicketStatusRequest $request, Ticket $ticket): JsonResponse
    {
        $this->authorize('status', $ticket);

        $this->ticketService->updateStatus($ticket, $request->validated());

        // Reload data
        $ticket = $this->ticketService->show($ticket);

        return $this->successResponse(
            new TicketResource($ticket),
            'Ticket status updated successfully'
        );
    }

    /**
     * Mengambil data untuk dashboard ticketing.
     *
     * @return JsonResponse
     */
    public function dashboard(): JsonResponse
    {
        $this->authorize('dashboard', Ticket::class);

        $data = $this->ticketService->getDashboardData();

        return $this->successResponse(
            $data,
            'Dashboard data fetched successfully'
        );
    }
}
