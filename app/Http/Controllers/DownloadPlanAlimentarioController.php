<?php

namespace App\Http\Controllers;

use App\Models\Consulta;
use App\Services\PlanAlimentarioRequestService;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadPlanAlimentarioController extends Controller
{
    /**
     * Serve the dietary plan attachment for a consultation.
     *
     * The route resolves records only: the consultation is bound from the URL
     * and ownership is enforced by resolving the plan through the relationship.
     * A missing request, a request without an attachment, or an attachment
     * whose stored object no longer exists all abort with 404.
     */
    public function __invoke(Consulta $consulta): StreamedResponse
    {
        $plan = $consulta->planAlimentario()->first();

        abort_if($plan === null || blank($plan->archivo_adjunto), 404);

        $disk = Storage::disk(PlanAlimentarioRequestService::DISK);

        abort_if(! $disk->exists($plan->archivo_adjunto), 404);

        return $disk->download($plan->archivo_adjunto, 'plan-alimentario.pdf');
    }
}
