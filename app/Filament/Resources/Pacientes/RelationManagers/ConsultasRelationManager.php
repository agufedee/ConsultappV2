<?php

namespace App\Filament\Resources\Pacientes\RelationManagers;

use App\Filament\Resources\Consultas\ConsultaResource;
use App\Models\Consulta;
use App\Services\PlanAlimentarioRequestService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ConsultasRelationManager extends RelationManager
{
    protected static string $relationship = 'consultas';

    protected static ?string $relatedResource = ConsultaResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->headerActions([
                CreateAction::make()
                    ->after(function (Consulta $record, array $data): void {
                        app(PlanAlimentarioRequestService::class)->sync($record, $data);
                    }),
            ]);
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function getDefaultActionUrl(Action $action): ?string
    {
        return null;
    }

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $count = $ownerRecord->consultas()->count();

        return $count > 0 ? (string) $count : null;
    }
}
