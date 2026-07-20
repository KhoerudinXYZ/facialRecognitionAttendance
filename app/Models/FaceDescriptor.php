<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FaceDescriptor extends Model
{
    protected $table = 'face_descriptors';

    protected $fillable = [
        'siswa_id',
        'descriptor',
    ];

    protected $casts = [
        'descriptor' => 'array',
    ];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }
}
