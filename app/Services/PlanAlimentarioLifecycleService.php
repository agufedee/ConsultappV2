<?php

namespace App\Services;

use App\Enums\PlanAlimentarioStatus;
use App\Models\PlanAlimentario;
use InvalidArgumentException;

class PlanAlimentarioLifecycleService
{
    /**
     * Deliver a plan alimentario request, requiring a non-empty delivery date.
     *
     * The date is validated before any mutation so an invalid submission leaves
     * the record untouched. The model saving invariant remains the authoritative
     * backstop for the status/date contract.
     *
     * @throws InvalidArgumentException when the delivery date is empty.
     */
    public function deliver(PlanAlimentario $plan, ?string $fechaEntrega): PlanAlimentario
    {
        if (blank($fechaEntrega)) {
            throw new InvalidArgumentException('A delivery date is required to deliver a plan alimentario request.');
        }

        $plan->estado = PlanAlimentarioStatus::Delivered;
        $plan->fecha_entrega = $fechaEntrega;
        $plan->save();

        return $plan;
    }

    /**
     * Mark a plan alimentario request as awaiting payment.
     *
     * Payment pending never blocks delivery and triggers no billing action; the
     * model invariant clears any previously stored delivery date.
     */
    public function markPaymentPending(PlanAlimentario $plan): PlanAlimentario
    {
        $plan->estado = PlanAlimentarioStatus::PaymentPending;
        $plan->save();

        return $plan;
    }
}
