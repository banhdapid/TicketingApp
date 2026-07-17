<?php

namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;

class EventFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->role === 'admin';
    }
    public function rules(): array
    {
        return [
            // Rules untuk Event
            'judul' => 'required|string|max:255',
            'deskripsi' => 'required|string',
            'lokasi' => 'required|string|max:255',
            'kategori_id' => 'required|exists:kategoris,id',
            'tanggal_waktu' => 'required|date|after:now',
            'gambar' => 'nullable|image|mimes:jpg,jpeg,png|max:2048', // max:2MB

            // Rules untuk Tikets (Validasi Array Berlapis)
            'tikets' => 'required|array|min:1',
            'tikets.*.tipe' => 'required|in:reguler,premium',
            'tikets.*.harga' => 'required|numeric|min:0',
            'tikets.*.stok' => 'required|integer|min:0',
            'tikets.*.id' => 'nullable|exists:tikets,id',
        ];
    }

    public function messages(): array
    {
        return [
            // Pesan Error Event
            'judul.required' => 'Judul event wajib diisi.',
            'judul.string' => 'Judul event harus berupa teks.',
            'judul.max' => 'Judul event tidak boleh lebih dari 255 karakter.',
            
            'deskripsi.required' => 'Deskripsi event wajib diisi.',
            'deskripsi.string' => 'Deskripsi event harus berupa teks.',
            
            'lokasi.required' => 'Lokasi event wajib diisi.',
            'lokasi.string' => 'Lokasi event harus berupa teks.',
            'lokasi.max' => 'Lokasi event tidak boleh lebih dari 255 karakter.',
            
            'kategori_id.required' => 'Kategori event wajib dipilih.',
            'kategori_id.exists' => 'Kategori yang dipilih tidak valid atau tidak ditemukan.',
            
            'tanggal_waktu.required' => 'Tanggal dan waktu event wajib diisi.',
            'tanggal_waktu.date' => 'Format tanggal dan waktu tidak valid.',
            'tanggal_waktu.after' => 'Tanggal event harus dijadwalkan di masa depan (setelah waktu saat ini).',
            
            'gambar.image' => 'File yang diunggah harus berupa gambar.',
            'gambar.mimes' => 'Format gambar yang diizinkan hanya JPG, JPEG, dan PNG.',
            'gambar.max' => 'Ukuran gambar maksimal adalah 2MB.',

            // Pesan Error Tiket (Array)
            'tikets.required' => 'Data tiket wajib diisi.',
            'tikets.array' => 'Format data tiket tidak valid.',
            'tikets.min' => 'Minimal harus ada 1 jenis tiket yang ditambahkan.',
            
            'tikets.*.tipe.required' => 'Tipe tiket pada setiap baris wajib diisi.',
            'tikets.*.tipe.in' => 'Tipe tiket hanya boleh berisi "reguler" atau "premium".',
            
            'tikets.*.harga.required' => 'Harga tiket wajib diisi.',
            'tikets.*.harga.numeric' => 'Harga tiket harus berupa angka.',
            'tikets.*.harga.min' => 'Harga tiket tidak boleh bernilai negatif.',
            
            'tikets.*.stok.required' => 'Stok tiket wajib diisi.',
            'tikets.*.stok.integer' => 'Stok tiket harus berupa bilangan bulat.',
            'tikets.*.stok.min' => 'Stok tiket tidak boleh bernilai negatif.',
            
            'tikets.*.id.exists' => 'ID tiket tidak ditemukan saat melakukan update.',
        ];
    }
}