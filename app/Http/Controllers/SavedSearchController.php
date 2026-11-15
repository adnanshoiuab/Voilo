<?php

namespace App\Http\Controllers;

use App\Models\SavedSearch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SavedSearchController extends Controller
{
    public function index(Request $request): View
    {
        $savedSearches = $request->user()->savedSearches()->latest()->paginate(10);

        return view('saved-searches.index', compact('savedSearches'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validatedData = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'keywords' => ['nullable', 'string', 'max:255', 'required_without:location'],
            'location' => ['nullable', 'string', 'max:255', 'required_without:keywords'],
            'digest_enabled' => ['sometimes', 'boolean'],
        ]);
        $validatedData['digest_enabled'] = $request->boolean('digest_enabled');

        $request->user()->savedSearches()->create($validatedData);

        return redirect()->route('saved-searches.index')->with('success', 'Search saved successfully.');
    }

    public function update(Request $request, SavedSearch $savedSearch): RedirectResponse
    {
        abort_unless($savedSearch->user_id === $request->user()->id, 404);

        $request->validate([
            'digest_enabled' => ['sometimes', 'boolean'],
        ]);
        $savedSearch->update([
            'digest_enabled' => $request->boolean('digest_enabled'),
        ]);

        return redirect()->route('saved-searches.index')->with('success', 'Digest preference updated.');
    }

    public function destroy(Request $request, SavedSearch $savedSearch): RedirectResponse
    {
        abort_unless($savedSearch->user_id === $request->user()->id, 404);

        $savedSearch->delete();

        return redirect()->route('saved-searches.index')->with('success', 'Search deleted.');
    }
}
