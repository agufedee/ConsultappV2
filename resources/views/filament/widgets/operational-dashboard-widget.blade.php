<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex flex-wrap items-center justify-between gap-4">
            

            <x-filament::actions>
                <x-filament::button
                    tag="a"
                    :href="$newConsultationUrl"
                    icon="heroicon-o-plus-circle"
                    color="gray"
                    size="sm"
                    outlined
                >
                    Nueva consulta
                </x-filament::button>

                <x-filament::button
                    tag="a"
                    :href="$newPatientUrl"
                    icon="heroicon-o-user-plus"
                    color="gray"
                    size="sm"
                    outlined
                >
                    Nuevo paciente
                </x-filament::button>

                <x-filament::button
                    tag="a"
                    :href="$patientSearchUrl"
                    icon="heroicon-o-magnifying-glass"
                    color="gray"
                    size="sm"
                    outlined
                >
                    Buscar paciente
                </x-filament::button>

                <x-filament::button
                    tag="a"
                    :href="$queueUrl"
                    icon="heroicon-o-queue-list"
                    color="primary"
                    size="sm"
                    outlined
                >
                    Ver cola completa
                </x-filament::button>
            </x-filament::actions>
        </div>
    </x-filament::section>

    {{ $this->table }}
</x-filament-widgets::widget>
