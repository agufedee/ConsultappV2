<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\DietaryPlanQueue;
use App\Filament\Resources\Consultas\ConsultaResource;
use App\Filament\Resources\Pacientes\PacienteResource;
use App\Models\PlanAlimentario;
use App\Services\PlanAlimentarioLifecycleService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class OperationalDashboardWidget extends TableWidget
{
    /** Registered explicitly on the `consultapp` panel only. */
    protected static bool $isDiscovered = false;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.operational-dashboard-widget';

    /** Maximum number of pending plans rendered on the dashboard. */
    private const PENDING_LIMIT = 10;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                PlanAlimentario::query()
                    ->pending()
                    ->with(['consulta.paciente'])
                    ->orderedForQueue()
                    ->limit(self::PENDING_LIMIT)
            )
            ->paginated(false)
            ->emptyStateHeading('Sin planes pendientes')
            ->columns([
                TextColumn::make('consulta.paciente.nombre_completo')
                    ->label('Paciente'),
                TextColumn::make('consulta.motivo')
                    ->label('Consulta')
                    ->formatStateUsing(fn (string $state): string => ConsultaResource::motivoOptions()[$state] ?? $state),
                TextColumn::make('created_at')
                    ->label('Solicitado')
                    ->date('d/m/Y'),
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
                        app(PlanAlimentarioLifecycleService::class)->deliver($record, $data['fecha_entrega']);
                    }),
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'paymentPendingCount' => PlanAlimentario::query()->paymentPending()->count(),
            'newConsultationUrl' => ConsultaResource::getUrl('create', panel: 'consultapp'),
            'newPatientUrl' => PacienteResource::getUrl('create', panel: 'consultapp'),
            'patientSearchUrl' => PacienteResource::getUrl('index', panel: 'consultapp'),
            'queueUrl' => DietaryPlanQueue::getUrl(panel: 'consultapp'),
        ];
    }
}
