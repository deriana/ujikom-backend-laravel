<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

/**
 * Class NotificationController
 *
 * Controller untuk mengelola notifikasi sistem bagi pengguna yang sedang login,
 * mencakup pengambilan daftar notifikasi, menandai sebagai terbaca, dan penghapusan.
 */
class NotificationController extends Controller
{
    /**
     * Mengambil semua daftar notifikasi milik pengguna (terbaca maupun belum terbaca).
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getNotifications()
    {
        return Auth::user()->notifications()->latest()->get();
    }

    /**
     * Mengambil daftar notifikasi yang belum terbaca.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Database\Eloquent\Collection
     */
    public function getUnreadNotifications()
    {
        $count = Auth::user()->unreadNotifications->count();
        if ($count === 0) {
            return response()->json([]);
        }

        return Auth::user()->unreadNotifications()->latest()->get();
    }

    /**
     * Menandai satu notifikasi spesifik sebagai terbaca.
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsRead($id)
    {
        $notification = Auth::user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return response()->json(['message' => 'Marked as read']);
    }

    /**
     * Menandai semua notifikasi yang belum terbaca milik pengguna sebagai terbaca.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAllAsRead()
    {
        Auth::user()->unreadNotifications->markAsRead();

        return response()->json([
            'message' => 'All notifications marked as read',
            'unread_count' => 0,
        ]);
    }

    /**
     * Menghapus satu notifikasi spesifik dari database.
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete($id)
    {
        $notification = Auth::user()->notifications()->findOrFail($id);
        $notification->delete();

        return response()->json(['message' => 'Deleted']);
    }

    /**
     * Menghapus seluruh riwayat notifikasi milik pengguna.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteAll()
    {
        Auth::user()->notifications()->delete();

        return response()->json(['message' => 'All notifications deleted']);
    }
}
