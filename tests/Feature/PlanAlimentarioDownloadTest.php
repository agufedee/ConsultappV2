<?php

use App\Models\Consulta;
use App\Models\Paciente;
use App\Models\PlanAlimentario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local_private');
});

it('requires a panel user to download an attachment', function () {
    $consulta = Consulta::factory()->create(['requiere_plan' => true]);
    Storage::disk('local_private')->put('plan/plan.pdf', 'pdf');

    PlanAlimentario::factory()->create([
        'consulta_id' => $consulta->id,
        'archivo_adjunto' => 'plan/plan.pdf',
    ]);

    $this->get(route('plan-alimentario.download', $consulta, false))
        ->assertRedirect();
});

it('downloads the attachment for an authenticated user with attachment disposition', function () {
    $this->actingAs(User::factory()->create());

    $consulta = Consulta::factory()->create(['requiere_plan' => true]);
    Storage::disk('local_private')->put('plan/plan.pdf', 'pdf-content');

    PlanAlimentario::factory()->create([
        'consulta_id' => $consulta->id,
        'archivo_adjunto' => 'plan/plan.pdf',
    ]);

    $response = $this->get(route('plan-alimentario.download', $consulta, false));

    $response->assertOk();
    $response->assertDownload();
    $response->assertHeader('Content-Type', 'application/pdf');
    expect($response->streamedContent())->toBe('pdf-content');
});

it('returns 404 when the stored object is missing', function () {
    $this->actingAs(User::factory()->create());

    $consulta = Consulta::factory()->create(['requiere_plan' => true]);
    PlanAlimentario::factory()->create([
        'consulta_id' => $consulta->id,
        'archivo_adjunto' => 'plan/missing.pdf',
    ]);

    $this->get(route('plan-alimentario.download', $consulta, false))
        ->assertNotFound();
});

it('returns 404 when the consultation has no request', function () {
    $this->actingAs(User::factory()->create());

    $consulta = Consulta::factory()->create(['requiere_plan' => false]);

    $this->get(route('plan-alimentario.download', $consulta, false))
        ->assertNotFound();
});

it('denies an attachment download across consultations', function () {
    $this->actingAs(User::factory()->create());

    $paciente = Paciente::factory()->create();
    $consulta = Consulta::factory()->create(['paciente_id' => $paciente->id, 'requiere_plan' => true]);
    Storage::disk('local_private')->put('plan/plan.pdf', 'pdf');

    PlanAlimentario::factory()->create([
        'consulta_id' => $consulta->id,
        'archivo_adjunto' => 'plan/plan.pdf',
    ]);

    $other = Consulta::factory()->create(['paciente_id' => $paciente->id, 'requiere_plan' => false]);

    $this->get(route('plan-alimentario.download', $other, false))
        ->assertNotFound();
});

it('exposes no public url for the private disk', function () {
    Storage::disk('local_private')->put('plan/plan.pdf', 'pdf');

    $diskConfig = Config::get('filesystems.disks.local_private');

    expect($diskConfig)->not->toHaveKey('url');
    expect($diskConfig)->not->toHaveKey('serve');
    expect($diskConfig['root'])->toBe(storage_path('app/private/planes-alimentarios'));
});
