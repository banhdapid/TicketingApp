<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\EventFormRequest;
use App\Models\Event;
use App\Models\Kategori;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class EventController extends Controller
{
public function show(Event $event)
    {
        //Load event dengan relationships nya
        $event->load(['kategori', 'tikets']);

        //Tambahkan pencarian Related Events
        //Panggil model Event secara eksplisit karena sedang berada di dalam instance $event tunggal
        $relatedEvents = \App\Models\Event::with('kategori', 'tikets')
            ->where('kategori_id', $event->kategori_id) //Kategori harus sama
            ->where('id', '!=', $event->id) //Kecualikan event yang sedang dibaca ini
            ->upcoming() //Event belum terjadi
            ->take(4) //Maksimal 4 event
            ->get();

        //Return view dengan menyisipkan variabel baru
        return view('events.show', [
            'event'         => $event,
            'relatedEvents' => $relatedEvents,
        ]);
    }

    public function index(Request $request)
    {
        //Inisialisasi query dan Load events dengan relationships (Eager Loading)
        //untuk mencegah masalah N+1 Query Problem yang membuat server lemot
        $query = Event::with(['kategori', 'tikets']);

        //Filter by kategori_id jika parameter ada
        $query->when($request->filled('kategori_id'), function ($q) use ($request) {
            $q->where('kategori_id', $request->kategori_id);
        });

        //Search by judul atau lokasi jika parameter search ada
        $query->when($request->filled('search'), function ($q) use ($request) {
            $searchTerm = '%' . $request->search . '%';
            
            //PENTING: Gunakan closure (kurung kurawal) di dalam where 
            //agar logika "OR" tidak merusak filter kategori di atasnya.
            $q->where(function ($subQuery) use ($searchTerm) {
                $subQuery->where('judul', 'like', $searchTerm)
                         ->orWhere('lokasi', 'like', $searchTerm);
            });
        });

        //Sort by tanggal_waktu (asc/desc)
        //Ambil input 'sort', default-kan ke 'asc' sesuai petunjuk
        $sortOrder = $request->get('sort', 'asc');
        
        //Validasi agar user tidak sengaja memasukkan string aneh (SQL Injection prevention)
        $sortOrder = strtolower($sortOrder) === 'desc' ? 'desc' : 'asc';
        $query->orderBy('tanggal_waktu', $sortOrder);

        //Paginate dengan 10 items per page
        //withQueryString() memastikan parameter seperti ?search=xxx&sort=desc tidak hilang saat user menekan tombol "Next Page"
        $events = $query->paginate(10)->withQueryString();

        //Kembalikan data ke view
        return view('pages.admin.events.index', compact('events'));        
        //Jika ini adalah backend untuk API, ganti return view menjadi:
        //return response()->json($events);
    }

    public function create()
    {
        //Mengambil semua kategori (diurutkan agar rapi di dropdown form)
        $kategoris = Kategori::orderBy('nama', 'asc')->get();

        return view('pages.admin.events.create', compact('kategoris'));
    }

    public function store(EventFormRequest $request)
    {
        //Handle Image Upload
        $imagePath = 'konser.jpg'; //Fallback default image
        
        if ($request->hasFile('gambar')) {
            //Simpan ke folder storage/app/public/events
            $imagePath = $request->file('gambar')->store('events', 'public');
        }

        //Gunakan Database Transaction
        //Ini memastikan jika ada error saat membuat tiket, data event akan di-rollback (dibatalkan)
        try {
            DB::beginTransaction();

            //Create Event
            $event = Event::create([
                'user_id'       => \Illuminate\Support\Facades\Auth::id(), // Otomatis mengambil ID admin yang sedang login
                'kategori_id'   => $request->kategori_id,
                'judul'         => $request->judul,
                'deskripsi'     => $request->deskripsi,
                'lokasi'        => $request->lokasi,
                'tanggal_waktu' => $request->tanggal_waktu,
                'gambar'        => $imagePath,
            ]);

            // Create Tickets (Loop array dari input)
            // Menggunakan relasi tikets() yang sudah kita buat sebelumnya di Model
            foreach ($request->tikets as $tiket) {
                $event->tikets()->create([
                    'tipe'  => $tiket['tipe'],
                    'harga' => $tiket['harga'],
                    'stok'  => $tiket['stok'],
                ]);
            }

            // Jika semua langkah di atas sukses, permanenkan data di database
            DB::commit();

            // Redirect dengan pesan sukses
            return redirect()->route('pages.admin.events.index')
                             ->with('success', 'Event dan tiket berhasil ditambahkan!');

        } catch (\Exception $e) {
            // Jika terjadi error (misal database mati tengah jalan), batalkan semua proses insert
            DB::rollBack();
            
            // Catat error ke file log Laravel (storage/logs/laravel.log) agar mudah di-debug
            Log::error('Error saat menyimpan event: ' . $e->getMessage());

            // Kembalikan user ke halaman form dengan pesan error
            return back()->withInput()
                         ->with('error', 'Terjadi kesalahan sistem saat menyimpan data.');
        }
    }

    public function edit(Event $event)
    {
        //Load relasi tiket agar siap di-loop di form HTML
        $event->load('tikets');
        
        //Load kategori untuk dropdown
        $kategoris = Kategori::orderBy('nama', 'asc')->get();
        
        //Cek status penjualan menggunakan helper yang kita buat di Model
        $hasSales = $event->hasSales();

        return view('pages.admin.events.edit', compact('event', 'kategoris', 'hasSales'));
    }

    public function update(EventFormRequest $request, Event $event)
    {
        // Validasi Khusus: Cek jika tiket sudah terjual, tanggal tidak boleh mundur/maju
        if ($event->hasSales()) {
            // Parse ke Carbon agar perbandingan string waktunya akurat dan seragam
            $oldDate = Carbon::parse($event->tanggal_waktu)->format('Y-m-d H:i');
            $newDate = Carbon::parse($request->tanggal_waktu)->format('Y-m-d H:i');
            
            if ($oldDate !== $newDate) {
                return back()->withInput()->with('error', 'Tidak dapat mengubah tanggal/waktu karena tiket event sudah terjual.');
            }
        }

        try {
            DB::beginTransaction();

            // Handle Image Update
            $imagePath = $event->gambar; // Secara default, pertahankan gambar lama di database

            if ($request->hasFile('gambar')) {
                // JANGAN HAPUS jika gambar lamanya adalah gambar default 'konser.jpg'
                if (!empty($event->gambar) && $event->gambar !== 'konser.jpg' && Storage::disk('public')->exists($event->gambar)) {
                    Storage::disk('public')->delete($event->gambar);
                }
                
                // Simpan gambar baru
                $imagePath = $request->file('gambar')->store('events', 'public');
            }

            // 4. Update Event Data
            $event->update([
                'kategori_id'   => $request->kategori_id,
                'judul'         => $request->judul,
                'deskripsi'     => $request->deskripsi,
                'lokasi'        => $request->lokasi,
                'tanggal_waktu' => $request->tanggal_waktu,
                'gambar'        => $imagePath,
            ]);

            //Handle Tickets
            
            // Kumpulkan ID tiket yang dikirim dari form (buang nilai null/kosong)
            $submittedTicketIds = collect($request->tikets)->pluck('id')->filter()->toArray();

            // Hapus tiket yang di-remove user dari form, TAPI hanya jika belum ada penjualan
            if (!$event->hasSales()) {
                $event->tikets()->whereNotIn('id', $submittedTicketIds)->delete();
            }

            // Looping data tiket dari request untuk Update atau Create
            foreach ($request->tikets as $tiketData) {
                if (!empty($tiketData['id'])) {
                    // Update tiket yang sudah ada
                    $event->tikets()->where('id', $tiketData['id'])->update([
                        'tipe'  => $tiketData['tipe'],
                        'harga' => $tiketData['harga'],
                        'stok'  => $tiketData['stok'],
                    ]);
                } else {
                    // Create tiket baru (karena tidak punya ID dari form)
                    $event->tikets()->create([
                        'tipe'  => $tiketData['tipe'],
                        'harga' => $tiketData['harga'],
                        'stok'  => $tiketData['stok'],
                    ]);
                }
            }

            DB::commit();
            return redirect()->route('pages.admin.events.index')->with('success', 'Data Event berhasil diperbarui!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error update event: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Terjadi kesalahan saat menyimpan perubahan.');
        }
    }

    public function destroy(Event $event)
    {
        // Cek apakah event sudah memiliki penjualan
        if ($event->hasSales()) {
            return back()->with('error', 'Event tidak dapat dihapus karena sudah ada tiket yang terjual!');
        }

        try {
            // Hapus file gambar dari storage
            // PENTING: Kita harus memastikan tidak menghapus gambar default 'konser.jpg'
            // karena gambar tersebut dipakai bersama oleh event-event lain.
            if (!empty($event->gambar) && $event->gambar !== 'konser.jpg') {
                if (Storage::disk('public')->exists($event->gambar)) {
                    Storage::disk('public')->delete($event->gambar);
                }
            }

            // Delete event
            // Karena kita sudah menyetel ->onDelete('cascade') di file migration,
            // semua data tiket terkait akan otomatis musnah dari database.
            $event->delete();

            // Redirect dengan success message
            return redirect()->route('pages.admin.events.index')
                             ->with('success', 'Event berhasil dihapus.');

        } catch (\Exception $e) {
            Log::error('Error hapus event: ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan sistem saat menghapus event.');
        }
    }

}
