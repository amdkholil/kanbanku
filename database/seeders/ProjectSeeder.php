<?php

namespace Database\Seeders;

use App\Models\Board;
use App\Models\Column;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::create([
            'name' => 'Demo User',
            'email' => 'demo@example.com',
            'password' => Hash::make('password'),
        ]);

        $project = Project::create([
            'name' => 'Main Project',
            'slug' => 'main-project',
            'description' => 'This is our main kanban project',
            'owner_id' => $user->id,
            'color' => '#4f46e5',
        ]);

        ProjectMember::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'role' => 'owner',
        ]);

        $board = Board::create([
            'project_id' => $project->id,
            'name' => 'Development Board',
            'slug' => 'dev-board',
            'is_default' => true,
            'position' => 0,
        ]);

        $columns = [
            ['name' => 'To Do', 'slug' => 'to-do', 'position' => 0, 'color' => '#94a3b8'],
            ['name' => 'In Progress', 'slug' => 'in-progress', 'position' => 1, 'color' => '#38bdf8'],
            ['name' => 'Review', 'slug' => 'review', 'position' => 2, 'color' => '#fbbf24'],
            ['name' => 'Done', 'slug' => 'done', 'position' => 3, 'color' => '#4ade80'],
        ];

        foreach ($columns as $colData) {
            $column = Column::create([
                'board_id' => $board->id,
                'name' => $colData['name'],
                'slug' => $colData['slug'],
                'position' => $colData['position'],
                'color' => $colData['color'],
            ]);

            if ($colData['slug'] === 'to-do') {
                Task::create([
                    'column_id' => $column->id,
                    'title' => 'Setup project structure',
                    'description' => 'Initialize laravel, inertia, and vue',
                    'position' => 0,
                    'priority' => 'high',
                    'status' => 'open',
                    'created_by' => $user->id,
                ]);
                Task::create([
                    'column_id' => $column->id,
                    'title' => 'Design database schema',
                    'description' => 'Create migrations based on plan.md',
                    'position' => 1,
                    'priority' => 'medium',
                    'status' => 'open',
                    'created_by' => $user->id,
                ]);
            }

            if ($colData['slug'] === 'in-progress') {
                Task::create([
                    'column_id' => $column->id,
                    'title' => 'Implement Kanban UI',
                    'description' => 'Build the board and task cards with Vue',
                    'position' => 0,
                    'priority' => 'urgent',
                    'status' => 'in_progress',
                    'created_by' => $user->id,
                ]);
            }
        }
    }
}
