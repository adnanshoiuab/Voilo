<?php

namespace Tests\Feature;

use App\Mail\WeeklyJobDigest;
use App\Models\Applicant;
use App\Models\Job;
use App\Models\SavedSearch;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CareerHubWorkflowsTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_owner_can_update_application_status_and_candidate_can_view_it(): void
    {
        $owner = User::factory()->create();
        $candidate = User::factory()->create();
        $otherUser = User::factory()->create();
        $job = Job::factory()->for($owner)->create();
        $application = $this->makeApplicant($job, $candidate);
        $otherJob = Job::factory()->for($owner)->create(['title' => 'Private application']);
        $this->makeApplicant($otherJob, $otherUser);
        $this->assertSame('Applied', $application->fresh()->status);

        $this->actingAs($owner)
            ->patch(route('applications.status.update', $application), ['status' => 'Interview'])
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('applicants', [
            'id' => $application->id,
            'status' => 'Interview',
        ]);

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Interview')
            ->assertSee(route('profiles.show', $candidate));

        $this->actingAs($otherUser)
            ->patch(route('applications.status.update', $application), ['status' => 'Offered'])
            ->assertForbidden();

        $this->actingAs($candidate)
            ->get(route('applications.index'))
            ->assertOk()
            ->assertSee($job->title)
            ->assertSee('Interview')
            ->assertDontSee('Private application');
    }

    public function test_application_status_rejects_values_outside_the_defined_pipeline(): void
    {
        $owner = User::factory()->create();
        $candidate = User::factory()->create();
        $application = $this->makeApplicant(Job::factory()->for($owner)->create(), $candidate);

        $this->actingAs($owner)
            ->patch(route('applications.status.update', $application), ['status' => 'Hired'])
            ->assertSessionHasErrors('status');
    }

    public function test_candidate_profile_respects_public_visibility_and_displays_work_history(): void
    {
        $privateCandidate = User::factory()->create([
            'headline' => 'Private profile',
            'profile_is_public' => false,
        ]);

        $this->get(route('profiles.show', $privateCandidate))->assertNotFound();
        $this->actingAs($privateCandidate)
            ->get(route('profiles.show', $privateCandidate))
            ->assertOk()
            ->assertSee('Private profile');

        $publicCandidate = User::factory()->create([
            'headline' => 'Senior PHP Engineer',
            'bio' => 'Builds reliable software.',
            'profile_is_public' => true,
        ]);
        $publicCandidate->workExperiences()->create([
            'job_title' => 'Engineer',
            'company_name' => 'Example Co',
            'start_date' => '2020-01-01',
            'is_current' => true,
        ]);

        $this->get(route('profiles.show', $publicCandidate))
            ->assertOk()
            ->assertSee('Senior PHP Engineer')
            ->assertSee('Builds reliable software.')
            ->assertSee('Example Co')
            ->assertDontSee($publicCandidate->email);
    }

    public function test_candidate_can_update_profile_and_manage_only_their_own_work_history(): void
    {
        $candidate = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($candidate)
            ->put(route('profile.update'), [
                'name' => $candidate->name,
                'email' => $candidate->email,
                'headline' => 'Product-minded developer',
                'bio' => 'I build web applications.',
                'github_url' => 'https://github.com/candidate',
                'portfolio_url' => 'https://candidate.example.com',
                'profile_is_public' => '1',
            ])
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('users', [
            'id' => $candidate->id,
            'headline' => 'Product-minded developer',
            'profile_is_public' => true,
        ]);

        $experience = $otherUser->workExperiences()->create([
            'job_title' => 'Designer',
            'company_name' => 'Other Co',
            'start_date' => '2020-01-01',
            'is_current' => true,
        ]);

        $this->actingAs($candidate)
            ->put(route('work-experiences.update', $experience), [
                'job_title' => 'Changed',
                'company_name' => 'Changed Co',
                'start_date' => '2020-01-01',
                'is_current' => '1',
            ])
            ->assertForbidden();

        $this->actingAs($candidate)
            ->post(route('work-experiences.store'), [
                'job_title' => 'Developer',
                'company_name' => 'Candidate Co',
                'start_date' => '2022-01-01',
                'end_date' => '2023-01-01',
            ])
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('work_experiences', [
            'user_id' => $candidate->id,
            'job_title' => 'Developer',
            'is_current' => false,
        ]);
    }

    public function test_saved_searches_are_private_and_weekly_digest_is_opt_in(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($owner)
            ->post(route('saved-searches.store'), [
                'name' => 'Remote PHP',
                'keywords' => 'PHP',
                'location' => 'Remote',
                'digest_enabled' => '1',
            ])
            ->assertRedirect(route('saved-searches.index'));

        $savedSearch = SavedSearch::query()->sole();
        $this->assertTrue($savedSearch->digest_enabled);

        $this->actingAs($owner)
            ->patch(route('saved-searches.update', $savedSearch))
            ->assertRedirect(route('saved-searches.index'));
        $this->assertFalse($savedSearch->fresh()->digest_enabled);
        $this->actingAs($owner)
            ->patch(route('saved-searches.update', $savedSearch), ['digest_enabled' => '1'])
            ->assertRedirect(route('saved-searches.index'));

        $this->actingAs($otherUser)
            ->get(route('saved-searches.index'))
            ->assertOk()
            ->assertDontSee('Remote PHP');

        $this->actingAs($otherUser)
            ->delete(route('saved-searches.destroy', $savedSearch))
            ->assertNotFound();

        $this->actingAs($owner)
            ->post(route('saved-searches.store'), [
                'name' => 'Empty search',
                'digest_enabled' => '1',
            ])
            ->assertSessionHasErrors(['keywords', 'location']);
    }

    public function test_weekly_digest_matches_saved_searches_deduplicates_jobs_and_is_idempotent(): void
    {
        $this->travelTo(Carbon::parse('2026-11-17 10:00:00'));
        Mail::fake();

        $candidate = User::factory()->create();
        $candidate->savedSearches()->create([
            'name' => 'PHP roles',
            'keywords' => 'php',
            'digest_enabled' => true,
        ]);
        $candidate->savedSearches()->create([
            'name' => 'Developer in London',
            'keywords' => 'developer',
            'location' => 'London',
            'digest_enabled' => true,
        ]);

        $matchingJob = Job::factory()->create([
            'title' => 'PHP Developer',
            'city' => 'London',
            'created_at' => now()->subDays(8),
        ]);
        Job::factory()->create([
            'title' => 'PHP Designer',
            'city' => 'Paris',
            'created_at' => now()->subDays(7),
        ]);
        Job::factory()->create([
            'title' => 'PHP Developer',
            'city' => 'London',
            'created_at' => now()->subDays(15),
        ]);

        $this->artisan('app:send-weekly-job-digests')->assertSuccessful();
        $this->artisan('app:send-weekly-job-digests')->assertSuccessful();

        Mail::assertQueued(WeeklyJobDigest::class, function (WeeklyJobDigest $mail) use ($matchingJob): bool {
            return $mail->jobs->count() === 2
                && $mail->jobs->contains('id', $matchingJob->id);
        });
        Mail::assertQueued(WeeklyJobDigest::class, 1);
        $this->assertDatabaseCount('weekly_job_digest_deliveries', 1);
    }

    private function makeApplicant(Job $job, User $candidate): Applicant
    {
        return Applicant::query()->create([
            'job_id' => $job->id,
            'user_id' => $candidate->id,
            'full_name' => $candidate->name,
            'contact_email' => $candidate->email,
            'resume_path' => 'resumes/example.pdf',
        ]);
    }
}
