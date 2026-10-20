<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Site;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SiteController extends Controller
{
    public function index(): View
    {
        $sites = Site::query()->withCount('visits')->orderBy('name')->get();

        return view('sites.index', compact('sites'));
    }

    public function create(): View
    {
        return view('sites.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'domain' => [
                'required',
                'string',
                'max:255',
                'unique:sites,domain',
            ],
        ]);

        Site::create($validated);

        return redirect()
            ->route('sites.index')
            ->with('success', 'Site ajouté avec succès.');
    }

    public function show(Site $site): View
    {
        return view('sites.show', compact('site'));
    }

    public function toggleActive(Site $site): RedirectResponse
    {
        $site->update(['is_active' => ! $site->is_active]);

        return redirect()
            ->route('sites.index')
            ->with('success', 'Statut du site modifié avec succès.');
    }

    public function destroy(Site $site): RedirectResponse
    {
        $site->delete();

        return redirect()
            ->route('sites.index')
            ->with('success', 'Site supprimé.');
    }
}
