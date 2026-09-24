<?php

namespace App\Enums;

enum PlanAlimentarioStatus: string
{
    case Pending = 'pending';
    case Delivered = 'delivered';
    case PaymentPending = 'payment_pending';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::Delivered => 'Entregado',
            self::PaymentPending => 'Falta de pago',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
