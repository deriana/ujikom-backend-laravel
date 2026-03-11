<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;

/**
 * Trait Blameable
 *
 * Trait ini secara otomatis mengisi kolom jejak audit (created_by_id, updated_by_id, deleted_by_id)
 * berdasarkan pengguna yang sedang terautentikasi saat model dibuat, diperbarui, atau dihapus.
 */
trait Blameable
{
    /**
     * Melakukan booting trait Blameable.
     * Mendaftarkan event listener untuk model Eloquent.
     */
    protected static function bootBlameable(): void
    {
        static::creating(function ($model) {
            if (Auth::check() && empty($model->created_by_id)) {
                $model->created_by_id = Auth::id();
            }
        });

        static::updating(function ($model) {
            if (Auth::check()) {
                $model->updated_by_id = Auth::id();
            }
        });

        static::deleting(function ($model) {
            if (method_exists($model, 'isForceDeleting') && ! $model->isForceDeleting()) {
                if (Auth::check()) {
                    $model->deleted_by_id = Auth::id();
                    $model->saveQuietly();
                }
            }
        });
    }
}
