<?php

namespace App\Filament\Pages;

use App\Enums\PlanAlimentarioStatus;
use App\Filament\Resources\Consultas\ConsultaResource;
use App\Models\PlanAlimentario;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class DietaryPlanQueue extends Page implements HasSchemas, HasTable
{
    use InteractsWithSchemas;
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Plan alimentario';

    protected static ?string $title = 'Plan alimentario';

    protected static ?string $slug = 'planes-alimentarios';

    protected string $view = 'filament.pages.dietary-plan-queue';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                PlanAlimentario::query()
                    ->with(['consulta.paciente'])
                    ->orderBy('created_at')
                    ->orderBy('id')
            )
            ->columns([
                TextColumn::make('consulta.paciente.nombre_completo')
                    ->label('Paciente'),
                TextColumn::make('consulta.motivo')
                    ->label('Consulta')
                    ->formatStateUsing(fn (string $state): string => ConsultaResource::motivoOptions()[$state] ?? $state),
                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (PlanAlimentarioStatus $state): string => $state->label()),
                TextColumn::make('fecha_entrega')
                    ->label('Fecha de entrega')
                    ->date('d/m/Y')
                    ->placeholder('—'),
                TextColumn::make('archivo_adjunto')
                    ->label('Adjunto')
                    ->formatStateUsing(fn (?string $state): string => $state === null ? '—' : 'PDF'),
            ])
            ->recordActions([
                Action::make('deliver')
                    ->label('Entregar')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->schema([
                        DatePicker::make('fecha_entrega')
                            ->label('Fecha de entrega')
                            ->required(),
                    ])
                    ->action(function (array $data, PlanAlimentario $record): void {
                        $record->estado = PlanAlimentarioStatus::Delivered;
                        $record->fecha_entrega = $data['fecha_entrega'];
                        $record->save();
                    }),
                Action::make('markPaymentPending')
                    ->label('Falta de pago')
                    ->icon(Heroicon::OutlinedCurrencyDollar)
                    ->action(function (PlanAlimentario $record): void {
                        $record->estado = PlanAlimentarioStatus::PaymentPending;
                        $record->save();
                    }),
                Action::make('download')
                    ->label('Descargar')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->url(fn (PlanAlimentario $record): string => route('plan-alimentario.download', $record->consulta))
                    ->visible(fn (PlanAlimentario $record): bool => $record->archivo_adjunto !== null),
            ]);
    }
}
