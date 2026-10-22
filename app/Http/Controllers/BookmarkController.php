<?php

namespace App\Http\Controllers;

use App\Models\Job;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class BookmarkController extends Controller
{
    // @desc  Get all users bookmarks
    // @route GET /bookmarks
    public function index(): View
    {
        $user = Auth::user();

        $bookmarks = $user->bookmarkedJobs()->orderBy('job_user_bookmarks.created_at', 'desc')->paginate(9);

        return view('jobs.bookmarked')->with('bookmarks', $bookmarks);
    }

    // @desc Create a new bookmark for a job
    // @route POST /bookmarks/{job}
    public function store(Job $job): RedirectResponse
    {
        $user = Auth::user();

        if ($user->bookmarkedJobs()->whereKey($job->id)->exists()) {
            return back()->with('error', 'Job is already bookmarked.');
        }

        $user->bookmarkedJobs()->syncWithoutDetaching([$job->id]);

        return back()->with('success', 'job bookmarked successfully.');
    }

    // @desc Remove a bookmark for a job
    // @route DELETE /bookmarks/{job}
    public function destroy(Job $job): RedirectResponse
    {
        $user = Auth::user();

        if (! $user->bookmarkedJobs()->whereKey($job->id)->exists()) {
            return back()->with('error', 'Job is not bookmarked.');
        }

        $user->bookmarkedJobs()->detach($job->id);

        return back()->with('success', 'Job removed from bookmarks.');
    }
}
