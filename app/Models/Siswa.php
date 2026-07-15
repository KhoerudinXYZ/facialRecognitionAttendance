<?php

namespace App\Models;

use App\Mail\SiswaResetPasswordMail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Mail;

class Siswa extends Authenticatable
{
    protected $table = 'siswa';

    protected $fillable = [
        'nis',
        'nisn',
        'no_hp_orang_tua',
        'email_orang_tua',
        'nama',
        'jenis_kelamin',
        'kelas_id',
        'foto',
        'is_active',
        'username',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'password' => 'hashed',
    ];

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    public function faceDescriptors(): HasMany
    {
        return $this->hasMany(FaceDescriptor::class, 'siswa_id');
    }

    public function absensi(): HasMany
    {
        return $this->hasMany(Absensi::class, 'siswa_id');
    }

    public function pengajuanIzin(): HasMany
    {
        return $this->hasMany(PengajuanIzin::class, 'siswa_id');
    }

    public function isEnrolled(): bool
    {
        return $this->faceDescriptors()->exists();
    }

    public function isRegistered(): bool
    {
        return $this->username !== null;
    }

    /**
     * Siswa tidak punya email sendiri — reset password (lihat
     * SiswaAuth\SiswaPasswordResetLinkController) memakai email_orang_tua
     * sebagai kanal, sama seperti notifikasi alpha/kehadiran.
     */
    public function getEmailForPasswordReset(): string
    {
        return $this->email_orang_tua;
    }

    public function routeNotificationForMail($notification): string
    {
        return $this->email_orang_tua;
    }

    /**
     * Override bawaan Laravel (Illuminate\Auth\Passwords\CanResetPassword)
     * karena notifikasi default membangun URL dengan route('password.reset',
     * ...) — nama route itu punya prefix 'siswa.' di aplikasi ini
     * (routes/siswa.php), jadi akan salah kalau dibiarkan bawaan. Kirim
     * langsung pakai Mailable (konsisten dengan SiswaAlphaMail/SiswaHadirMail)
     * daripada lewat sistem Notification, sekalian menyertakan nis di URL
     * supaya SiswaNewPasswordController bisa membedakan kakak-adik yang
     * kebetulan berbagi email_orang_tua yang sama.
     */
    public function sendPasswordResetNotification($token): void
    {
        $url = route('siswa.password.reset', [
            'token' => $token,
            'nis' => $this->nis,
            'email_orang_tua' => $this->email_orang_tua,
        ]);

        Mail::to($this->email_orang_tua)->send(new SiswaResetPasswordMail($this->nama, $url));
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $user->isAdmin()
            ? $query
            : $query->whereIn('kelas_id', $user->kelasBinaan()->pluck('id'));
    }
}
