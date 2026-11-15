<x-layout>
    <section class="max-w-3xl mx-auto bg-white rounded-lg shadow-md p-8">
        <div class="flex items-center gap-5">
            @if($candidate->avatar)
                <img src="{{ asset('storage/'.$candidate->avatar) }}" alt="{{ $candidate->name }}" class="w-24 h-24 rounded-full object-cover">
            @endif
            <div>
                <h1 class="text-3xl font-bold">{{ $candidate->name }}</h1>
                @if($candidate->headline)<p class="text-xl text-gray-700">{{ $candidate->headline }}</p>@endif
            </div>
        </div>

        @if($candidate->bio)
            <section class="mt-6">
                <h2 class="text-xl font-semibold">About</h2>
                <p class="mt-2 whitespace-pre-line">{{ $candidate->bio }}</p>
            </section>
        @endif

        <div class="flex gap-4 mt-4">
            @if($candidate->github_url)
                <a href="{{ $candidate->github_url }}" target="_blank" rel="noopener noreferrer" class="text-blue-700 underline">GitHub</a>
            @endif
            @if($candidate->portfolio_url)
                <a href="{{ $candidate->portfolio_url }}" target="_blank" rel="noopener noreferrer" class="text-blue-700 underline">Portfolio</a>
            @endif
        </div>

        <section class="mt-8">
            <h2 class="text-2xl font-semibold mb-4">Work History</h2>
            @forelse($candidate->workExperiences as $experience)
                <article class="border-l-4 border-blue-700 pl-4 mb-5">
                    <h3 class="text-lg font-semibold">{{ $experience->job_title }} · {{ $experience->company_name }}</h3>
                    <p class="text-gray-600">
                        {{ $experience->start_date->format('M Y') }} –
                        {{ $experience->is_current ? 'Present' : $experience->end_date?->format('M Y') }}
                    </p>
                    @if($experience->description)<p class="mt-2 whitespace-pre-line">{{ $experience->description }}</p>@endif
                </article>
            @empty
                <p>No work history has been added.</p>
            @endforelse
        </section>
    </section>
</x-layout>
