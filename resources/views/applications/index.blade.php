<x-layout>
    <section class="bg-white rounded-lg shadow-md p-6">
        <h1 class="text-3xl font-bold mb-6">My Applications</h1>
        @forelse($applications as $application)
            <article class="border-b py-4">
                <h2 class="text-xl font-semibold">
                    <a class="text-blue-700 hover:underline" href="{{ route('jobs.show', $application->job) }}">{{ $application->job->title }}</a>
                </h2>
                <p class="text-gray-600">{{ $application->job->company_name }} · {{ $application->job->city }}, {{ $application->job->state }}</p>
                <p class="mt-2">Application status: <strong>{{ $application->status }}</strong></p>
                <p class="text-sm text-gray-500">Applied {{ $application->created_at->diffForHumans() }}</p>
            </article>
        @empty
            <p>You have not applied to any jobs yet.</p>
        @endforelse
        {{ $applications->links() }}
    </section>
</x-layout>
