<h1>Your weekly Voilo job matches</h1>
<p>Here are new jobs matching your saved searches from the past week:</p>

@foreach($jobs as $job)
    <article style="margin-bottom: 24px;">
        <h2><a href="{{ route('jobs.show', $job) }}">{{ $job->title }}</a></h2>
        <p>{{ $job->company_name }} · {{ $job->city }}, {{ $job->state }}</p>
        <p>{{ \Illuminate\Support\Str::limit($job->description, 240) }}</p>
    </article>
@endforeach
