@extends('layouts.admin_layouts')

@section('title', 'Edit Event')

@section('content')
<div class="container mx-auto p-4 md:p-10">
    
    {{-- Back Button & Header --}}
    <div class="flex items-center gap-4 mb-6">
        <a href="{{ route('pages.admin.events.index') }}" class="btn btn-ghost btn-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
            Kembali
        </a>
        <h1 class="text-3xl font-semibold">Edit Event: {{ $event->judul }}</h1>
    </div>

    {{-- Warning Alert: Jika sudah ada penjualan --}}
    @if($hasSales)
        <div role="alert" class="alert alert-warning shadow-sm mb-6">
            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
            <div>
                <h3 class="font-bold">Peringatan Transaksi Aktif</h3>
                <div class="text-sm">Event ini sudah memiliki penjualan tiket. Tanggal & Waktu serta Tiket yang sudah ada tidak dapat dihapus.</div>
            </div>
        </div>
    @endif

    {{-- Alert Error Validasi --}}
    @if ($errors->any())
        <div role="alert" class="alert alert-error shadow-sm text-white mb-6">
            <div>
                <span class="font-bold">Gagal memperbarui data!</span>
                <ul class="list-disc pl-5 mt-1 text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- Main Form Card --}}
    <form action="{{ route('pages.admin.events.update', $event->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT') {{-- Wajib untuk method update di Laravel --}}
        
        <div class="bg-white p-6 rounded-box shadow-xs mb-6">
            <h3 class="text-xl font-bold mb-4 border-b pb-2">Informasi Event</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                
                {{-- Judul --}}
                <div class="space-y-2">
                    <label class="block">
                        <span class="text-sm font-medium">Judul Event</span>
                        <span class="text-error">*</span>
                    </label>
                    <input type="text" name="judul" value="{{ old('judul', $event->judul) }}" class="input input-bordered w-full" required>
                </div>

                {{-- Kategori --}}
                <div class="space-y-2">
                    <label class="block">
                        <span class="text-sm font-medium">Kategori</span>
                        <span class="text-error">*</span>
                    </label>
                    <select name="kategori_id" class="select select-bordered w-full" required>
                        <option value="" disabled>Pilih Kategori</option>
                        @foreach($kategoris as $kategori)
                            <option value="{{ $kategori->id }}" {{ old('kategori_id', $event->kategori_id) == $kategori->id ? 'selected' : '' }}>
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
                    <input type="text" name="lokasi" value="{{ old('lokasi', $event->lokasi) }}" class="input input-bordered w-full" required>
                </div>

                {{-- Tanggal & Waktu --}}
                <div class="space-y-2">
                    <label class="block">
                        <span class="text-sm font-medium">Tanggal & Waktu</span>
                        <span class="text-error">*</span>
                        @if($hasSales)
                            <span class="badge badge-warning badge-sm ml-1">Terkunci</span>
                        @endif
                    </label>
                    {{-- Parse format ke Y-m-d\TH:i agar terbaca oleh input datetime-local --}}
                    <input type="datetime-local" 
                           name="tanggal_waktu" 
                           value="{{ old('tanggal_waktu', \Carbon\Carbon::parse($event->tanggal_waktu)->format('Y-m-d\TH:i')) }}" 
                           class="input input-bordered w-full {{ $hasSales ? 'bg-base-200 text-gray-500 cursor-not-allowed' : '' }}" 
                           {{ $hasSales ? 'readonly' : 'required' }}>
                </div>

                {{-- Gambar Section --}}
                <div class="space-y-2 md:col-span-2">
                    <label class="block">
                        <span class="text-sm font-medium">Gambar Event (Maks 2MB)</span>
                        <span class="text-xs text-gray-500 ml-2">(Kosongkan jika tidak ingin mengubah gambar)</span>
                    </label>
                    
                    <div class="flex flex-col md:flex-row items-start gap-6 mt-2">
                        {{-- Current Image --}}
                        <div class="indicator w-full md:w-auto">
                            <span class="indicator-item badge badge-neutral">Saat Ini</span>
                            <img src="{{ $event->image_url }}" alt="Current Image" class="w-full md:w-40 h-32 object-cover rounded-box border border-base-300">
                        </div>
                        
                        {{-- New Image Upload --}}
                        <div class="w-full">
                            <input type="file" name="gambar" id="gambar-input" accept="image/png, image/jpeg, image/jpg" class="file-input file-input-bordered w-full max-w-xs">
                            <div id="preview-container" class="mt-3 hidden">
                                <p class="text-xs font-semibold mb-1 text-success">Preview Gambar Baru:</p>
                                <img id="image-preview" src="" alt="Preview" class="w-40 h-32 object-cover rounded-box border border-success">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Deskripsi --}}
                <div class="space-y-2 md:col-span-2">
                    <label class="block">
                        <span class="text-sm font-medium">Deskripsi Event</span>
                        <span class="text-error">*</span>
                    </label>
                    <textarea name="deskripsi" rows="4" class="textarea textarea-bordered w-full text-base" required>{{ old('deskripsi', $event->deskripsi) }}</textarea>
                </div>

            </div>
        </div>

        {{-- Dynamic Ticket Card --}}
        <div class="bg-white p-6 rounded-box shadow-xs mb-6">
            <div class="flex justify-between items-center border-b pb-2 mb-4">
                <h3 class="text-xl font-bold">Daftar Tiket</h3>
                <button type="button" id="add-ticket-btn" class="btn btn-sm btn-primary text-white">
                    + Tambah Tiket Baru
                </button>
            </div>

            {{-- Container untuk Ticket --}}
            <div id="ticket-container"></div>
        </div>

        {{-- Submit Button --}}
        <div class="flex justify-end gap-3 mt-6">
            <a href="{{ route('pages.admin.events.index') }}" class="btn btn-ghost">Batal</a>
            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
        </div>

    </form>
