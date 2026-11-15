<?php

namespace App\Console\Commands;

use App\Services\KedaluwarsaService;
use Illuminate\Console\Command;

class TandaiKantongKedaluwarsa extends Command
{
    protected $signature = 'kantong:tandai-kedaluwarsa';

    protected $description = 'Ubah kantong yang melewati tanggal kedaluwarsa menjadi status Kedaluwarsa';

    public function handle(KedaluwarsaService $service): int
    {
        $this->info($service->tandai() . ' kantong diubah menjadi Kedaluwarsa.');

        return self::SUCCESS;
    }
}