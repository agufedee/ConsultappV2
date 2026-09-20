<?php

use App\Filament\Resources\Pacientes\Pages\CreatePaciente;
use App\Filament\Resources\Pacientes\Pages\EditPaciente;
use App\Filament\Resources\Pacientes\Pages\ListPacientes;
use App\Models\Paciente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('renders the pacientes list page with records', function () {
    $pacientes = Paciente::factory()->count(2)->create();

    Livewire::test(ListPacientes::class)
        ->assertOk()
        ->assertCanSeeTableRecords($pacientes);
});

it('can search pacientes by nombre', function () {
    $maria = Paciente::factory()->create([
        'nombre' => 'María',
        'apellido' => 'González',
        'dni' => '30123456',
    ]);
    $juan = Paciente::factory()->create([
        'nombre' => 'Juan',
        'apellido' => 'Pérez',
        'dni' => '40123457',
    ]);

    Livewire::test(ListPacientes::class)
        ->searchTable('María')
        ->assertCanSeeTableRecords([$maria])
        ->assertCanNotSeeTableRecords([$juan]);
});

it('can search pacientes by apellido', function () {
    $maria = Paciente::factory()->create([
        'nombre' => 'María',
        'apellido' => 'González',
        'dni' => '30123456',
    ]);
    $juan = Paciente::factory()->create([
        'nombre' => 'Juan',
        'apellido' => 'Pérez',
        'dni' => '40123457',
    ]);

    Livewire::test(ListPacientes::class)
        ->searchTable('González')
        ->assertCanSeeTableRecords([$maria])
        ->assertCanNotSeeTableRecords([$juan]);
});

it('can search pacientes by dni', function () {
    $maria = Paciente::factory()->create([
        'nombre' => 'María',
        'apellido' => 'González',
        'dni' => '30123456',
    ]);
    $juan = Paciente::factory()->create([
        'nombre' => 'Juan',
        'apellido' => 'Pérez',
        'dni' => '40123457',
    ]);

    Livewire::test(ListPacientes::class)
        ->searchTable('30123456')
        ->assertCanSeeTableRecords([$maria])
        ->assertCanNotSeeTableRecords([$juan]);
});

it('can create a paciente', function () {
    Livewire::test(CreatePaciente::class)
        ->fillForm([
            'nombre' => 'Ana',
            'apellido' => 'López',
            'dni' => '30123456',
            'fecha_nacimiento' => '1990-01-15',
            'sexo' => 'femenino',
            'telefono' => '1144556677',
            'email' => 'ana.lopez@example.com',
            'antecedentes' => 'Sin antecedentes relevantes',
            'fecha_alta' => '2026-09-20',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('pacientes', [
        'nombre' => 'Ana',
        'apellido' => 'López',
        'dni' => '30123456',
        'sexo' => 'femenino',
        'email' => 'ana.lopez@example.com',
    ]);
});

it('rejects create without nombre', function () {
    Livewire::test(CreatePaciente::class)
        ->fillForm([
            'nombre' => '',
            'apellido' => 'López',
        ])
        ->call('create')
        ->assertHasFormErrors(['nombre' => 'required']);
});

it('rejects a duplicate dni on create', function () {
    Paciente::factory()->create(['dni' => '30123456']);

    Livewire::test(CreatePaciente::class)
        ->fillForm([
            'nombre' => 'Ana',
            'apellido' => 'López',
            'dni' => '30123456',
        ])
        ->call('create')
        ->assertHasFormErrors(['dni' => 'unique']);
});

it('persists an empty dni as null on create', function () {
    Livewire::test(CreatePaciente::class)
        ->fillForm([
            'nombre' => 'Ana',
            'apellido' => 'López',
            'dni' => '',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('pacientes', [
        'nombre' => 'Ana',
        'apellido' => 'López',
        'dni' => null,
    ]);
});

it('lets a paciente keep its own dni on edit', function () {
    $paciente = Paciente::factory()->create([
        'nombre' => 'Ana',
        'apellido' => 'López',
        'dni' => '30123456',
    ]);

    Livewire::test(EditPaciente::class, ['record' => $paciente->getRouteKey()])
        ->fillForm([
            'apellido' => 'López Gómez',
            'dni' => '30123456',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($paciente->fresh()->apellido)->toBe('López Gómez');
});

it('rejects assigning another pacientes dni on edit', function () {
    $primero = Paciente::factory()->create(['dni' => '30123456']);
    Paciente::factory()->create(['dni' => '30123457']);

    Livewire::test(EditPaciente::class, ['record' => $primero->getRouteKey()])
        ->fillForm(['dni' => '30123457'])
        ->call('save')
        ->assertHasFormErrors(['dni' => 'unique']);
});
