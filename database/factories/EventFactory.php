<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class EventFactory extends Factory
{
    public function definition(): array
    {
        return [
            // Tambahkan user_id agar tidak kena error Foreign Key
            'user_id' => 1, 
            
            'judul' => fake()->sentence(fake()->numberBetween(3, 5)), 
            'kategori_id' => \App\Models\Kategori::inRandomOrder()->first()->id ?? 1,
            'deskripsi' => fake()->paragraph(3),
            'lokasi' => fake()->city() . ', ' . fake()->streetAddress(),
            'tanggal_waktu' => fake()->dateTimeBetween('-1 month', '+3 months'),
            
            // Kita pakai gambar fallback yang sudah dipastikan aman sebelumnya
            'gambar' => 'konser.jpg', 
        ];
    }
}