<x-layout>
    <section class="max-w-3xl mx-auto bg-white rounded-lg shadow-md p-6">
        <h1 class="text-3xl font-bold mb-6">Saved Searches</h1>
        @forelse($savedSearches as $savedSearch)
            <article class="border-b py-4 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold">{{ $savedSearch->name }}</h2>
                    <p class="text-gray-600">
                        {{ $savedSearch->keywords ?: 'Any keyword' }} · {{ $savedSearch->location ?: 'Any location' }}
                    </p>
                    <form method="POST" action="{{ route('saved-searches.update', $savedSearch) }}" class="text-sm flex items-center gap-2 mt-2">
                        @csrf
                        @method('PATCH')
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="digest_enabled" value="1" @checked($savedSearch->digest_enabled)>
                            Email me weekly matching jobs
                        </label>
                        <button class="text-blue-700 underline">Save preference</button>
                    </form>
                </div>
                <form method="POST" action="{{ route('saved-searches.destroy', $savedSearch) }}">
                    @csrf
                    @method('DELETE')
                    <button class="text-red-700 underline">Delete</button>
                </form>
            </article>
        @empty
            <p>No saved searches yet. Search for jobs and save a search to find matching roles again.</p>
        @endforelse
        {{ $savedSearches->links() }}
    </section>
</x-layout>
