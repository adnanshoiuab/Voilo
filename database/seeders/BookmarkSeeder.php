<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Job;
use App\Models\User;

class BookmarkSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get test user
        $testUser = User::where('email', 'test@test.com')->firstOrFail();

        // Get all jobs
        $jobIds = Job::pluck('id')->toArray();

        // Randomly select job IDs to bookmark
        // $randomJobIds = array_rand($jobIds, 3);
        $randomKeys = array_rand($jobIds, 3);
        $randomJobIds = array_map(fn ($key) => $jobIds[$key], (array) $randomKeys);

        // Attach the selected jobs as bookmarks for the test user
        // foreach ($randomJobIds as $jobId) {
        //     $testUser->bookmarkJobs()->attach($jobIds[$jobId]);
        // }
        $testUser->bookmarkedJobs()->attach($randomJobIds);
    }
}
