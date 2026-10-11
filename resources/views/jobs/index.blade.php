<x-layout>
    <div class="bg-blue-900 h-24 px-4 mb-4 flex justify-center items-center rounded">
        <x-search />
    </div>
    
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