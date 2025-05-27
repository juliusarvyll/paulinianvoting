<x-filament::page>
    <form wire:submit.prevent="submit" class="space-y-6 max-w-md mx-auto">
        {{ $this->form }}
        <x-filament::button type="submit" color="primary">
            Add Random Votes
        </x-filament::button>
    </form>
</x-filament::page>
