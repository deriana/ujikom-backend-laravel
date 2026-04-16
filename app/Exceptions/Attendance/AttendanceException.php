<?php

namespace App\Exceptions\Attendance;

use DomainException;

/**
 * Class AttendanceException
 *
 * Exception dasar untuk menangani kesalahan terkait proses absensi.
 */
class AttendanceException extends DomainException
{
    protected $context = []; /**< Data tambahan terkait konteks kesalahan */

    /**
     * Membuat instance exception baru.
     *
     * @param string $message Pesan kesalahan
     * @param array $context Data tambahan untuk debugging atau informasi API
     * @param int $code Kode status HTTP (default 400)
     */
    public function __construct(string $message, array $context = [], int $code = 400)
    {
        parent::__construct($message, $code);
        $this->context = $context;
    }

    /**
     * Mendapatkan data konteks dari exception.
     *
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }
}
