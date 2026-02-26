<?php

namespace App\Http\Controllers;

use App\Models\Notes;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class NoteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $notes = Notes::where('user_id', Auth::id())
            ->orderBy('id', 'desc')
            ->get();

        return Inertia::render('Notes/Index', [
            'notes' => $notes
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'content' => 'required|string',
            'is_favorite' => 'boolean',
            'hide_preview_content' => 'boolean',
            'tags' => 'nullable|array',
        ]);

        $note = Notes::create([
            'user_id' => Auth::id(),
            'title' => $validated['title'] ?? '',
            'content' => $validated['content'],
            'is_favorite' => $validated['is_favorite'] ?? false,
            'hide_preview_content' => $validated['hide_preview_content'] ?? false,
            'tags' => $validated['tags'] ?? [],
        ]);

        return redirect()->back()->with('success', 'Note created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Notes $note)
    {
        if ($note->user_id != Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'content' => 'required|string',
            'is_favorite' => 'boolean',
            'hide_preview_content' => 'boolean',
            'tags' => 'nullable|array',
        ]);

        $note->update($validated);

        return redirect()->back()->with('success', 'Note updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Notes $note)
    {
        if ($note->user_id != Auth::id()) {
            abort(403);
        }

        $note->delete();

        return redirect()->back()->with('success', 'Note deleted successfully.');
    }

    /**
     * Toggle favorite status.
     */
    public function toggleFavorite(Notes $note)
    {
        if ($note->user_id != Auth::id()) {
            abort(403);
        }

        $note->update([
            'is_favorite' => !$note->is_favorite
        ]);

        return redirect()->back();
    }

    /**
     * Toggle preview content visibility.
     */
    public function togglePreview(Notes $note)
    {
        if ($note->user_id != Auth::id()) {
            abort(403);
        }

        $note->update([
            'hide_preview_content' => !$note->hide_preview_content
        ]);

        return redirect()->back();
    }
}
