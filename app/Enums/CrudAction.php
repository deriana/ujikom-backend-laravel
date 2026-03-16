<?php

namespace App\Enums;

/**
 * Enum CrudAction
 *
 * Mendefinisikan jenis tindakan CRUD dan status perubahan data karyawan.
 */
enum CrudAction: string
{
    case CREATED = 'created'; /**< Tindakan pembuatan data baru */
    case UPDATED = 'updated'; /**< Tindakan pembaruan data */
    case DELETED = 'deleted'; /**< Tindakan penghapusan data (soft delete) */
    case RESTORED = 'restored'; /**< Tindakan pemulihan data yang dihapus */
    case FORCE_DELETED = 'force_deleted'; /**< Tindakan penghapusan data secara permanen */

    // Users Specific Actions
    case TERMINATED = 'terminated'; /**< Tindakan pemberhentian karyawan */
    case RESIGNED = 'resigned'; /**< Tindakan pengunduran diri karyawan */
    case PHK = 'phk'; /**< Tindakan Pemutusan Hubungan Kerja (PHK) */
}
