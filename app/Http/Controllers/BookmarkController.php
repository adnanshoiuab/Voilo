<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use App\Models\Job;



class BookmarkController extends Controller
{
    // @desc  Get all users bookmarks
    // @route GET /bookmarks
    public function index(): View{
        $user = Auth::user();

        $bookmarks = $user->bookmarkedJobs()->orderBy('job_user_bookmarks.created_at', 'desc')->paginate(9);
        return view('jobs.bookmarked')->with('bookmarks', $bookmarks);  
        
    }

    // @desc Create a new bookmark for a job
    // @route POST /bookmarks/{job}
    public function store(Job $job): RedirectResponse{
        $user = Auth::user();
        
        // Check if the job is already bookmarked
        if ($user->bookmarkedJobs()->where('job_id', $job->id)->exists()){
            return back()->with('error', 'Job is already bookmarked.');
        }
        // Create a new bookmark
        $user->bookmarkedJobs()->attach($job->id);

        return back()->with('success', 'job bookmarked successfully.');
    }

    // @desc Remove a bookmark for a job
    // @route DELETE /bookmarks/{job}
    public function destroy(Job $job): RedirectResponse{
        $user = Auth::user();

        // Check if the job is bookmarked
        if ($user->bookmarkedJobs()->where('job_id', $job->id)->exists()){
            
            return back()->with('error', 'Job is not bookmarked.');
        }
        // Remove the bookmark
        $user->bookmarkedJobs()->detach($job->id);

        return back()->with('success', 'Job removed from bookmarks.');
    }

}