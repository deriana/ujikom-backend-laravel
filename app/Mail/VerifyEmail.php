<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Class VerifyEmail
 *
 * Mailable class untuk mengirimkan email verifikasi alamat email kepada pengguna baru,
 * sekaligus memberikan tautan untuk pengaturan kata sandi pertama kali.
 */
class VerifyEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $user; /**< Instance model User penerima email */
    public $token; /**< Token unik untuk verifikasi email dan set password */
    public $url; /**< URL lengkap menuju halaman set-password di frontend */

    /**
     * Membuat instance pesan baru.
     *
     * @param mixed $user Model user yang baru didaftarkan.
     * @param string $token Token verifikasi yang dihasilkan.
     */
    public function __construct($user, $token)
    {
        $this->user = $user;
        $this->token = $token;
        $this->url = config('app.frontend_url') . '/set-password?token=' . $token;
    }

    /**
     * Mendapatkan amplop pesan (envelope).
     *
     * @return Envelope Definisi subjek email.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Verify Your Email Address',
        );
    }

    /**
     * Mendapatkan definisi konten pesan.
     *
     * @return Content Definisi view HTML dan teks untuk isi email.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.verify-email',
            text: 'emails.verify-email-text',
        );
    }

    /**
     * Mendapatkan lampiran untuk pesan.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment> Daftar file lampiran.
     */
    public function attachments(): array
    {
        return [];
    }
}
