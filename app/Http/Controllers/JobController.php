<?php

namespace App\Http\Controllers;

use App\Models\Job;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class JobController extends Controller
{
    use AuthorizesRequests;

    // @desc Show all job listings
    // @route GET /jobs
    public function index(): View
    {
        $jobs = Job::latest()->paginate(9);

        return view('jobs.index')->with('jobs', $jobs);
    }

    // @desc Show create job form
    // @route GET /jobs/create
    public function create(): View
    {
        return view('jobs.create');
    }

    // @desc Save job to database
    // @route POST /jobs
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Job::class);

        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'salary' => 'required|integer|min:0',
            'tags' => 'nullable|string',
            'job_type' => [
                'required',
                'string',
                Rule::in(['Full-Time', 'Part-Time', 'Contract', 'Freelance', 'Internship']),
            ],
            'remote' => 'required|boolean',
            'requirements' => 'nullable|string',
            'benefits' => 'nullable|string',
            'address' => 'nullable|string',
            'city' => 'required|string',
            'state' => 'required|string',
            'zipcode' => 'nullable|string',
            'contact_email' => 'required|string|email',
            'contact_phone' => 'nullable|string',
            'company_name' => 'required|string',
            'company_description' => 'nullable|string',
            'company_logo' => 'nullable|image|mimes:jpeg,jpg,png,gif|max:2048',
            'company_website' => 'nullable|url',

        ]);

        $validatedData['user_id'] = $request->user()->id;

        if ($request->hasFile('company_logo')) {
            $path = $request->file('company_logo')->store('logos', 'public');
            $validatedData['company_logo'] = $path;
        }

        Job::create($validatedData);

        return redirect()->route('jobs.index')->with('success', 'Job listing created successfully!');
    }

    // @desc Display a single job listing
    // @route GET /jobs/{$id}
    public function show(Job $job): View
    {
        return view('jobs.show')->with('job', $job);
    }

    // @desc Show edit job form
    // @route GET /jobs/{$id}/edit
    public function edit(Job $job): View
    {
        // check if the user is authorized
        $this->authorize('update', $job);

        return view('jobs.edit')->with('job', $job);
    }

    // @desc Update job listing
    // @route PUT /jobs/{$id}
    public function update(Request $request, Job $job): RedirectResponse
    {
        // check if the user is authorized
        $this->authorize('update', $job);

        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'salary' => 'required|integer|min:0',
            'tags' => 'nullable|string',
            'job_type' => [
                'required',
                'string',
                Rule::in(['Full-Time', 'Part-Time', 'Contract', 'Freelance', 'Internship']),
            ],
            'remote' => 'required|boolean',
            'requirements' => 'nullable|string',
            'benefits' => 'nullable|string',
            'address' => 'nullable|string',
            'city' => 'required|string',
            'state' => 'required|string',
            'zipcode' => 'nullable|string',
            'contact_email' => 'required|string|email',
            'contact_phone' => 'nullable|string',
            'company_name' => 'required|string',
            'company_description' => 'nullable|string',
            'company_logo' => 'nullable|image|mimes:jpeg,jpg,png,gif|max:2048',
            'company_website' => 'nullable|url',

        ]);

        if ($request->hasFile('company_logo')) {
            if ($job->company_logo) {
                Storage::disk('public')->delete($job->company_logo);
            }

            $path = $request->file('company_logo')->store('logos', 'public');
            $validatedData['company_logo'] = $path;
        }

        $job->update($validatedData);

        return redirect()->route('jobs.index')->with('success', 'Job listing updated successfully!');
    }

    // @desc Delete a job listing
    // @route DELETE /jobs/{$id}
    public function destroy(Job $job): RedirectResponse
    {
        // check if the user is authorized
        $this->authorize('delete', $job);

        // Delete the company logo if it exists
        if ($job->company_logo) {
            Storage::disk('public')->delete($job->company_logo);
        }
        $job->delete();

        // Check if request came from the dashboard
        if (request()->query('from') == 'dashboard') {
            return redirect()->route('dashboard')->with('success', 'Job listing deleted successfully!');
        }

        return redirect()->route('jobs.index')->with('success', 'Job listing deleted successfully!');
    }
    // public function share() {
    //     return 'Share';
    // }

    // @desc Search for job listings
    // @route GET /jobs/search
    public function search(Request $request): View
    {
        $validatedData = $request->validate([
            'keywords' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
        ]);

        $keywords = strtolower($validatedData['keywords'] ?? '');
        $location = strtolower($validatedData['location'] ?? '');

        $query = Job::query();

        if ($keywords !== '') {
            $query->where(function ($q) use ($keywords) {
                $q->whereRaw('LOWER(title) like ?', ['%'.$keywords.'%'])
                    ->orWhereRaw('LOWER(description) like ?', ['%'.$keywords.'%'])
                    ->orWhereRaw('LOWER(tags) like ?', ['%'.$keywords.'%']);
            });
        }

        if ($location !== '') {
            $query->where(function ($q) use ($location) {
                $q->whereRaw('LOWER(address) like ?', ['%'.$location.'%'])
                    ->orWhereRaw('LOWER(city) like ?', ['%'.$location.'%'])
                    ->orWhereRaw('LOWER(state) like ?', ['%'.$location.'%'])
                    ->orWhereRaw('LOWER(zipcode) like ?', ['%'.$location.'%']);
            });
        }

        $jobs = $query->paginate(12)->withQueryString();

        return view('jobs.index')->with('jobs', $jobs);

    }
}
