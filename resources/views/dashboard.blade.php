<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('MEAL Master') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-8 text-gray-900 space-y-4">
                    <h3 class="text-lg font-semibold">Open the MEAL dashboard</h3>
                    <p class="text-sm text-gray-600">
                        The enterprise shell runs as a separate Angular app. Start it with
                        <code class="text-xs bg-gray-100 px-1.5 py-0.5 rounded">cd frontend && npm start</code>,
                        then sign in there with your API credentials.
                    </p>
                    <a
                        href="{{ rtrim(config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:4200')), '/') }}/login"
                        class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500"
                    >
                        Go to MEAL Master →
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
