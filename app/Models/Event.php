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
        $url = $this->url;

        //Cek apakah kolom gambar kosong (null / empty string)
        if (empty($url)) {
            // Sesuaikan path 'konser.jpg' dengan lokasi file fallback di folder public-mu
            return asset('konser.jpg'); 
        }
        //Cek apakah format teksnya adalah URL penuh yang valid (misal: S3 Amazon atau link eksternal)
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }
        //Cek apakah file fisik tersebut ada di folder storage lokal Laravel (disk 'public')
        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($url)) {
            // Storage::url() otomatis men-generate link seperti: http://localhost:8000/storage/namafile.jpg
            return \Illuminate\Support\Facades\Storage::url($url);
        }
        return asset('konser.jpg');
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
