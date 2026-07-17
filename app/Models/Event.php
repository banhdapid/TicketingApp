<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'kategori_id',
        'judul',
        'deskripsi',
        'lokasi',
        'gambar',
        'tanggal_waktu',
    ];

    protected $casts = [
        'tanggal_waktu' => 'datetime',
    ];

    public function getStatusAttribute()
    {
        $waktuEvent = \Carbon\Carbon::parse($this->tanggal_waktu);
        $waktuSekarang = \Carbon\Carbon::now();

        if ($waktuEvent->greaterThan($waktuSekarang)) {
            return 'Upcoming';
        }

        $batasWaktuSelesai = $waktuEvent->copy()->addHours(3);
        
        if ($waktuSekarang->lessThanOrEqualTo($batasWaktuSelesai)) {
            return 'Ongoing';
        }
        return 'Completed';
    }

public function getImageUrlAttribute()
    {
        //panggil kolom 'gambar'
        $url = $this->gambar; 

        // Cek apakah kolom gambar kosong (null / empty string)
        if (empty($url)) {
            // Karena konser.jpg ada di storage/app/public, kita gunakan Storage::url
            return \Illuminate\Support\Facades\Storage::url('konser.jpg');
        }

        // Cek apakah format teksnya adalah URL penuh yang valid (eksternal/S3)
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }

        // Cek apakah file fisik tersebut ada di folder storage lokal
        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($url)) {
            return \Illuminate\Support\Facades\Storage::url($url);
        }

        // Fallback terakhir jika nama gambar tercatat di DB tapi file fisiknya terhapus
        return \Illuminate\Support\Facades\Storage::url('konser.jpg');
    }

    public function hasSales(): bool
    {
        return $this->orders()->exists();
    }

    public function tikets()
    {
        return $this->hasMany(Tiket::class);
    }

    public function kategori()
    {
        return $this->belongsTo(Kategori::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function orders()
    {
        return $this->hasMany(Order::class);
    }


    public function scopeUpcoming($query)
    {
        return $query->where('tanggal_waktu', '>', now());
    }

    public function scopeOngoing($query)
    {
        return $query->where('tanggal_waktu', '<=', now())
                     // Menggunakan raw query untuk menghitung selisih jam langsung di MySQL
                     ->whereRaw('TIMESTAMPDIFF(HOUR, tanggal_waktu, NOW()) < 3');
    }

    public function scopeCompleted($query)
    {
        // Event dianggap selesai jika waktu saat ini sudah lebih dari atau sama dengan 3 jam sejak tanggal_waktu
        return $query->whereRaw('TIMESTAMPDIFF(HOUR, tanggal_waktu, NOW()) >= 3');
    }
}
