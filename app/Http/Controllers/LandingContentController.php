<?php

namespace App\Http\Controllers;

use App\Support\LandingContent;
use App\Support\LandingVisits;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LandingContentController extends Controller
{
    public function edit(): View
    {
        return view('landing-content.edit', [
            'sections' => LandingContent::sections(),
            'overrides' => LandingContent::overrides(),
            'stats' => LandingVisits::summary(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'content' => ['required', 'array', 'max:500'],
            'content.*' => ['nullable', 'string', 'max:3000'],
        ]);
        // Keys contain dots (e.g. hero.title), so read the raw array instead of dot-notation validated data.
        $content = (array) $request->input('content', []);
        $changed = LandingContent::save($content);

        return redirect()->route('landing-content.edit', ['tab' => $request->input('tab')])
            ->with('success', $changed ? "Saved — {$changed} ".str('text')->plural($changed).' updated on the live website.' : 'No changes to save.');
    }
}
