<?php

namespace Database\Seeders;

use App\Models\BloodBag;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class BloodBagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * PBI-03: Seeder data awal kantong darah dengan variasi golongan darah, komponen,
     * status, dan rentang tanggal kedaluwarsa untuk menguji logika sistem FIFO.
     */
    public function run(): void
    {
        $today = Carbon::today();

        $bloodBags = [
            // 1. Golongan A+ (PRC): Mendekati kedaluwarsa dalam 5 hari -> Prioritas FIFO #1 untuk A+
            [
                'bag_code'            => 'BB-202611-001',
                'blood_type'          => 'A',
                'rhesus'              => '+',
                'component_type'      => 'Packed Red Cells',
                'volume_ml'           => 350,
                'donor_id'            => 'D-000001',
                'collection_date'     => $today->copy()->subDays(30)->toDateString(),
                'expiry_date'         => $today->copy()->addDays(5)->toDateString(),
                'storage_location'    => 'Refrigerator A - Shelf 1',
                'storage_temperature' => 4.0,
                'status'              => BloodBag::STATUS_AVAILABLE,
            ],

            // 2. Golongan A+ (PRC): Masih segar (25 hari) -> Prioritas FIFO setelah BB-202611-001
            [
                'bag_code'            => 'BB-202611-002',
                'blood_type'          => 'A',
                'rhesus'              => '+',
                'component_type'      => 'Packed Red Cells',
                'volume_ml'           => 350,
                'donor_id'            => 'D-000002',
                'collection_date'     => $today->copy()->subDays(10)->toDateString(),
                'expiry_date'         => $today->copy()->addDays(25)->toDateString(),
                'storage_location'    => 'Refrigerator A - Shelf 2',
                'storage_temperature' => 3.8,
                'status'              => BloodBag::STATUS_AVAILABLE,
            ],

            // 3. Golongan B+ (Thrombocyte Concentrate): Masa simpan sangat singkat (sisa 2 hari) -> Prioritas FIFO #1 untuk B+
            [
                'bag_code'            => 'BB-202611-003',
                'blood_type'          => 'B',
                'rhesus'              => '+',
                'component_type'      => 'Thrombocyte Concentrate',
                'volume_ml'           => 250,
                'donor_id'            => 'D-000003',
                'collection_date'     => $today->copy()->subDays(3)->toDateString(),
                'expiry_date'         => $today->copy()->addDays(2)->toDateString(),
                'storage_location'    => 'Platelet Agitator Unit 1',
                'storage_temperature' => 22.0,
                'status'              => BloodBag::STATUS_AVAILABLE,
            ],

            // 4. Golongan O+ (Whole Blood): Tersedia 28 hari
            [
                'bag_code'            => 'BB-202611-004',
                'blood_type'          => 'O',
                'rhesus'              => '+',
                'component_type'      => 'Whole Blood',
                'volume_ml'           => 450,
                'donor_id'            => 'D-000004',
                'collection_date'     => $today->copy()->subDays(7)->toDateString(),
                'expiry_date'         => $today->copy()->addDays(28)->toDateString(),
                'storage_location'    => 'Refrigerator B - Shelf 1',
                'storage_temperature' => 4.2,
                'status'              => BloodBag::STATUS_AVAILABLE,
            ],

            // 5. Golongan O+ (PRC): Kritis mendekati kedaluwarsa (sisa 2 hari) -> Prioritas FIFO #1 untuk O+
            [
                'bag_code'            => 'BB-202611-005',
                'blood_type'          => 'O',
                'rhesus'              => '+',
                'component_type'      => 'Packed Red Cells',
                'volume_ml'           => 350,
                'donor_id'            => 'D-000005',
                'collection_date'     => $today->copy()->subDays(33)->toDateString(),
                'expiry_date'         => $today->copy()->addDays(2)->toDateString(),
                'storage_location'    => 'Refrigerator B - Shelf 2',
                'storage_temperature' => 4.1,
                'status'              => BloodBag::STATUS_AVAILABLE,
            ],

            // 6. Golongan AB+ (Fresh Frozen Plasma): Masa simpan panjang 1 tahun (Freezer)
            [
                'bag_code'            => 'BB-202611-006',
                'blood_type'          => 'AB',
                'rhesus'              => '+',
                'component_type'      => 'Fresh Frozen Plasma',
                'volume_ml'           => 200,
                'donor_id'            => 'Mobile Unit Grand Indonesia',
                'collection_date'     => $today->copy()->subDays(40)->toDateString(),
                'expiry_date'         => $today->copy()->addDays(325)->toDateString(),
                'storage_location'    => 'Deep Freezer -30C Box 01',
                'storage_temperature' => -25.5,
                'status'              => BloodBag::STATUS_AVAILABLE,
            ],

            // 7. Golongan O- (PRC - Darah Langka): Sisa 20 hari
            [
                'bag_code'            => 'BB-202611-007',
                'blood_type'          => 'O',
                'rhesus'              => '-',
                'component_type'      => 'Packed Red Cells',
                'volume_ml'           => 350,
                'donor_id'            => 'D-000008',
                'collection_date'     => $today->copy()->subDays(15)->toDateString(),
                'expiry_date'         => $today->copy()->addDays(20)->toDateString(),
                'storage_location'    => 'Rare Blood Vault R-01',
                'storage_temperature' => 3.9,
                'status'              => BloodBag::STATUS_AVAILABLE,
            ],

            // 8. Golongan A- (PRC - Darah Langka): Sisa 23 hari
            [
                'bag_code'            => 'BB-202611-008',
                'blood_type'          => 'A',
                'rhesus'              => '-',
                'component_type'      => 'Packed Red Cells',
                'volume_ml'           => 350,
                'donor_id'            => 'D-000010',
                'collection_date'     => $today->copy()->subDays(12)->toDateString(),
                'expiry_date'         => $today->copy()->addDays(23)->toDateString(),
                'storage_location'    => 'Rare Blood Vault R-02',
                'storage_temperature' => 4.0,
                'status'              => BloodBag::STATUS_AVAILABLE,
            ],

            // 9. Golongan B+ (PRC): Status 'reserved' (sudah dialokasikan untuk jadwal operasi)
            [
                'bag_code'            => 'BB-202611-009',
                'blood_type'          => 'B',
                'rhesus'              => '+',
                'component_type'      => 'Packed Red Cells',
                'volume_ml'           => 350,
                'donor_id'            => 'Sukarela UTD Pusat',
                'collection_date'     => $today->copy()->subDays(20)->toDateString(),
                'expiry_date'         => $today->copy()->addDays(15)->toDateString(),
                'storage_location'    => 'Refrigerator A - Shelf 3',
                'storage_temperature' => 4.0,
                'status'              => BloodBag::STATUS_RESERVED,
            ],

            // 10. Golongan AB- (WB): Status 'expired' (sudah lewat kedaluwarsa 3 hari lalu)
            [
                'bag_code'            => 'BB-202611-010',
                'blood_type'          => 'AB',
                'rhesus'              => '-',
                'component_type'      => 'Whole Blood',
                'volume_ml'           => 350,
                'donor_id'            => 'D-000012',
                'collection_date'     => $today->copy()->subDays(38)->toDateString(),
                'expiry_date'         => $today->copy()->subDays(3)->toDateString(),
                'storage_location'    => 'Quarantine Expired Bin',
                'storage_temperature' => 4.5,
                'status'              => BloodBag::STATUS_EXPIRED,
            ],

            // 11. Golongan O+ (PRC): Status 'distributed' (telah dikirim ke RS Siloam)
            [
                'bag_code'            => 'BB-202611-011',
                'blood_type'          => 'O',
                'rhesus'              => '+',
                'component_type'      => 'Packed Red Cells',
                'volume_ml'           => 350,
                'donor_id'            => 'D-000001',
                'collection_date'     => $today->copy()->subDays(25)->toDateString(),
                'expiry_date'         => $today->copy()->addDays(10)->toDateString(),
                'storage_location'    => 'Cold Box Transit #01',
                'storage_temperature' => 3.9,
                'status'              => BloodBag::STATUS_DISTRIBUTED,
            ],
        ];

        foreach ($bloodBags as $data) {
            BloodBag::updateOrCreate(
                ['bag_code' => $data['bag_code']],
                $data
            );
        }
    }
}

