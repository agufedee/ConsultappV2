<?php

use App\Filament\Resources\Consultas\Pages\CreateConsulta;
use App\Filament\Resources\Consultas\Pages\EditConsulta;
use App\Models\Consulta;
use App\Models\Paciente;
use App\Models\PlanAlimentario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local_private');
});

it('rejects a non-pdf replacement before touching the existing file', function () {
    Storage::disk('local_private')->put('plan/old.pdf', 'old');

    $consulta = Consulta::factory()->create(['requiere_plan' => true]);
    $plan = PlanAlimentario::factory()->create([
        'consulta_id' => $consulta->id,
        'archivo_adjunto' => 'plan/old.pdf',
    ]);

    Livewire::test(EditConsulta::class, ['record' => $consulta->getRouteKey()])
        ->fillForm([
            'fecha' => '2026-09-01',
            'motivo' => 'control',
            'peso' => 70.00,
            'altura' => 170.00,
            'requiere_plan' => true,
        ])
        ->upload('data.archivo_adjunto', [UploadedFile::fake()->create('plan.txt', 100, 'text/plain')])
        ->call('save')
        ->assertHasFormErrors(['archivo_adjunto']);

    expect(Storage::disk('local_private')->exists('plan/old.pdf'))->toBeTrue();
    expect($plan->fresh()->archivo_adjunto)->toBe('plan/old.pdf');
});

it('rejects an oversized replacement before touching the existing file', function () {
    Storage::disk('local_private')->put('plan/old.pdf', 'old');

    $consulta = Consulta::factory()->create(['requiere_plan' => true]);
    $plan = PlanAlimentario::factory()->create([
        'consulta_id' => $consulta->id,
        'archivo_adjunto' => 'plan/old.pdf',
    ]);

    Livewire::test(EditConsulta::class, ['record' => $consulta->getRouteKey()])
        ->fillForm([
            'fecha' => '2026-09-01',
            'motivo' => 'control',
            'peso' => 70.00,
            'altura' => 170.00,
            'requiere_plan' => true,
        ])
        ->upload('data.archivo_adjunto', [UploadedFile::fake()->create('plan.pdf', 6000, 'application/pdf')])
        ->call('save')
        ->assertHasFormErrors(['archivo_adjunto']);

    expect(Storage::disk('local_private')->exists('plan/old.pdf'))->toBeTrue();
    expect($plan->fresh()->archivo_adjunto)->toBe('plan/old.pdf');
});

it('uses generated paths, never the client filename', function () {
    $this->actingAs(User::factory()->create());

    $paciente = Paciente::factory()->create();

    Livewire::test(CreateConsulta::class)
        ->fillForm([
            'paciente_id' => $paciente->id,
            'fecha' => '2026-09-21',
            'motivo' => 'control',
            'peso' => 70.00,
            'altura' => 170.00,
            'requiere_plan' => true,
        ])
        ->upload('data.archivo_adjunto', [UploadedFile::fake()->create('client-name.pdf', 500, 'application/pdf')])
        ->call('create')
        ->assertHasNoFormErrors();

    $path = Consulta::query()->first()->planAlimentario->archivo_adjunto;

    expect($path)->not->toBeNull();
    expect($path)->not->toContain('client-name');
    expect(Storage::disk('local_private')->exists($path))->toBeTrue();
});
