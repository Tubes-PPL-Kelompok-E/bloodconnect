<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PemusnahanRequest;
use App\Models\KantongDarah;
use App\Models\LogTransaksi;
use App\Models\PemusnahanKantong;
use App\Services\KedaluwarsaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PemusnahanController extends Controller
{
    /** Daftar kantong kedaluwarsa (belum dimusnahkan). */
    public function kedaluwarsa()
    {
        $kantongs = KantongDarah::where('status', 'kedaluwarsa')
            ->orderBy('tanggal_kedaluwarsa')
            ->paginate(10);

        $totalDimusnahkan = PemusnahanKantong::count();

        return view('admin.pemusnahan.kedaluwarsa', compact('kantongs', 'totalDimusnahkan'));
    }

    /** Pengecekan kedaluwarsa manual (sama dengan scheduler). */
    public function periksa(KedaluwarsaService $service)
    {
        $jumlah = $service->tandai();

        return redirect()->route('admin.kedaluwarsa')
            ->with('success', "{$jumlah} kantong ditandai kedaluwarsa.");
    }

    /** Riwayat pemusnahan. */
    public function index(Request $request)
    {
        $riwayat = PemusnahanKantong::with('kantong')
            ->when($request->q, function ($query, $q) {
                $like = '%' . mb_strtolower($q) . '%';
                $query->where(function ($w) use ($like) {
                    $w->whereRaw('LOWER(nama_petugas) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(alasan) LIKE ?', [$like])
                        ->orWhereHas('kantong', fn ($k) => $k->whereRaw('LOWER(kode_batch) LIKE ?', [$like]));
                });
            })
            ->when($request->filter === 'dini', fn ($q) => $q->where('sebelum_kedaluwarsa', true))
            ->latest('tanggal_pemusnahan')->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('admin.pemusnahan.index', compact('riwayat'));
    }

    public function create(Request $request)
    {
        $kantongs = KantongDarah::whereIn('status', PemusnahanKantong::BISA_DIMUSNAHKAN)
            ->whereDoesntHave('pemusnahan')
            ->orderByRaw("CASE WHEN status = 'kedaluwarsa' THEN 0 ELSE 1 END")
            ->orderBy('tanggal_kedaluwarsa')
            ->get();

        $dipilih = $request->query('kantong_id');

        return view('admin.pemusnahan.create', compact('kantongs', 'dipilih'));
    }

    public function store(PemusnahanRequest $request)
    {
        $data = $request->validated();

        $pemusnahan = DB::transaction(function () use ($data, $request) {
            $kantong = KantongDarah::lockForUpdate()->findOrFail($data['kantong_id']);
            $dini = PemusnahanKantong::apakahSebelumKedaluwarsa($kantong, $data['tanggal_pemusnahan']);
            $user = $request->user();

            $pemusnahan = PemusnahanKantong::create([
                'kantong_id'          => $kantong->id,
                'tanggal_pemusnahan'  => $data['tanggal_pemusnahan'],
                'alasan'              => $data['alasan'],
                'sebelum_kedaluwarsa' => $dini,
                'alasan_khusus'       => $dini ? $data['alasan_khusus'] : null,
                'petugas_id'          => $user?->id,
                'nama_petugas'        => $user?->name ?? '-',
                'status_sebelumnya'   => $kantong->status,
            ]);

            $lama = $kantong->status;
            $kantong->update(['status' => 'dimusnahkan']);

            LogTransaksi::catat(
                'kantong_dimusnahkan', 'kantong', $kantong->id,
                "Kantong {$kantong->kode_batch} dimusnahkan" . ($dini ? ' (sebelum kedaluwarsa)' : ''),
                [
                    'lama' => ['status' => $lama],
                    'baru' => [
                        'status'  => 'dimusnahkan',
                        'tanggal' => $data['tanggal_pemusnahan'],
                        'alasan'  => $data['alasan'],
                    ],
                ]
            );

            return $pemusnahan;
        });

        return redirect()->route('admin.pemusnahan.show', $pemusnahan)
            ->with('success', 'Pemusnahan kantong berhasil dicatat.');
    }

    public function show(PemusnahanKantong $pemusnahan)
    {
        $pemusnahan->load('kantong');

        return view('admin.pemusnahan.show', compact('pemusnahan'));
    }

    public function edit(PemusnahanKantong $pemusnahan)
    {
        $pemusnahan->load('kantong');

        return view('admin.pemusnahan.edit', compact('pemusnahan'));
    }

    /** Ubah catatan pemusnahan. */
    public function update(PemusnahanRequest $request, PemusnahanKantong $pemusnahan)
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $pemusnahan) {
            $kantong = $pemusnahan->kantong;
            $dini = PemusnahanKantong::apakahSebelumKedaluwarsa($kantong, $data['tanggal_pemusnahan']);

            $lama = $pemusnahan->only(['tanggal_pemusnahan', 'alasan', 'alasan_khusus']);

            $pemusnahan->update([
                'tanggal_pemusnahan'  => $data['tanggal_pemusnahan'],
                'alasan'              => $data['alasan'],
                'sebelum_kedaluwarsa' => $dini,
                'alasan_khusus'       => $dini ? $data['alasan_khusus'] : null,
            ]);

            LogTransaksi::catat(
                'kantong_dimusnahkan', 'kantong', $kantong->id,
                "Catatan pemusnahan kantong {$kantong->kode_batch} diubah",
                ['lama' => $lama, 'baru' => $pemusnahan->only(['tanggal_pemusnahan', 'alasan', 'alasan_khusus'])]
            );
        });

        return redirect()->route('admin.pemusnahan.show', $pemusnahan)
            ->with('success', 'Catatan pemusnahan berhasil diubah.');
    }

    /** Batalkan catatan yang salah input: status kantong dikembalikan. */
    public function destroy(PemusnahanKantong $pemusnahan)
    {
        DB::transaction(function () use ($pemusnahan) {
            $kantong = KantongDarah::lockForUpdate()->findOrFail($pemusnahan->kantong_id);

            // Jika tanggal kedaluwarsa sudah lewat, kembalikan sebagai Kedaluwarsa
            $kembali = $kantong->tanggal_kedaluwarsa->lt(today())
                ? 'kedaluwarsa'
                : $pemusnahan->status_sebelumnya;

            $kantong->update(['status' => $kembali]);

            LogTransaksi::catat(
                'kantong_dimusnahkan', 'kantong', $kantong->id,
                "Pemusnahan kantong {$kantong->kode_batch} dibatalkan, status dikembalikan ke {$kembali}",
                ['lama' => ['status' => 'dimusnahkan'], 'baru' => ['status' => $kembali]]
            );

            $pemusnahan->delete();
        });

        return redirect()->route('admin.pemusnahan')
            ->with('success', 'Catatan pemusnahan dibatalkan. Status kantong dikembalikan.');
    }
}