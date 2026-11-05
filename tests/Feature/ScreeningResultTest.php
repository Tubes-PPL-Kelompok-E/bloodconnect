<?php

namespace Tests\Feature;

use App\Models\BloodBag;
use App\Models\ScreeningResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ScreeningResultTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function payload(BloodBag $bag, array $override = []): array
    {
        return array_merge([
            'blood_bag_id' => $bag->id,
            'report_number' => 'BA/UTD/2026/001',
            'hiv' => 'non_reaktif',
            'hbsag' => 'non_reaktif',
            'hcv' => 'non_reaktif',
            'sifilis' => 'non_reaktif',
            'tested_at' => now()->toDateString(),
            'file' => UploadedFile::fake()->create('ba.pdf', 100, 'application/pdf'),
        ], $override);
    }

    public function test_create_non_reactive_makes_bag_ready(): void
    {
        $bag = BloodBag::factory()->create();

        $this->post(route('screenings.store'), $this->payload($bag))
            ->assertRedirect(route('screenings.index'));

        $this->assertDatabaseHas('screening_results', ['report_number' => 'BA/UTD/2026/001']);
        $this->assertSame('ready', $bag->fresh()->status);
        Storage::disk('public')->assertExists(ScreeningResult::first()->file_path);
    }

    public function test_create_reactive_makes_bag_ditahan(): void
    {
        $bag = BloodBag::factory()->create();

        $this->post(route('screenings.store'), $this->payload($bag, ['hbsag' => 'reaktif']));

        $this->assertSame('ditahan', $bag->fresh()->status);
    }

    public function test_create_validation_fails_without_required_fields(): void
    {
        $this->post(route('screenings.store'), [])
            ->assertSessionHasErrors(['blood_bag_id', 'report_number', 'hiv', 'hbsag', 'hcv', 'sifilis', 'tested_at']);
    }

    public function test_cannot_screen_same_bag_twice(): void
    {
        $bag = BloodBag::factory()->create();
        $this->post(route('screenings.store'), $this->payload($bag));

        $this->post(route('screenings.store'), $this->payload($bag, ['report_number' => 'BA-2']))
            ->assertSessionHasErrors('blood_bag_id');
    }

    public function test_read_list_and_detail(): void
    {
        $bag = BloodBag::factory()->create();
        $this->post(route('screenings.store'), $this->payload($bag));
        $result = ScreeningResult::first();

        $this->get(route('screenings.index'))
            ->assertOk()->assertSee($bag->bag_number)->assertSee('BA/UTD/2026/001');

        $this->get(route('screenings.show', $result))
            ->assertOk()->assertSee('Detail Hasil Uji');
    }

    public function test_update_requires_reason_and_changes_status(): void
    {
        $bag = BloodBag::factory()->create();
        $this->post(route('screenings.store'), $this->payload($bag));
        $result = ScreeningResult::first();

        $base = [
            'report_number' => $result->report_number,
            'hiv' => 'reaktif', 'hbsag' => 'non_reaktif', 'hcv' => 'non_reaktif', 'sifilis' => 'non_reaktif',
            'tested_at' => now()->toDateString(),
        ];

        // tanpa alasan -> gagal
        $this->put(route('screenings.update', $result), $base)
            ->assertSessionHasErrors('correction_reason');

        // dengan alasan -> sukses, kantong jadi ditahan
        $this->put(route('screenings.update', $result),
            $base + ['correction_reason' => 'Salah input HIV pada lembar kerja'])
            ->assertRedirect(route('screenings.show', $result));

        $this->assertSame('ditahan', $bag->fresh()->status);
        $this->assertNotNull($result->fresh()->corrected_at);
    }

    public function test_delete_returns_bag_to_karantina_and_removes_file(): void
    {
        $bag = BloodBag::factory()->create();
        $this->post(route('screenings.store'), $this->payload($bag));
        $result = ScreeningResult::first();
        $path = $result->file_path;

        $this->delete(route('screenings.destroy', $result))
            ->assertRedirect(route('screenings.index'));

        $this->assertDatabaseMissing('screening_results', ['id' => $result->id]);
        $this->assertSame('karantina', $bag->fresh()->status);
        Storage::disk('public')->assertMissing($path);
    }
}