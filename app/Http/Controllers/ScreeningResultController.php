<?php

namespace App\Http\Controllers;

use App\Http\Requests\ScreeningRequest;
use App\Models\BloodBag;
use App\Models\ScreeningResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ScreeningResultController extends Controller
{
    public function index(Request $request)
    {
        $results = ScreeningResult::with('bloodBag')
            ->when($request->q, function ($query, $q) {
                $query->where(function ($w) use ($q) {
                    $w->where('report_number', 'like', "%{$q}%")
                        ->orWhereHas('bloodBag', fn ($b) => $b->where('bag_number', 'like', "%{$q}%"));
                });
            })
            ->when($request->status, fn ($query, $s) =>
                $query->whereHas('bloodBag', fn ($b) => $b->where('status', $s)))
            ->latest('tested_at')->latest('id')
            ->paginate(10)
            ->withQueryString();

        $pendingCount = BloodBag::where('status', BloodBag::KARANTINA)->count();

        return view('screenings.index', compact('results', 'pendingCount'));
    }

    public function create(Request $request)
    {
        $bags = BloodBag::where('status', BloodBag::KARANTINA)
            ->whereDoesntHave('screening')
            ->orderBy('bag_number')->get();

        $selectedBag = $request->query('blood_bag_id');

        return view('screenings.create', compact('bags', 'selectedBag'));
    }

    public function store(ScreeningRequest $request)
    {
        DB::transaction(function () use ($request) {
            $data = $request->safe()->except('file');

            if ($request->hasFile('file')) {
                $data['file_path'] = $request->file('file')->store('berita-acara', 'public');
            }

            $result = ScreeningResult::create($data);
            $result->bloodBag()->update(['status' => $result->resultingBagStatus()]);
        });

        return redirect()->route('screenings.index')
            ->with('success', 'Hasil uji saring berhasil disimpan dan status kantong diperbarui.');
    }

    public function show(ScreeningResult $screening)
    {
        $screening->load('bloodBag');

        return view('screenings.show', ['result' => $screening]);
    }

    public function edit(ScreeningResult $screening)
    {
        $screening->load('bloodBag');

        return view('screenings.edit', ['result' => $screening]);
    }

    /** Koreksi hasil uji (wajib alasan). */
    public function update(ScreeningRequest $request, ScreeningResult $screening)
    {
        DB::transaction(function () use ($request, $screening) {
            $data = $request->safe()->except('file');
            $data['corrected_at'] = now();

            if ($request->hasFile('file')) {
                if ($screening->file_path) {
                    Storage::disk('public')->delete($screening->file_path);
                }
                $data['file_path'] = $request->file('file')->store('berita-acara', 'public');
            }

            $screening->update($data);
            $screening->bloodBag()->update(['status' => $screening->resultingBagStatus()]);
        });

        return redirect()->route('screenings.show', $screening)
            ->with('success', 'Koreksi hasil uji berhasil disimpan. Status kantong disesuaikan.');
    }

    /** Hapus hasil uji salah input: kantong kembali Karantina. */
    public function destroy(ScreeningResult $screening)
    {
        DB::transaction(function () use ($screening) {
            if ($screening->file_path) {
                Storage::disk('public')->delete($screening->file_path);
            }
            $screening->bloodBag()->update(['status' => BloodBag::KARANTINA]);
            $screening->delete();
        });

        return redirect()->route('screenings.index')
            ->with('success', 'Hasil uji dihapus. Kantong dikembalikan ke status Karantina.');
    }
}