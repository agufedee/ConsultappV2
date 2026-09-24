<?php

namespace App\Services;

use App\Enums\PlanAlimentarioStatus;
use App\Models\Consulta;
use App\Models\PlanAlimentario;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use LogicException;

class PlanAlimentarioRequestService
{
    /**
     * Disk that stores the dietary plan attachment. Files are stored with
     * generated (ULID) names on a non-served private disk, so they are only
     * reachable through the download route.
     */
    public const DISK = 'local_private';

    public function __construct(private readonly ConnectionInterface $db) {}

    /**
     * Synchronize a consulta's plan alimentario request with the consultation form.
     *
     * When the consultation is marked as requiring a plan, a pending request is
     * created (or the existing one is updated in place). When unmarked, the request
     * row is removed.
     *
     * Stored files follow the disk lifecycle: the new file is written by the form
     * before this service runs, the replaced file is removed only after the
     * database write commits, and a file stored for a write that rolls back is
     * removed immediately so no orphaned object survives.
     *
     * @param  array{requiere_plan?: bool, estado?: string|null, fecha_entrega?: string|null, archivo_adjunto?: string|array<string>|null}  $data
     */
    public function sync(Consulta $consulta, array $data): void
    {
        $this->db->transaction(function () use ($consulta, $data) {
            $consulta->requiere_plan = $data['requiere_plan'] ?? false;
            $consulta->save();

            $plan = $consulta->planAlimentario()->first();

            if (! $consulta->requiere_plan) {
                if ($plan !== null) {
                    $storedPath = $plan->archivo_adjunto;
                    $plan->delete();
                    $this->deleteAfterCommit($storedPath);
                }

                return;
            }

            // The consultation form dehydrates the file upload to a single path,
            // but the raw state (used by hooks) is an array. Normalize both shapes.
            $archivoAdjunto = $data['archivo_adjunto'] ?? null;

            if (is_array($archivoAdjunto)) {
                $archivoAdjunto = $archivoAdjunto[0] ?? null;
            }

            if ($plan === null) {
                PlanAlimentario::create([
                    'consulta_id' => $consulta->id,
                    'estado' => PlanAlimentarioStatus::Pending,
                    'archivo_adjunto' => $archivoAdjunto,
                    'vigente_desde' => $data['vigente_desde'] ?? now()->toDateString(),
                ]);

                return;
            }

            $estado = isset($data['estado']) ? PlanAlimentarioStatus::from($data['estado']) : $plan->estado;
            $previousPath = $plan->archivo_adjunto;

            $plan->estado = $estado;
            $plan->fecha_entrega = ($estado === PlanAlimentarioStatus::Delivered && isset($data['fecha_entrega']))
                ? $data['fecha_entrega']
                : null;

            if (array_key_exists('archivo_adjunto', $data)) {
                $plan->archivo_adjunto = $archivoAdjunto;
            }

            try {
                $plan->save();
            } catch (LogicException $e) {
                // The write rolled back, so the row keeps the previous path.
                // Remove the newly stored object that the row no longer references.
                if ($archivoAdjunto !== null && $archivoAdjunto !== $previousPath) {
                    Storage::disk(self::DISK)->delete($archivoAdjunto);
                }

                $plan->refresh();

                throw $e;
            }

            if ($plan->archivo_adjunto !== $previousPath) {
                $this->deleteAfterCommit($previousPath);
            }
        });
    }

    private function deleteAfterCommit(?string $path): void
    {
        if (blank($path)) {
            return;
        }

        DB::afterCommit(fn () => Storage::disk(self::DISK)->delete($path));
    }
}
