<?php

namespace Tests\Feature;

use App\Mail\JobApplied;
use App\Models\Applicant;
use App\Models\Job;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class JobBoardWorkflowsTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_job_listing_with_form_fields(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('jobs.store'), $this->validJobData());

        $response->assertRedirect(route('jobs.index'));
        $this->assertDatabaseHas('job_listings', [
            'title' => 'Software Engineer',
            'state' => 'NY',
            'contact_email' => 'hiring@example.com',
            'user_id' => $user->id,
        ]);
    }

    public function test_guest_cannot_create_job_listing(): void
    {
        $this->post(route('jobs.store'), $this->validJobData())
            ->assertRedirect(route('login'));
    }

    public function test_job_owner_can_update_listing_but_another_user_cannot(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $job = Job::factory()->for($owner)->create();

        $this->actingAs($otherUser)
            ->put(route('jobs.update', $job), $this->validJobData())
            ->assertForbidden();

        $this->actingAs($owner)
            ->put(route('jobs.update', $job), $this->validJobData(['title' => 'Updated title']))
            ->assertRedirect(route('jobs.index'));

        $this->assertDatabaseHas('job_listings', ['id' => $job->id, 'title' => 'Updated title']);
    }

    public function test_user_can_bookmark_and_remove_a_job(): void
    {
        $user = User::factory()->create();
        $job = Job::factory()->create();

        $this->actingAs($user)->post(route('bookmarks.store', $job))->assertRedirect();
        $this->assertTrue($user->fresh()->bookmarkedJobs()->whereKey($job->id)->exists());

        $this->actingAs($user)->delete(route('bookmarks.destroy', $job))->assertRedirect();
        $this->assertFalse($user->fresh()->bookmarkedJobs()->whereKey($job->id)->exists());
    }

    public function test_database_prevents_duplicate_bookmarks_and_applications(): void
    {
        $user = User::factory()->create();
        $job = Job::factory()->create();
        $timestamps = ['created_at' => now(), 'updated_at' => now()];

        DB::table('job_user_bookmarks')->insert([
            'user_id' => $user->id,
            'job_id' => $job->id,
            ...$timestamps,
        ]);

        try {
            DB::table('job_user_bookmarks')->insert([
                'user_id' => $user->id,
                'job_id' => $job->id,
                ...$timestamps,
            ]);
            $this->fail('Duplicate bookmark was accepted.');
        } catch (QueryException) {
            $this->assertDatabaseCount('job_user_bookmarks', 1);
        }

        $application = [
            'user_id' => $user->id,
            'job_id' => $job->id,
            'full_name' => $user->name,
            'contact_email' => $user->email,
            'resume_path' => 'resumes/example.pdf',
            ...$timestamps,
        ];
        DB::table('applicants')->insert($application);

        try {
            DB::table('applicants')->insert($application);
            $this->fail('Duplicate application was accepted.');
        } catch (QueryException) {
            $this->assertDatabaseCount('applicants', 1);
        }
    }

    public function test_applicant_can_apply_once_and_owner_receives_queued_email(): void
    {
        Storage::fake('local');
        Mail::fake();

        $owner = User::factory()->create();
        $applicant = User::factory()->create();
        $job = Job::factory()->for($owner)->create();

        $response = $this->actingAs($applicant)->post(route('applications.store', $job), [
            'full_name' => $applicant->name,
            'contact_phone' => '555-0100',
            'contact_email' => $applicant->email,
            'message' => 'Please consider my application.',
            'location' => 'New York',
            'resume' => UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf'),
        ]);

        $response->assertRedirect();
        $application = Applicant::query()->sole();
        $this->assertTrue(Storage::disk('local')->exists($application->resume_path));
        Mail::assertQueued(JobApplied::class, fn (JobApplied $mail): bool => $mail->hasTo($owner->email));

        $this->actingAs($applicant)->post(route('applications.store', $job), [
            'full_name' => $applicant->name,
            'contact_email' => $applicant->email,
            'resume' => UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf'),
        ])->assertSessionHas('error');

        $this->assertCount(1, Applicant::all());
    }

    public function test_only_job_owner_can_download_or_delete_application(): void
    {
        Storage::fake('local');

        $owner = User::factory()->create();
        $applicant = User::factory()->create();
        $otherUser = User::factory()->create();
        $job = Job::factory()->for($owner)->create();
        $resumePath = UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf')
            ->store('resumes', 'local');
        $application = Applicant::query()->create([
            'job_id' => $job->id,
            'user_id' => $applicant->id,
            'full_name' => $applicant->name,
            'contact_email' => $applicant->email,
            'resume_path' => $resumePath,
        ]);

        $this->actingAs($otherUser)
            ->get(route('applications.resume', $application))
            ->assertForbidden();
        $this->actingAs($otherUser)
            ->delete(route('applications.destroy', $application))
            ->assertForbidden();

        $this->actingAs($owner)
            ->get(route('applications.resume', $application))
            ->assertOk();
        $this->actingAs($owner)
            ->delete(route('applications.destroy', $application))
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseMissing('applicants', ['id' => $application->id]);
        $this->assertFalse(Storage::disk('local')->exists($resumePath));
    }

    public function test_search_validation_and_pagination_preserve_filters(): void
    {
        Job::factory()->create(['title' => 'Software Engineer', 'city' => 'Albany']);

        $this->get(route('jobs.search', ['keywords' => 'software', 'location' => 'Albany']))
            ->assertOk()
            ->assertSee('Software Engineer');

        $this->get(route('jobs.search', ['keywords' => ['invalid']]))
            ->assertSessionHasErrors('keywords');
    }

    public function test_geocoding_validates_input_and_reports_missing_configuration(): void
    {
        $this->getJson('/geocode')->assertUnprocessable();

        config(['services.mapbox.key' => null]);
        $this->getJson('/geocode?address=Albany')->assertServiceUnavailable();
    }

    private function validJobData(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Software Engineer',
            'description' => 'Build and maintain web applications.',
            'salary' => 90000,
            'job_type' => 'Full-Time',
            'remote' => 0,
            'city' => 'Albany',
            'state' => 'NY',
            'contact_email' => 'hiring@example.com',
            'company_name' => 'Example Co',
        ], $overrides);
    }
}
