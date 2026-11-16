<x-layout>
    <section class="flex flex-col md:flex-row gap-4">
        {{-- Profile Info Form --}}
        <div class="bg-white p-8 rounded-lg shadow-md w-full">
            <h3 class="text-3xl text-center font-bold mb-4">
                Profile Info
            </h3>

            @if($user->avatar)
            <div class="mt-2 flex justify-center">
                <img src="{{ asset('storage/' . $user->avatar) }}" alt="{{ $user->name }}" class="w-32 h-32 object-cover rounded-full">
            </div>
            @endif

            <form action="{{ route('profile.update')}}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <x-inputs.text id="name" name="name" label="Name" value="{{ $user->name }}" />
                <x-inputs.text id="email" name="email" label="Email address" type="email" value="{{ $user->email }}" />
                <x-inputs.text id="headline" name="headline" label="Professional headline" value="{{ $user->headline }}" />

                <div class="mb-4">
                    <label class="block text-gray-700" for="bio">Professional bio</label>
                    <textarea id="bio" name="bio" rows="5" maxlength="5000" class="w-full px-4 py-2 border rounded">{{ old('bio', $user->bio) }}</textarea>
                    @error('bio')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>

                <x-inputs.text id="github_url" name="github_url" label="GitHub URL" type="url" value="{{ $user->github_url }}" />
                <x-inputs.text id="portfolio_url" name="portfolio_url" label="Portfolio URL" type="url" value="{{ $user->portfolio_url }}" />

                <x-inputs.file id="avatar" name="avatar" label="upload avatar" />

                <label class="flex items-center gap-2 mb-4">
                    <input type="checkbox" name="profile_is_public" value="1" @checked(old('profile_is_public', $user->profile_is_public))>
                    <span>Make my candidate profile publicly visible</span>
                </label>

                <button type="submit" class="w-full bg-green-500 hover:bg-green-600 text-white px-4 py-2 border rounded focus:outline-none">Save
                </button>
            </form>
            <a href="{{ route('profiles.show', $user) }}" class="block text-center text-blue-700 underline mt-4">Preview my candidate profile</a>
        </div>

        <div class="bg-white p-8 rounded-lg shadow-md w-full">
            <h3 class="text-3xl text-center font-bold mb-4">Work History</h3>
            @forelse($workExperiences as $experience)
                <form action="{{ route('work-experiences.update', $experience) }}" method="POST" class="border-b py-4">
                    @csrf
                    @method('PUT')
                    <label class="block text-gray-700">Job title</label>
                    <input name="job_title" value="{{ $experience->job_title }}" required maxlength="255" class="w-full px-4 py-2 border rounded mb-2">
                    <label class="block text-gray-700">Company</label>
                    <input name="company_name" value="{{ $experience->company_name }}" required maxlength="255" class="w-full px-4 py-2 border rounded mb-2">
                    <div class="grid grid-cols-2 gap-2">
                        <div><label class="block text-gray-700">Start date</label><input type="date" name="start_date" value="{{ $experience->start_date->format('Y-m-d') }}" required class="w-full px-4 py-2 border rounded"></div>
                        <div><label class="block text-gray-700">End date</label><input type="date" name="end_date" value="{{ $experience->end_date?->format('Y-m-d') }}" class="w-full px-4 py-2 border rounded"></div>
                    </div>
                    <label class="flex items-center gap-2 my-3"><input type="checkbox" name="is_current" value="1" @checked($experience->is_current)> I currently work here</label>
                    <label class="block text-gray-700">Description</label>
                    <textarea name="description" rows="3" maxlength="5000" class="w-full px-4 py-2 border rounded">{{ $experience->description }}</textarea>
                    <div class="flex gap-3 mt-2">
                        <button class="bg-blue-600 text-white px-4 py-2 rounded">Update</button>
                        <button form="delete-experience-{{ $experience->id }}" class="text-red-600 underline">Remove</button>
                    </div>
                </form>
                <form id="delete-experience-{{ $experience->id }}" action="{{ route('work-experiences.destroy', $experience) }}" method="POST" class="hidden">
                    @csrf
                    @method('DELETE')
                </form>
            @empty
                <p class="text-gray-600 mb-4">Add your work history to build a candidate portfolio.</p>
            @endforelse

            <form action="{{ route('work-experiences.store') }}" method="POST" class="mt-4 border-t pt-4">
                @csrf
                <h4 class="text-xl font-semibold mb-3">Add experience</h4>
                <input name="job_title" placeholder="Job title" required maxlength="255" class="w-full px-4 py-2 border rounded mb-2">
                <input name="company_name" placeholder="Company" required maxlength="255" class="w-full px-4 py-2 border rounded mb-2">
                <div class="grid grid-cols-2 gap-2">
                    <input type="date" name="start_date" required aria-label="Start date" class="w-full px-4 py-2 border rounded">
                    <input type="date" name="end_date" aria-label="End date" class="w-full px-4 py-2 border rounded">
                </div>
                <label class="flex items-center gap-2 my-3"><input type="checkbox" name="is_current" value="1"> I currently work here</label>
                <textarea name="description" rows="3" maxlength="5000" placeholder="Description" class="w-full px-4 py-2 border rounded"></textarea>
                <button class="bg-green-600 text-white px-4 py-2 rounded mt-2">Add experience</button>
            </form>
        </div>

        {{-- Job Listings --}}
        <div class="bg-white p-8 rounded-lg shadow-md w-full">
            <h3 class="text-3xl text-center font-bold mb-4">
                My Job Listings
            </h3>
            @forelse($jobs as $job)
            <div class="flex justify-between items-center border-b-2 border-gray-200 py-2">
                <div>
                    <h3 class="text-xl font-semibold">{{$job->title}}</h3>
                    <p class="text-gray-700">{{$job->job_type}}</p>
                </div>
                <div class="flex space-x-3">
                    <a href="{{ route('jobs.edit', $job->id) }}" class="bg-blue-500 text-white px-4 py-2 rounded text-sm">
                        Edit
                    </a>
                    <form method="POST" action="{{ route('jobs.destroy', $job->id)}}?from=dashboard" onsubmit="return confirm('Are you sure that you want to delete this job?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded text-sm">
                            Delete
                        </button>
                    </form>
                </div>
            </div>

            {{-- Applicants --}}
            <div class="mt-4">
                <h4 class="text-lg font-semibold mb-2">Applicants</h4>
                @forelse($job->applicants as $applicant)
                    <div class="border-b-2 border-gray-200 py-2">
                        <p class="text-gray-700"><strong>Name: </strong><a href="{{ route('profiles.show', $applicant->user) }}" class="text-blue-700 hover:underline">{{ $applicant->full_name }}</a></p>
                        <p class="text-gray-700"><strong>Phone: </strong>{{ $applicant->contact_phone }}</p>
                        <p class="text-gray-700"><strong>Email: </strong>{{ $applicant->contact_email }}</p>
                        <p class="text-gray-700"><strong>Message: </strong>{{ $applicant->message }}</p>
                        <form action="{{ route('applications.status.update', $applicant) }}" method="POST" class="flex items-center gap-2 my-3">
                            @csrf
                            @method('PATCH')
                            <label for="status-{{ $applicant->id }}">Status</label>
                            <select id="status-{{ $applicant->id }}" name="status" class="border rounded px-2 py-1">
                                @foreach(\App\Models\Applicant::STATUSES as $status)
                                    <option value="{{ $status }}" @selected($applicant->status === $status)>{{ $status }}</option>
                                @endforeach
                            </select>
                            <button class="bg-blue-600 text-white px-3 py-1 rounded">Update</button>
                        </form>
                        <p class="text-gray-800 my-4">
                            <a href="{{ route('applications.resume', $applicant) }}" class="text-blue-500 hover:underline"><i class="fas fa-download"></i> Download Resume</a>
                        </p>
                        {{-- Delete Applicant --}}
                        <form method="POST" action="{{ route('applications.destroy', $applicant->id) }}" onsubmit="return confirm('Are you sure that you want to delete this applicant?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-500 hover:text-red-700 text-sm"><i class="fas fa-trash"></i> Delete Applicant</button>
                        </form> 
                    </div>
                @empty
                    <p class="text-gray-700">No applicants yet.</p>
                @endforelse
            </div>
            @empty
            <p class="text-gray-700">You have not job listings</p>
            @endforelse

        </div>
    </section>
    <x-bottom-banner />
</x-layout>
