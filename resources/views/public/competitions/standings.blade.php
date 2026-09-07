<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-bold text-xl text-white">{{ $competition->name }} — Classement</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @include('public.competitions._tabs')

            <div class="bg-surface border border-border rounded-lg overflow-hidden">
                @include('public.competitions._standings-table')
            </div>
        </div>
    </div>
</x-app-layout>
