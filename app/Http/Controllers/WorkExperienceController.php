<?php

namespace App\Http\Controllers;

use App\Models\WorkExperience;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WorkExperienceController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validatedData = $this->validatedData($request);
        $validatedData['is_current'] = $request->boolean('is_current');

        if ($validatedData['is_current']) {
            $validatedData['end_date'] = null;
        }

        $request->user()->workExperiences()->create($validatedData);

        return redirect()->route('dashboard')->with('success', 'Work experience added.');
    }

    public function update(Request $request, WorkExperience $workExperience): RedirectResponse
    {
        abort_unless($workExperience->user_id === $request->user()->id, 403);

        $validatedData = $this->validatedData($request);
        $validatedData['is_current'] = $request->boolean('is_current');

        if ($validatedData['is_current']) {
            $validatedData['end_date'] = null;
        }

        $workExperience->update($validatedData);

        return redirect()->route('dashboard')->with('success', 'Work experience updated.');
    }

    public function destroy(Request $request, WorkExperience $workExperience): RedirectResponse
    {
        abort_unless($workExperience->user_id === $request->user()->id, 403);

        $workExperience->delete();

        return redirect()->route('dashboard')->with('success', 'Work experience removed.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request): array
    {
        return $request->validate([
            'job_title' => ['required', 'string', 'max:255'],
            'company_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'is_current' => ['sometimes', 'boolean'],
        ]);
    }
}
