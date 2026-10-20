<?php

namespace App\Models;

use App\Models\Court;
use App\Models\Lapangan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class JadwalLapangan extends Model
{
    use HasFactory;

    protected $table = 'jadwal_lapangan';

    protected $fillable = [
        'lapangan_id',
        'court_id',
        'jam_awal',
        'jam_akhir',
        'tanggal',
        'status'
    ];

    public function court()
    {
        return $this->belongsTo(Court::class);
    }

    public function lapangan()
    {
        return $this->belongsTo(Lapangan::class);
    }

    // Relationship with KonfirmasiBooking
    public function konfirmasiBooking()
    {
        return $this->hasOne(KonfirmasiBooking::class, 'jadwal_lapangan_id');
    }
}
