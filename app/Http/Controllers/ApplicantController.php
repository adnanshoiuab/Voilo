<?php

namespace App\Http\Controllers;

use App\Mail\JobApplied;
use App\Models\Applicant;
use App\Models\Job;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApplicantController extends Controller
{
    // @desc Store a new job application
    // @route POST /jobs/{job}/apply
    public function store(Request $request, Job $job): RedirectResponse
    {
        $validatedData = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'contact_email' => ['required', 'string', 'email', 'max:255'],
            'message' => ['nullable', 'string', 'max:5000'],
            'location' => ['nullable', 'string', 'max:255'],
            'resume' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:2048'],
        ]);

        $userId = $request->user()->id;
        if (Applicant::where('job_id', $job->id)->where('user_id', $userId)->exists()) {
            return back()->with('error', 'You have already applied for this job.');
        }

        $resumePath = $request->file('resume')->store('resumes', 'local');
        try {
            $application = DB::transaction(function () use ($validatedData, $job, $userId, $resumePath): Applicant {
                return Applicant::create([
                    ...$validatedData,
                    'job_id' => $job->id,
                    'user_id' => $userId,
                    'resume_path' => $resumePath,
                ]);
            });
        } catch (QueryException $exception) {
            Storage::disk('local')->delete($resumePath);
            if (Applicant::where('job_id', $job->id)->where('user_id', $userId)->exists()) {
                return back()->with('error', 'You have already applied for this job.');
            }

            throw $exception;
        }

        if ($job->user) {
            Mail::to($job->user->email)->queue(new JobApplied($application, $job));
        }

        return redirect()->back()->with('success', 'Your application has been submitted successfully!');
    }

    public function resume(Request $request, Applicant $applicant): StreamedResponse
    {
        abort_unless($request->user()->id === $applicant->job->user_id, 403);

        abort_unless(Storage::disk('local')->exists($applicant->resume_path), 404);

        return Storage::disk('local')->download($applicant->resume_path);
    }

    public function destroy(Request $request, Applicant $applicant): RedirectResponse
    {
        abort_unless($request->user()->id === $applicant->job->user_id, 403);

        Storage::disk('local')->delete($applicant->resume_path);
        Storage::disk('public')->delete($applicant->resume_path);
        $applicant->delete();

        return redirect()->route('dashboard')->with('success', 'Applicant deleted successfully!');
    }
}
