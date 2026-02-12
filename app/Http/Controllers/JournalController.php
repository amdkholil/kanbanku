<?php

namespace App\Http\Controllers;

use App\Models\Journal;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class JournalController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->query('date', now()->format('Y-m-d'));
        $month = date('m', strtotime($date));
        $year = date('Y', strtotime($date));
        
        $journal = Auth::user()->journals()
            ->where('entry_date', $date)
            ->first();

        $datesWithEntries = Auth::user()->journals()
            ->whereYear('entry_date', $year)
            ->whereMonth('entry_date', $month)
            ->pluck('entry_date')
            ->map(fn($date) => (int) date('j', strtotime($date)))
            ->toArray();

        return Inertia::render('Journals/Index', [
            'initialJournal' => $journal,
            'selectedDate' => $date,
            'datesWithEntries' => $datesWithEntries,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'entry_date' => 'required|date',
            'content' => 'nullable|string',
            'is_favorite' => 'boolean',
        ]);

        // Check if content is empty (strip HTML tags and whitespace)
        $contentText = strip_tags($validated['content'] ?? '');
        $isContentEmpty = empty(trim($contentText));

        // Find existing journal
        $existingJournal = Auth::user()->journals()
            ->where('entry_date', $validated['entry_date'])
            ->first();

        // If content is empty, delete the journal if it exists
        if ($isContentEmpty) {
            if ($existingJournal) {
                $existingJournal->delete();
                return back()->with('success', 'Journal deleted successfully.');
            }
            return back()->with('info', 'No journal to save.');
        }

        // If content is not empty, save or update the journal
        $journal = Auth::user()->journals()->updateOrCreate(
            ['entry_date' => $validated['entry_date']],
            [
                'content' => $validated['content'],
                'is_favorite' => $validated['is_favorite'] ?? false,
            ]
        );

        return back()->with('success', 'Journal saved successfully.');
    }

    public function getByDate($date)
    {
        $journal = Auth::user()->journals()
            ->where('entry_date', $date)
            ->first();

        return response()->json($journal);
    }

    public function getDatesByMonth(Request $request)
    {
        $month = $request->query('month');
        $year = $request->query('year');

        $dates = Auth::user()->journals()
            ->whereYear('entry_date', $year)
            ->whereMonth('entry_date', $month)
            ->pluck('entry_date')
            ->map(fn($date) => (int) date('j', strtotime($date)))
            ->toArray();

        return response()->json($dates);
    }
}
