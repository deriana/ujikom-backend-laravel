<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Class PasswordResetMail
 *
 * Mailable class untuk mengirimkan email instruksi reset password kepada pengguna.
 */
class PasswordResetMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $user; /**< Instance model User penerima email */
    public $token; /**< Token unik untuk verifikasi reset password */
    public $url; /**< URL lengkap menuju halaman reset password di frontend */

    /**
     * Membuat instance pesan baru.
     *
     * @param mixed $user Model user yang meminta reset password.
     * @param string $token Token reset password yang dihasilkan.
     */
    public function __construct($user, $token)
    {
        $this->user = $user;
        $this->token = $token;
        $this->url = config('app.frontend_url') . '/reset-password?token=' . $token;
    }

    /**
     * Mendapatkan amplop pesan (envelope).
     *
     * @return Envelope Definisi subjek email.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reset Your Password',
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
            view: 'emails.password-reset',
            text: 'emails.password-reset-text',
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
