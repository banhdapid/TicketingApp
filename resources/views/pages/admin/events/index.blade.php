@extends('layouts.admin_layouts')

@section('title', 'Manajemen Event')

@section('content')

    <div class="container mx-auto p-10">
        
        {{-- Header Section --}}
        <div class="flex items-center mb-4">
            <h1 class="text-3xl font-semibold">Manajemen Event</h1>
            <a href="{{ route('pages.admin.events.create') }}" class="btn btn-primary ml-auto">Tambah Event</a>
        </div>

        {{-- Success/Error Alerts --}}
        @if(session('success'))
            <div role="alert" class="alert alert-success mb-4 shadow-sm">
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div role="alert" class="alert alert-error mb-4 shadow-sm text-white">
                <span>{{ session('error') }}</span>
            </div>
        @endif

        {{-- Filter Form --}}
        <div class="rounded-box bg-white p-5 shadow-xs mb-6">
            <form action="{{ route('pages.admin.events.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                <div class="form-control w-full">
                    <label class="label"><span class="label-text">Search</span></label>
                    <input type="text" name="search" class="input input-bordered w-full" placeholder="Cari judul/lokasi..." value="{{ request('search') }}">
                </div>

                <div class="form-control w-full">
                    <label class="label"><span class="label-text">Kategori</span></label>
                    <select name="kategori_id" class="select select-bordered w-full">
                        <option value="">Semua Kategori</option>
                        @foreach(\App\Models\Kategori::all() as $kategori)
                            <option value="{{ $kategori->id }}" {{ request('kategori_id') == $kategori->id ? 'selected' : '' }}>
                                {{ $kategori->nama }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-control w-full">
                    <label class="label"><span class="label-text">Urutkan Tanggal</span></label>
                    <select name="sort" class="select select-bordered w-full">
                        <option value="asc" {{ request('sort') == 'asc' ? 'selected' : '' }}>Terdekat (Asc)</option>
                        <option value="desc" {{ request('sort') == 'desc' ? 'selected' : '' }}>Terjauh (Desc)</option>
                    </select>
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="btn btn-neutral flex-1">Filter</button>
                    <a href="{{ route('pages.admin.events.index') }}" class="btn btn-outline">Reset</a>
                </div>
            </form>
        </div>

        {{-- Table Section --}}
        <div class="overflow-x-auto rounded-box bg-white p-5 shadow-xs">
            <table class="table w-full">
                <!-- head -->
                <thead>
                    <tr>
                        <th>Gambar</th>
                        <th>Judul</th>
                        <th>Kategori</th>
                        <th>Waktu & Lokasi</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($events as $event)
                        <tr>
                            <td>
                                <div class="avatar">
                                    <div class="w-16 h-16 rounded">
                                        <img src="{{ $event->image_url }}" alt="Thumbnail" />
                                    </div>
                                </div>
                            </td>
                            <td class="font-bold">{{ $event->judul }}</td>
                            <td>
                                <span class="badge badge-ghost">{{ $event->kategori->nama ?? '-' }}</span>
                            </td>
                            <td>
                                <div class="text-sm">{{ \Carbon\Carbon::parse($event->tanggal_waktu)->format('d M Y, H:i') }}</div>
                                <div class="text-xs opacity-50">{{ $event->lokasi }}</div>
                            </td>
                            <td>
                                @php
                                    $badgeColor = match($event->status) {
                                        'Upcoming' => 'badge-info',
                                        'Ongoing' => 'badge-warning',
                                        'Completed' => 'badge-success',
                                        default => 'badge-ghost'
                                    };
                                @endphp
                                <span class="badge {{ $badgeColor }} gap-2">{{ $event->status }}</span>
                            </td>
                            <td>
                                <a href="{{ route('events.show', $event->id) }}" class="btn btn-sm btn-info text-white mr-1">View</a>
                                <a href="{{ route('pages.admin.events.edit', $event->id) }}" class="btn btn-sm btn-primary mr-1">Edit</a>
                                {{-- Tombol Delete memanggil Modal --}}
                                <button type="button" class="btn btn-sm bg-red-500 text-white" onclick="openDeleteModal(this)" data-id="{{ $event->id }}">Hapus</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-8">Tidak ada data event yang ditemukan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            
            {{-- Pagination --}}
            <div class="mt-4">
                {{ $events->appends(request()->except('page'))->links() }}
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <dialog id="delete_modal" class="modal">
        <form method="POST" class="modal-box">
            @csrf
            @method('DELETE')

            <input type="hidden" name="event_id" id="delete_event_id">

            <h3 class="text-lg font-bold mb-4">Hapus Event</h3>
            <p>Apakah Anda yakin ingin menghapus event ini? Semua data tiket yang terkait juga akan ikut terhapus secara permanen.</p>
            <div class="modal-action">
                <button class="btn bg-red-500 text-white hover:bg-red-600" type="submit">Hapus</button>
                <button class="btn" onclick="delete_modal.close()" type="button">Batal</button>
            </div>
        </form>
    </dialog>

    <script>
        function openDeleteModal(button) {
            const id = button.dataset.id;
            const form = document.querySelector('#delete_modal form');
            
            document.getElementById("delete_event_id").value = id;

            // Menggunakan URL string agar tidak terikat dengan penamaan route spesifik di Blade
            form.action = `{{ url('/admin/events') }}/${id}`;

            delete_modal.showModal();
        }
    </script>

@endsection