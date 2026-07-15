@extends('layouts.admin_layouts')

@section('title', 'Tambah Event Baru')

@section('content')
<div class="container mx-auto p-4 md:p-10">
    
    {{-- Back Button & Header --}}
    <div class="flex items-center gap-4 mb-6">
        <a href="{{ route('pages.admin.events.index') }}" class="btn btn-ghost btn-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
            Kembali
        </a>
        <h1 class="text-3xl font-semibold">Tambah Event Baru</h1>
    </div>

    {{-- Alert Error Validasi --}}
    @if ($errors->any())
        <div role="alert" class="alert alert-error shadow-sm text-white mb-6">
            <div>
                <span class="font-bold">Terjadi kesalahan!</span>
                <ul class="list-disc pl-5 mt-1 text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- Main Form Card --}}
    <form action="{{ route('pages.admin.events.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        
        <div class="bg-white p-6 rounded-box shadow-xs mb-6">
            <h3 class="text-xl font-bold mb-4 border-b pb-2">Informasi Event</h3>
            
            {{-- Fields Event (Grid 2 Columns) --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                
                {{-- Judul --}}
                <div class="space-y-2">
                    <label class="block">
                        <span class="text-sm font-medium">Judul Event</span>
                        <span class="text-error">*</span>
                    </label>
                    <input type="text" name="judul" value="{{ old('judul') }}" class="input input-bordered w-full" required>
                </div>

                {{-- Kategori --}}
                <div class="space-y-2">
                    <label class="block">
                        <span class="text-sm font-medium">Kategori</span>
                        <span class="text-error">*</span>
                    </label>
                    <select name="kategori_id" class="select select-bordered w-full" required>
                        <option value="" disabled selected>Pilih Kategori</option>
                        @foreach($kategoris as $kategori)
                            <option value="{{ $kategori->id }}" {{ old('kategori_id') == $kategori->id ? 'selected' : '' }}>
                                {{ $kategori->nama }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Lokasi --}}
                <div class="space-y-2">
                    <label class="block">
                        <span class="text-sm font-medium">Lokasi</span>
                        <span class="text-error">*</span>
                    </label>
                    <input type="text" name="lokasi" value="{{ old('lokasi') }}" class="input input-bordered w-full" required>
                </div>

                {{-- Tanggal & Waktu --}}
                <div class="space-y-2">
                    <label class="block">
                        <span class="text-sm font-medium">Tanggal & Waktu</span>
                        <span class="text-error">*</span>
                    </label>
                    <input type="datetime-local" name="tanggal_waktu" value="{{ old('tanggal_waktu') }}" class="input input-bordered w-full" required>
                </div>

                {{-- Gambar --}}
                <div class="space-y-2 md:col-span-2">
                    <label class="block">
                        <span class="text-sm font-medium">Gambar (Maks 2MB)</span>
                    </label>
                    <input type="file" name="gambar" id="gambar-input" accept="image/png, image/jpeg, image/jpg" class="file-input file-input-bordered w-full max-w-xs">
                    {{-- Image Preview Container (Hidden by default) --}}
                    <div id="preview-container" class="mt-3 hidden">
                        <img id="image-preview" src="" alt="Preview" class="w-48 h-auto object-cover rounded-md border border-base-300">
                    </div>
                </div>

                {{-- Deskripsi --}}
                <div class="space-y-2 md:col-span-2">
                    <label class="block">
                        <span class="text-sm font-medium">Deskripsi Event</span>
                        <span class="text-error">*</span>
                    </label>
                    <textarea name="deskripsi" rows="4" class="textarea textarea-bordered w-full text-base" required>{{ old('deskripsi') }}</textarea>
                </div>

            </div>
        </div>

        {{-- Dynamic Ticket Card --}}
        <div class="bg-white p-6 rounded-box shadow-xs mb-6">
            <div class="flex justify-between items-center border-b pb-2 mb-4">
                <h3 class="text-xl font-bold">Daftar Tiket</h3>
                <button type="button" id="add-ticket-btn" class="btn btn-sm btn-primary text-white">
                    + Tambah Tiket
                </button>
            </div>

            {{-- Container untuk Ticket Cards yang di-generate via JS --}}
            <div id="ticket-container">
                <!-- Javascript will inject ticket forms here -->
            </div>
        </div>

        {{-- Submit Button --}}
        <div class="flex justify-end gap-3 mt-6">
            <a href="{{ route('pages.admin.events.index') }}" class="btn btn-ghost">Batal</a>
            <button type="submit" class="btn btn-primary">Simpan Event</button>
        </div>

    </form>
</div>

{{-- JavaScript untuk Gambar & Form Dinamis --}}
<script>
    document.addEventListener("DOMContentLoaded", function() {
        
        // --- 1. Image Preview Logic ---
        const gambarInput = document.getElementById('gambar-input');
        const previewContainer = document.getElementById('preview-container');
        const imagePreview = document.getElementById('image-preview');

        gambarInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    previewContainer.classList.remove('hidden');
                }
                reader.readAsDataURL(file);
            } else {
                imagePreview.src = "";
                previewContainer.classList.add('hidden');
            }
        });

        // --- 2. Dynamic Ticket Form Logic ---
        const ticketContainer = document.getElementById('ticket-container');
        const btnAddTicket = document.getElementById('add-ticket-btn');
        let ticketCount = 0; // Index array dimulai dari 0

        // Fungsi untuk merender HTML Card Tiket
        function createTicketForm(index) {
            // Tampilan label untuk user (dimulai dari 1)
            const labelNumber = index + 1; 
            
            const html = `
            <div class="ticket-card border border-base-200 rounded-box p-5 mb-4 bg-base-50" id="ticket-${index}">
                <div class="flex justify-between items-center mb-4">
                    <h4 class="font-semibold text-md">Tiket #${labelNumber}</h4>
                    <button type="button" class="btn btn-sm btn-error text-white btn-delete-ticket" data-id="${index}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                        Hapus
                    </button>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="space-y-2">
                        <label class="block">
                            <span class="text-sm font-medium">Tipe Tiket</span>
                            <span class="text-error">*</span>
                        </label>
                        <select name="tikets[${index}][tipe]" class="select select-bordered w-full" required>
                            <option value="" disabled selected>Pilih Tipe</option>
                            <option value="reguler">Reguler</option>
                            <option value="premium">Premium</option>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label class="block">
                            <span class="text-sm font-medium">Harga (Rp)</span>
                            <span class="text-error">*</span>
                        </label>
                        <input type="number" name="tikets[${index}][harga]" min="0" placeholder="0" class="input input-bordered w-full" required>
                    </div>

                    <div class="space-y-2">
                        <label class="block">
                            <span class="text-sm font-medium">Stok</span>
                            <span class="text-error">*</span>
                        </label>
                        <input type="number" name="tikets[${index}][stok]" min="0" placeholder="0" class="input input-bordered w-full" required>
                    </div>
                </div>
            </div>
            `;
            
            // Render elemen HTML
            ticketContainer.insertAdjacentHTML('beforeend', html);
        }

        // Event Listener untuk tombol Tambah Tiket
        btnAddTicket.addEventListener('click', function() {
            createTicketForm(ticketCount);
            ticketCount++;
        });

        // Event Listener menggunakan Event Delegation untuk tombol Hapus Tiket
        ticketContainer.addEventListener('click', function(e) {
            // Mencari tombol hapus terdekat dari elemen yang diklik
            const deleteBtn = e.target.closest('.btn-delete-ticket');
            
            if (deleteBtn) {
                const ticketCards = document.querySelectorAll('.ticket-card');
                // Validasi: Cegah user menghapus jika hanya tersisa 1 tiket
                if (ticketCards.length <= 1) {
                    alert('Event minimal harus memiliki 1 jenis tiket!');
                    return;
                }
                
                const id = deleteBtn.getAttribute('data-id');
                const cardToRemove = document.getElementById(`ticket-${id}`);
                if (cardToRemove) {
                    cardToRemove.remove();
                }
            }
        });

        // Inisialisasi: Tambahkan 1 tiket secara default saat halaman dimuat
        createTicketForm(ticketCount);
        ticketCount++;
    });
</script>
@endsection