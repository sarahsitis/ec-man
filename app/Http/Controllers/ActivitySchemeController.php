<?php

namespace App\Http\Controllers;

use App\Models\ActivityScheme;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ActivitySchemeController extends Controller {
    public function index() {
        return Inertia::render('ActivitySchemes/Index', ['schemes' => ActivityScheme::withCount('activities')->orderBy('name')->get()]);
    }

    private function data(Request $request): array {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'], 'category' => ['required', 'string', 'max:100'],
            'objectives' => ['required', 'string', 'max:5000'], 'agenda' => ['required', 'string', 'max:10000'],
            'duration_minutes' => ['required', 'integer', 'between:10,480'],
        ]);
    }

    public function store(Request $request) {
        ActivityScheme::create($this->data($request));
        return back()->with('success', 'Skema kegiatan ditambahkan.');
    }

    public function update(Request $request, ActivityScheme $activityScheme) {
        $activityScheme->update($this->data($request));
        return back()->with('success', 'Skema kegiatan diperbarui. Agenda kegiatan yang sudah dibuat tetap tersimpan.');
    }

    public function destroy(ActivityScheme $activityScheme) {
        $activityScheme->delete();
        return back()->with('success', 'Skema dihapus. Agenda kegiatan yang sudah dibuat tetap tersimpan.');
    }
}
