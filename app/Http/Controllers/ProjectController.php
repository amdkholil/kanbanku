<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Board;
use App\Models\Column;
use App\Models\ProjectMember;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProjectController extends Controller
{
    public function index()
    {
        $projects = Project::where('owner_id', Auth::id())
            ->orWhereHas('members', function ($query) {
                $query->where('user_id', Auth::id());
            })
            ->latest()
            ->get();

        return Inertia::render('Projects/Index', [
            'projects' => $projects
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'nullable|string|size:7',
        ]);

        return DB::transaction(function () use ($validated) {
            $project = Project::create([
                'name' => $validated['name'],
                'slug' => Str::slug($validated['name']) . '-' . Str::random(5),
                'description' => $validated['description'],
                'owner_id' => Auth::id(),
                'color' => $validated['color'] ?? '#4f46e5',
            ]);

            ProjectMember::create([
                'project_id' => $project->id,
                'user_id' => Auth::id(),
                'role' => 'owner',
            ]);

            $board = Board::create([
                'project_id' => $project->id,
                'name' => 'Main Board',
                'slug' => 'main-board',
                'is_default' => true,
                'position' => 0,
            ]);

            $columns = [
                ['name' => 'To Do', 'slug' => 'to-do', 'position' => 0, 'color' => '#94a3b8'],
                ['name' => 'In Progress', 'slug' => 'in-progress', 'position' => 1, 'color' => '#38bdf8'],
                ['name' => 'Review', 'slug' => 'review', 'position' => 2, 'color' => '#fbbf24'],
                ['name' => 'Done', 'slug' => 'done', 'position' => 3, 'color' => '#4ade80'],
            ];

            foreach ($columns as $column) {
                Column::create([
                    'board_id' => $board->id,
                    'name' => $column['name'],
                    'slug' => $column['slug'],
                    'position' => $column['position'],
                    'color' => $column['color'],
                ]);
            }

            return redirect()->route('projects.show', $project->slug);
        });
    }

    public function show(Project $project)
    {
        $project->load(['boards' => function ($query) {
            $query->where('is_default', true)->with(['columns' => function ($q) {
                $q->orderBy('position')->with(['tasks' => function ($tq) {
                    $tq->orderBy('position')->with(['comments.user']);
                }]);
            }]);
        }]);

        $board = $project->boards->first();

        return Inertia::render('Projects/Show', [
            'project' => $project,
            'board' => $board
        ]);
    }
}
