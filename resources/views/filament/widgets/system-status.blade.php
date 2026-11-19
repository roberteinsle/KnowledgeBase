{{-- Filament ships precompiled CSS without the app's Tailwind classes, so the layout uses inline styles. --}}
<x-filament-widgets::widget>
    <x-filament::section :heading="__('System status')">
        <x-slot name="afterHeader">
            <div style="display: flex; flex-wrap: wrap; gap: .5rem;">
                {{ $this->testSmtpAction }}
                {{ $this->sendTestMailAction }}
            </div>
        </x-slot>

        <ul style="margin: 0; padding: 0; list-style: none;">
            @foreach ($this->getChecks() as ['check' => $check, 'result' => $result])
                @php($color = match ($result->status) { 'ok' => 'success', 'warning' => 'warning', default => 'danger' })
                <li style="display: flex; align-items: flex-start; gap: .75rem; padding: .65rem 0; {{ $loop->last ? '' : 'border-bottom: 1px solid rgba(127, 127, 127, .15);' }}">
                    <span style="flex: 0 0 5.5rem; padding-top: .1rem;">
                        <x-filament::badge :color="$color">
                            {{ match ($result->status) { 'ok' => __('OK'), 'warning' => __('Warning'), default => __('Error') } }}
                        </x-filament::badge>
                    </span>
                    <span style="min-width: 0; flex: 1 1 auto;">
                        <span class="fi-section-header-heading" style="display: block; font-size: .875rem;">{{ __($check->label()) }}</span>
                        <span style="display: block; font-size: .875rem; opacity: .7; overflow-wrap: anywhere;">{{ $result->message }}</span>
                    </span>
                </li>
            @endforeach
        </ul>
    </x-filament::section>

    <x-filament-actions::modals />
</x-filament-widgets::widget>