</div>

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

        // --- 2. Dynamic Ticket Pre-population ---
        const ticketContainer = document.getElementById('ticket-container');
        const btnAddTicket = document.getElementById('add-ticket-btn');
        
        // Lempar data PHP (termasuk status hasSales dan data tiket) ke JavaScript
        const hasSales = @json($hasSales);
        // Prioritaskan fungsi old() jika ada validasi gagal, jika tidak pakai tiket dari database
        const existingTickets = @json(old('tikets', $event->tikets)); 
        
        let ticketCount = 0;

        function createTicketForm(index, ticket = {}) {
            const id = ticket.id || '';
            const tipe = ticket.tipe || '';
            const harga = ticket.harga || '';
            const stok = ticket.stok || '';
            
            // Tiket terkunci jika event sudah punya penjualan DAN tiket tersebut berasal dari database (memiliki ID)
            const isLocked = hasSales && id !== '';
            
            const labelNumber = index + 1; 

            // Logic untuk menentukan opsi terpilih
            const isReguler = tipe === 'reguler' ? 'selected' : '';
            const isPremium = tipe === 'premium' ? 'selected' : '';

            const html = `
            <div class="ticket-card border border-base-200 rounded-box p-5 mb-4 bg-base-50" id="ticket-${index}">
                <div class="flex justify-between items-center mb-4">
                    <h4 class="font-semibold text-md">
                        Tiket #${labelNumber}
                        ${isLocked ? '<span class="badge badge-warning badge-sm ml-2">Sudah Terjual</span>' : ''}
                    </h4>
                    
                    ${isLocked 
                        ? '<span class="text-xs text-gray-500 font-medium bg-base-200 px-3 py-1 rounded">Terjaga oleh Sistem</span>' 
                        : `<button type="button" class="btn btn-sm btn-error text-white btn-delete-ticket" data-id="${index}">Hapus</button>`
                    }
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Input ID Tersembunyi untuk Update -->
                    <input type="hidden" name="tikets[${index}][id]" value="${id}">
                    
                    <div class="space-y-2">
                        <label class="block">
                            <span class="text-sm font-medium">Tipe Tiket</span>
                            <span class="text-error">*</span>
                        </label>
                        <select name="tikets[${index}][tipe]" class="select select-bordered w-full" required>
                            <option value="" disabled ${!tipe ? 'selected' : ''}>Pilih Tipe</option>
                            <option value="reguler" ${isReguler}>Reguler</option>
                            <option value="premium" ${isPremium}>Premium</option>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label class="block">
                            <span class="text-sm font-medium">Harga (Rp)</span>
                            <span class="text-error">*</span>
                        </label>
                        <input type="number" name="tikets[${index}][harga]" value="${harga}" min="0" class="input input-bordered w-full" required>
                    </div>

                    <div class="space-y-2">
                        <label class="block">
                            <span class="text-sm font-medium">Stok</span>
                            <span class="text-error">*</span>
                        </label>
                        <input type="number" name="tikets[${index}][stok]" value="${stok}" min="0" class="input input-bordered w-full" required>
                    </div>
                </div>
            </div>
            `;
            
            ticketContainer.insertAdjacentHTML('beforeend', html);
        }

        // Render existing tickets (atau tambahkan 1 kosong jika array kosong/error)
        if (existingTickets && existingTickets.length > 0) {
            existingTickets.forEach(ticket => {
                createTicketForm(ticketCount, ticket);
                ticketCount++;
            });
        } else {
            createTicketForm(ticketCount);
            ticketCount++;
        }

        btnAddTicket.addEventListener('click', function() {
            createTicketForm(ticketCount);
            ticketCount++;
        });

        ticketContainer.addEventListener('click', function(e) {
            const deleteBtn = e.target.closest('.btn-delete-ticket');
            if (deleteBtn) {
                const ticketCards = document.querySelectorAll('.ticket-card');
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
    });
</script>
@endsection