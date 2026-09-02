<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-bold text-xl text-white">Inscrire une équipe — {{ $competition->name }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-surface border border-border rounded-lg p-6">
                <p class="text-sm text-text-muted mb-6">
                    Vous inscrivez votre équipe pour la compétition <strong class="text-white">{{ $competition->name }}</strong> (saison {{ $competition->season }}).
                    Votre demande sera examinée par l'organisateur avant validation définitive.
                </p>

                <form method="POST" action="{{ route('public.competitions.register-team.store', $competition) }}" class="space-y-6">
                    @csrf

                    <div>
                        <x-input-label for="name" value="Nom de l'équipe" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" value="{{ old('name') }}" required autofocus />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <x-input-label for="city" value="Ville" />
                            <x-text-input id="city" name="city" type="text" class="mt-1 block w-full" value="{{ old('city') }}" />
                        </div>
                        <div>
                            <x-input-label for="home_ground" value="Terrain / stade" />
                            <x-text-input id="home_ground" name="home_ground" type="text" class="mt-1 block w-full" value="{{ old('home_ground') }}" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <x-input-label for="contact_phone" value="Téléphone de contact" />
                            <x-text-input id="contact_phone" name="contact_phone" type="text" class="mt-1 block w-full" value="{{ old('contact_phone') }}" />
                        </div>
                        <div>
                            <x-input-label for="contact_email" value="Email de contact" />
                            <x-text-input id="contact_email" name="contact_email" type="email" class="mt-1 block w-full" value="{{ old('contact_email') }}" />
                            <x-input-error :messages="$errors->get('contact_email')" class="mt-2" />
                        </div>
                    </div>

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('public.competitions.show', $competition) }}" class="px-4 py-2 text-sm text-text-muted hover:text-white">Annuler</a>
                        <x-primary-button>Soumettre l'inscription</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
