<?php

namespace App\Http\Controllers\Petugas;

use App\Http\Controllers\Controller;
use App\Models\Donation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DonationController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));

        $donations = Donation::with('donor')
            ->when($q !== '', function ($query) use ($q) {
                $query->where('blood_bag_code', 'like', "%{$q}%")
                    ->orWhereHas('donor', fn ($d) => $d->where('name', 'like', "%{$q}%"));
            })
            ->orderByDesc('donation_date')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('donasi.petugas.index', compact('donations', 'q'));
    }

    public function create()
    {
        return view('donasi.petugas.create', ['donors' => $this->donors()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());

        Donation::create($data + [
            'recorded_by' => $request->user()->id,
            'status'      => Donation::STATUS_TERCATAT,
        ]);

        return redirect()->route('petugas.donasi.index')->with('success', 'Donasi berhasil dicatat.');
    }

        public function edit(Donation $donation)
    {
        abort_if($donation->isCancelled(), 403, 'Donasi yang dibatalkan tidak dapat diubah.');

        return view('donasi.petugas.edit', [
            'donation' => $donation,
            'donors'   => $this->donors(),
        ]);
    }

    public function update(Request $request, Donation $donation)
    {
        abort_if($donation->isCancelled(), 403, 'Donasi yang dibatalkan tidak dapat diubah.');

        $donation->update($request->validate($this->rules($donation)));

        return redirect()->route('petugas.donasi.index')->with('success', 'Data donasi berhasil dikoreksi.');
    }

        public function destroy(Request $request, Donation $donation)
    {
        if ($donation->isCancelled()) {
            return redirect()->route('petugas.donasi.index')->withErrors('Donasi ini sudah dibatalkan.');
        }

        $data = $request->validate([
            'cancel_reason' => ['required', 'string', 'max:255'],
        ]);

        $donation->update([
            'status'        => Donation::STATUS_DIBATALKAN,
            'cancel_reason' => $data['cancel_reason'],
            'cancelled_at'  => now(),
        ]);

        return redirect()->route('petugas.donasi.index')->with('success', 'Donasi berhasil dibatalkan.');
    }

    private function donors()
    {
        return User::role(config('donor.donor_role'))->orderBy('name')->get(['id', 'name', 'email']);
    }

    private function rules(?Donation $donation = null): array
    {
        return [
            'user_id'        => ['required', Rule::in($this->donors()->pluck('id')->all())],
            'blood_bag_code' => [
                'required', 'string', 'max:50',
                Rule::unique('donations', 'blood_bag_code')
                    ->where('status', Donation::STATUS_TERCATAT)
                    ->ignore($donation?->id),
            ],
            'donation_date'  => ['required', 'date', 'before_or_equal:today'],
            'volume_ml'      => ['required', 'integer', 'between:250,500'],
            'blood_type'     => ['nullable', Rule::in(['A', 'B', 'AB', 'O'])],
            'notes'          => ['nullable', 'string', 'max:255'],
        ];
    }
}