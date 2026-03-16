<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Class CrudActivityNotification
 *
 * Notifikasi sistem yang digunakan untuk mencatat aktivitas CRUD (Create, Read, Update, Delete)
 * ke dalam database agar dapat ditampilkan pada panel notifikasi pengguna.
 */
class CrudActivityNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /** @var string Judul notifikasi */
    protected $title; /**< Judul singkat aktivitas */

    /** @var string Pesan detail notifikasi */
    protected $message; /**< Deskripsi lengkap mengenai aktivitas yang terjadi */

    /** @var string|null URL tujuan saat notifikasi diklik */
    protected $url; /**< Link referensi ke resource terkait */

    /**
     * Membuat instance notifikasi baru.
     *
     * @param string $title
     * @param string $message
     * @param string|null $url
     */
    public function __construct($title, $message, $url = null)
    {
        $this->title = $title;
        $this->message = $message;
        $this->url = $url;

        $this->afterCommit = true;
    }

    /**
     * Menentukan saluran pengiriman notifikasi.
     *
     * @param mixed $notifiable
     * @return array<int, string>
     */
    public function via($notifiable)
    {
        return ['database'];
    }

    /**
     * Mendefinisikan representasi array dari notifikasi untuk disimpan di database.
     *
     * @param mixed $notifiable
     * @return array<string, mixed>
     */
    public function toDatabase($notifiable)
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
        ];
    }
}
