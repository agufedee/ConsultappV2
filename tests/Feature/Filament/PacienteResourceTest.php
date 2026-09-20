<?php

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
