<?php

namespace App\Models;

use App\Models\Court;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Lapangan extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'lapangan';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'nama_lapangan',
        'alamat',
        'deskripsi',
        'image',
        'user_id'
    ];

    /**
     * Get the courts for the lapangan.
     */
    public function courts()
    {
        return $this->hasMany(Court::class);
    }

    /**
     * Get the jadwal lapangan for the lapangan.
     */
    public function jadwalLapangan()
    {
        return $this->hasMany(JadwalLapangan::class);
    }
}
