<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Class TicketPolicy
 *
 * Menangani logika otorisasi untuk operasi pada model Ticket.
 */
class TicketPolicy
{
    use HandlesAuthorization;

    /**
     * Menentukan apakah pengguna dapat melihat daftar tiket.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('ticketing.index');
    }

    /**
     * Menentukan apakah pengguna dapat melihat detail tiket tertentu.
     */
    public function view(User $user, Ticket $ticket): bool
    {
        return $user->can('ticketing.show');
    }

    /**
     * Menentukan apakah pengguna dapat membuat data tiket baru.
     */
    public function create(User $user): bool
    {
        return $user->can('ticketing.create');
    }

    /**
     * Menentukan apakah pengguna dapat mengubah data tiket.
     */
    public function update(User $user, Ticket $ticket): bool
    {
        return $user->can('ticketing.edit');
    }

    /**
     * Menentukan apakah pengguna dapat menghapus data tiket.
     */
    public function delete(User $user, Ticket $ticket): bool
    {
        return $user->can('ticketing.destroy');
    }

    /**
     * Menentukan apakah pengguna dapat mengekspor data tiket.
     */
    public function export(User $user): bool
    {
        return $user->can('ticketing.export');
    }

    /**
     * Menentukan apakah pengguna dapat membalas tiket.
     */
    public function reply(User $user, Ticket $ticket): bool
    {
        return $user->can('ticketing.reply');
    }

    /**
     * Menentukan apakah pengguna dapat mengubah status tiket.
     */
    public function changeStatus(User $user, Ticket $ticket): bool
    {
        return $user->can('ticketing.status');
    }

    /**
     * Menentukan apakah pengguna dapat menetapkan (assign) operator.
     */
    public function assign(User $user, Ticket $ticket): bool
    {
        return $user->can('ticketing.assign');
    }

    /**
     * Menentukan apakah pengguna dapat memberikan rating (feedback).
     */
    public function rate(User $user, Ticket $ticket): bool
    {
        return $user->can('ticketing.rate');
    }

    /**
     * Menentukan apakah pengguna dapat melihat dashboard tiket.
     */
    public function dashboard(User $user): bool
    {
        return $user->can('ticketing.dashboard');
    }
}
