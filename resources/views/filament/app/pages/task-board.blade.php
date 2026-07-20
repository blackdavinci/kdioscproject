<x-filament-panels::page>
    <div class="mb-4 flex flex-wrap items-center gap-3">
        <div class="max-w-xs">
            <x-filament::input.wrapper>
                <x-filament::input.select wire:model.live="projectId" :disabled="$onlyInternal">
                    <option value="">Tous les projets</option>
                    @foreach ($this->projectOptions() as $id => $title)
                        <option value="{{ $id }}">{{ $title }}</option>
                    @endforeach
                </x-filament::input.select>
            </x-filament::input.wrapper>
        </div>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" wire:model.live="onlyInternal" class="rounded border-gray-300">
            Hors projet uniquement
        </label>
    </div>

    <div
        wire:key="board-{{ $projectId }}-{{ $onlyInternal ? 1 : 0 }}"
        x-data
        x-init="
            const run = () => { window.initKdiKanban ? window.initKdiKanban($el, $wire) : setTimeout(run, 120); };
            run();
        "
        class="grid grid-cols-1 gap-4 md:grid-cols-4"
    >
        @foreach ($this->statuses() as $status)
            @php($tasks = $this->columns()[$status->value])
            <div class="rounded-lg bg-gray-100 dark:bg-white/5 p-2">
                <div class="mb-2 flex items-center justify-between px-1">
                    <x-filament::badge :color="$status->getColor()">{{ $status->getLabel() }}</x-filament::badge>
                    <span class="text-xs text-gray-500">{{ $tasks->count() }}</span>
                </div>

                <div data-kanban-column="{{ $status->value }}" class="min-h-24 space-y-2">
                    @foreach ($tasks as $task)
                        <div
                            data-task-id="{{ $task->id }}"
                            wire:key="task-{{ $task->id }}"
                            class="cursor-grab rounded-md bg-white dark:bg-gray-900 p-2.5 shadow-sm ring-1 ring-gray-200 dark:ring-white/10"
                        >
                            <div class="text-sm font-medium">{{ $task->title }}</div>
                            <div class="mt-1 flex flex-wrap items-center gap-1.5">
                                <x-filament::badge size="xs" :color="$task->priority->getColor()">{{ $task->priority->getLabel() }}</x-filament::badge>
                                @if ($task->isInternal())
                                    <x-filament::badge size="xs" color="gray">Interne</x-filament::badge>
                                @endif
                                @if ($task->due_date)
                                    <span class="text-xs {{ $task->isOverdue() ? 'text-danger-600 font-semibold' : 'text-gray-400' }}">
                                        {{ $task->due_date->format('d/m') }}
                                    </span>
                                @endif
                            </div>
                            @if ($task->assigneeName() !== '—')
                                <div class="mt-1 text-xs text-gray-500">{{ $task->assigneeName() }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</x-filament-panels::page>
