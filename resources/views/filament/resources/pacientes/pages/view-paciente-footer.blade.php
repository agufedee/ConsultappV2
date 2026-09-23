@if ($hasConsultations)
    <div>
        <button type="button" wire:click="toggleWeightChart">
            {{ $showWeightChart ? 'Hide weight evolution' : 'Show weight evolution' }}
        </button>

        @if ($showWeightChart)
            <livewire:app.filament.widgets.patient-weight-evolution-chart :record="$record" />
        @endif
    </div>
@endif
