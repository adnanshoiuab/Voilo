<x-layout>
    <div class="bg-blue-900 h-24 px-4 mb-4 flex justify-center items-center rounded">
        <x-search />
    </div>

    @auth
        @if(request()->hasAny(['keywords', 'location']))
            <form method="POST" action="{{ route('saved-searches.store') }}" class="bg-white p-4 rounded mb-4 flex flex-wrap items-center gap-3">
                @csrf
                <input type="hidden" name="keywords" value="{{ request('keywords') }}">
                <input type="hidden" name="location" value="{{ request('location') }}">
                <input name="name" required maxlength="255" value="{{ request('keywords') ?: request('location') }}" placeholder="Name this search" class="px-3 py-2 border rounded">
                <label class="flex items-center gap-2"><input type="checkbox" name="digest_enabled" value="1"> Email me weekly matching jobs</label>
                <button class="bg-blue-700 hover:bg-blue-600 text-white px-4 py-2 rounded">Save search</button>
            </form>
        @endif
    @endauth
    
    {{-- Back button --}}
    @if(request()->has('keywords'))
        <a href="{{ route('jobs.index') }}" class="bg-gray-700 hover:bg-gray-600 text-white px-4 py-2 rounded mb-4 inline-block">
            <i class="fas fa-arrow-left mr-2"></i> Back
        </a>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        @forelse ($jobs as $job)
            <x-job-card :job="$job" />
        @empty
        <p>No jobs available.</p>
        @endforelse
    </div>

    {{-- Pagination Links --}}
    {{ $jobs->links()}}
    
</x-layout>