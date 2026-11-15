<?php

namespace App\Console\Commands;

use App\Mail\WeeklyJobDigest;
use App\Models\Job;
use App\Models\SavedSearch;
use App\Services\JobSearch;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class SendWeeklyJobDigests extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-weekly-job-digests';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Queue weekly job digests for opted-in saved searches';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $weekStart = now()->subWeek()->startOfWeek();
        $weekEnd = $weekStart->copy()->endOfWeek();

        $searchesByUser = SavedSearch::query()
            ->with('user')
            ->where('digest_enabled', true)
            ->get()
            ->groupBy('user_id');

        foreach ($searchesByUser as $savedSearches) {
            $user = $savedSearches->first()->user;
            $jobs = $savedSearches
                ->flatMap(function (SavedSearch $savedSearch) use ($weekStart, $weekEnd): Collection {
                    return JobSearch::apply(
                        Job::query(),
                        $savedSearch->keywords ?? '',
                        $savedSearch->location ?? '',
                    )
                        ->whereBetween('created_at', [$weekStart, $weekEnd])
                        ->get();
                })
                ->unique('id')
                ->values();

            if ($jobs->isEmpty()) {
                continue;
            }

            try {
                DB::transaction(function () use ($user, $jobs, $weekStart): void {
                    DB::table('weekly_job_digest_deliveries')->insert([
                        'user_id' => $user->id,
                        'week_start' => $weekStart->toDateString(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    Mail::to($user->email)->queue(new WeeklyJobDigest($jobs));
                });
            } catch (QueryException $exception) {
                $alreadyDelivered = DB::table('weekly_job_digest_deliveries')
                    ->where('user_id', $user->id)
                    ->where('week_start', $weekStart->toDateString())
                    ->exists();

                if ($alreadyDelivered) {
                    continue;
                }

                throw $exception;
            }
        }

        return self::SUCCESS;
    }
}
