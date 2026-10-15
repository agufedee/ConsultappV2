<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex flex-wrap items-center justify-between gap-4">
            <span class="text-sm font-medium">
                Falta de pago: {{ $paymentPendingCount }}
            </span>

            <nav class="flex flex-wrap gap-2">
                <a href="{{ $newConsultationUrl }}">Nueva consulta</a>
                <a href="{{ $newPatientUrl }}">Nuevo paciente</a>
                <a href="{{ $patientSearchUrl }}">Buscar paciente</a>
                <a href="{{ $queueUrl }}">Ver cola completa</a>
            </nav>
        </div>
    </x-filament::section>

    {{ $this->table }}
</x-filament-widgets::widget>
