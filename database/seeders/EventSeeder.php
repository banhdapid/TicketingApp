<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Tiket;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        // 1. DATA EXISTING MILIKMU (Dibiarkan untuk testing data spesifik)
        $events = [
            [
                'user_id' => 1,
                'judul' => 'Konser Musik Rock',
                'deskripsi' => 'Nikmati malam penuh energi dengan band rock terkenal.',
                'tanggal_waktu' => '2027-08-15 19:00:00',
                'lokasi' => 'Stadion Utama',
                'kategori_id' => 1,
                'gambar' => 'konser_rock.jpg',
            ],
            [
                'user_id' => 1,
                'judul' => 'Konser Musik indie',
                'deskripsi' => 'Nikmati siang dan sore hari dengan energi band indie terkenal.',
                'tanggal_waktu' => '2027-07-15 19:00:00',
                'lokasi' => 'Stadion Kridosomo',
                'kategori_id' => 1,
                'gambar' => 'konser_rock.jpg',
            ],
            [
                'user_id' => 1,
                'judul' => 'Pameran Seni Kontemporer',
                'deskripsi' => 'Jelajahi karya seni modern dari seniman lokal dan internasional.',
                'tanggal_waktu' => '2027-09-10 10:00:00',
                'lokasi' => 'Galeri Seni Kota',
                'kategori_id' => 2,
                'gambar' => 'pameran_seni.jpg',
            ],
            [
                'user_id' => 1,
                'judul' => 'Festival Makanan Internasional',
                'deskripsi' => 'Cicipi berbagai hidangan lezat dari seluruh dunia.',
                'tanggal_waktu' => '2027-10-05 12:00:00',
                'lokasi' => 'Taman Kota',
                'kategori_id' => 3,
                'gambar' => 'festival_makanan.jpg',
            ],
        ];

        // Looping untuk data manualmu
        foreach ($events as $eventData) {
            $createdEvent = Event::create($eventData);
            
            // Buatkan tiket reguler dasar untuk event manual ini agar view-nya tidak error
            Tiket::create([
                'event_id' => $createdEvent->id,
                'tipe' => 'reguler',
                'harga' => 50000,
                'stok' => 100,
            ]);
        }

        // 2. DATA FACTORY (Untuk testing Pagination dengan 30 data tambahan)
        Event::factory(30)->create()->each(function ($event) {
            // Tiket Reguler Acak
            Tiket::create([
                'event_id' => $event->id,
                'tipe' => 'reguler',
                'harga' => fake()->randomElement([50000, 75000, 100000]),
                'stok' => fake()->numberBetween(100, 500),
            ]);

            // Tiket Premium Acak
            Tiket::create([
                'event_id' => $event->id,
                'tipe' => 'premium',
                'harga' => fake()->randomElement([150000, 200000, 250000]),
                'stok' => fake()->numberBetween(20, 50),
            ]);
        });
    }
}