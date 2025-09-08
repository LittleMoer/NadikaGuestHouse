<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Rekap extends Model
{
    protected $table = 'rekap';
    protected $fillable = [
        'periode_awal', 'periode_akhir', 'jumlah_booking', 'jumlah_tamu', 'total_pendapatan', 'tipe_rekap', 'catatan'
    ];
}
