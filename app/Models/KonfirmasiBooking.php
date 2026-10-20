<?php

namespace App\Models;

use App\Models\JadwalLapangan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class KonfirmasiBooking extends Model
{
    use HasFactory;

    protected $table = 'konfirmasi_bookings';

    protected $fillable = [
        'jadwal_lapangan_id',
        'nama',
        'no_whatsapp',
        'durasi',
        'status',
    ];

    // Relationship with JadwalLapangan
    public function jadwalLapangan()
    {
        return $this->belongsTo(JadwalLapangan::class);
    }
}
