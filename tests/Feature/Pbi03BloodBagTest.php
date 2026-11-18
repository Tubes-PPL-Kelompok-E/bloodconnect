<?php

namespace Tests\Feature;

use App\Models\BloodBag;
use Carbon\Carbon;
use Database\Seeders\BloodBagSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class Pbi03BloodBagTest extends TestCase
{
    use RefreshDatabase;

    public function test_tabel_blood_bags_memiliki_seluruh_kolom_yang_disyaratkan(): void
    {
        $this->assertTrue(Schema::hasTable('blood_bags'));

        $kolomWajib = [
            'id',
            'bag_code',
            'blood_type',
            'rhesus',
            'component_type',
            'volume_ml',
            'donor_id',
            'collection_date',
            'expiry_date',
            'storage_location',
            'storage_temperature',
            'status',
            'created_at',
            'updated_at',
        ];

        foreach ($kolomWajib as $kolom) {
            $this->assertTrue(
                Schema::hasColumn('blood_bags', $kolom),
                "Kolom [{$kolom}] tidak ditemukan pada tabel blood_bags."
            );
        }
    }

    public function test_casting_tipe_data_model_blood_bag(): void
    {
        $bag = BloodBag::create([
            'bag_code'            => 'TEST-BAG-001',
            'blood_type'          => 'A',
            'rhesus'              => '+',
            'component_type'      => 'Packed Red Cells',
            'volume_ml'           => 350,
            'donor_id'            => 'D-000001',
            'collection_date'     => '2026-11-01',
            'expiry_date'         => '2026-12-13',
            'storage_location'    => 'Kulkas A Rak 1',
            'storage_temperature' => 4.2,
            'status'              => BloodBag::STATUS_AVAILABLE,
        ]);

        $this->assertInstanceOf(Carbon::class, $bag->collection_date);
        $this->assertInstanceOf(Carbon::class, $bag->expiry_date);
        $this->assertIsInt($bag->volume_ml);
        $this->assertIsFloat($bag->storage_temperature);
        $this->assertEquals(4.2, $bag->storage_temperature);
    }

    public function test_scope_fifo_hanya_menampilkan_status_available_dan_mengurutkan_expiry_terdekat(): void
    {
        $today = Carbon::today();

        // 1. Available - Kedaluwarsa 10 hari lagi (Urutan FIFO #2)
        $bag1 = BloodBag::create([
            'bag_code'            => 'FIFO-001',
            'blood_type'          => 'A',
            'rhesus'              => '+',
            'component_type'      => 'PRC',
            'volume_ml'           => 350,
            'collection_date'     => $today->copy()->subDays(25),
            'expiry_date'         => $today->copy()->addDays(10),
            'storage_location'    => 'Kulkas A',
            'status'              => BloodBag::STATUS_AVAILABLE,
        ]);

        // 2. Available - Kedaluwarsa 2 hari lagi (Urutan FIFO #1 - paling mendesak dipakai)
        $bag2 = BloodBag::create([
            'bag_code'            => 'FIFO-002',
            'blood_type'          => 'A',
            'rhesus'              => '+',
            'component_type'      => 'PRC',
            'volume_ml'           => 350,
            'collection_date'     => $today->copy()->subDays(33),
            'expiry_date'         => $today->copy()->addDays(2),
            'storage_location'    => 'Kulkas A',
            'status'              => BloodBag::STATUS_AVAILABLE,
        ]);

        // 3. Available - Kedaluwarsa 30 hari lagi (Urutan FIFO #3)
        $bag3 = BloodBag::create([
            'bag_code'            => 'FIFO-003',
            'blood_type'          => 'A',
            'rhesus'              => '+',
            'component_type'      => 'PRC',
            'volume_ml'           => 350,
            'collection_date'     => $today->copy()->subDays(5),
            'expiry_date'         => $today->copy()->addDays(30),
            'storage_location'    => 'Kulkas A',
            'status'              => BloodBag::STATUS_AVAILABLE,
        ]);

        // 4. Status 'reserved' meskipun expiry dekat, tidak boleh masuk antrean FIFO ready
        BloodBag::create([
            'bag_code'            => 'FIFO-RESERVED',
            'blood_type'          => 'A',
            'rhesus'              => '+',
            'component_type'      => 'PRC',
            'volume_ml'           => 350,
            'collection_date'     => $today->copy()->subDays(34),
            'expiry_date'         => $today->copy()->addDays(1),
            'storage_location'    => 'Kulkas A',
            'status'              => BloodBag::STATUS_RESERVED,
        ]);

        // 5. Status 'expired' tidak boleh masuk antrean FIFO
        BloodBag::create([
            'bag_code'            => 'FIFO-EXPIRED',
            'blood_type'          => 'A',
            'rhesus'              => '+',
            'component_type'      => 'PRC',
            'volume_ml'           => 350,
            'collection_date'     => $today->copy()->subDays(40),
            'expiry_date'         => $today->copy()->subDays(5),
            'storage_location'    => 'Kulkas Expired',
            'status'              => BloodBag::STATUS_EXPIRED,
        ]);

        $fifoBags = BloodBag::fifo()->get();

        $this->assertCount(3, $fifoBags);
        $this->assertEquals('FIFO-002', $fifoBags->get(0)->bag_code); // Sisa 2 hari
        $this->assertEquals('FIFO-001', $fifoBags->get(1)->bag_code); // Sisa 10 hari
        $this->assertEquals('FIFO-003', $fifoBags->get(2)->bag_code); // Sisa 30 hari
    }

    public function test_accessor_sisa_masa_simpan_dan_kedaluwarsa(): void
    {
        $today = Carbon::today();

        $bagAktif = BloodBag::create([
            'bag_code'         => 'BAG-AKTIF',
            'blood_type'       => 'O',
            'rhesus'           => '+',
            'component_type'   => 'PRC',
            'volume_ml'        => 350,
            'collection_date'  => $today->copy()->subDays(20),
            'expiry_date'      => $today->copy()->addDays(15),
            'storage_location' => 'Kulkas B',
            'status'           => BloodBag::STATUS_AVAILABLE,
        ]);

        $this->assertEquals(15, $bagAktif->remaining_days);
        $this->assertFalse($bagAktif->isExpired());
        $this->assertEquals('O+', $bagAktif->full_blood_type);

        $bagKadaluwarsa = BloodBag::create([
            'bag_code'         => 'BAG-LEWAT',
            'blood_type'       => 'B',
            'rhesus'           => '-',
            'component_type'   => 'WB',
            'volume_ml'        => 350,
            'collection_date'  => $today->copy()->subDays(38),
            'expiry_date'      => $today->copy()->subDays(3),
            'storage_location' => 'Kulkas B',
            'status'           => BloodBag::STATUS_EXPIRED,
        ]);

        $this->assertEquals(-3, $bagKadaluwarsa->remaining_days);
        $this->assertTrue($bagKadaluwarsa->isExpired());
        $this->assertEquals('B-', $bagKadaluwarsa->full_blood_type);
    }

    public function test_blood_bag_seeder_berhasil_mengisi_data_dummy(): void
    {
        $this->seed(BloodBagSeeder::class);

        $this->assertGreaterThanOrEqual(10, BloodBag::count());

        // Pastikan kantong tersedia terurut secara FIFO
        $fifoBags = BloodBag::fifo()->get();
        $this->assertNotEmpty($fifoBags);

        $previousExp = null;
        foreach ($fifoBags as $bag) {
            $this->assertEquals(BloodBag::STATUS_AVAILABLE, $bag->status);
            if ($previousExp !== null) {
                $this->assertGreaterThanOrEqual($previousExp, $bag->expiry_date->timestamp);
            }
            $previousExp = $bag->expiry_date->timestamp;
        }
    }
}

