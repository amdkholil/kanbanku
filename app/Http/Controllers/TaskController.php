<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Column;
use App\Models\Flag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    public function move(Request $request)
    {
        $request->validate([
            'task_id' => 'required|exists:tasks,id',
            'column_id' => 'required|exists:columns,id',
            'position' => 'required|integer',
        ]);

        $task = Task::findOrFail($request->task_id);
        $oldColumnId = $task->column_id;
        $newColumnId = $request->column_id;
        $newPosition = $request->position;

        // If moving within the same column
        if ($oldColumnId == $newColumnId) {
            $this->reorderTasks($oldColumnId, $task->position, $newPosition, $task->id);
        } else {
            // If moving to a different column
            // Shift tasks down in old column
            Task::where('column_id', $oldColumnId)
                ->where('position', '>', $task->position)
                ->decrement('position');

            // Shift tasks up in new column
            Task::where('column_id', $newColumnId)
                ->where('position', '>=', $newPosition)
                ->increment('position');
        }

        $task->column_id = $newColumnId;
        $task->position = $newPosition;
        $task->save();

        return response()->json(['success' => true]);
    }

    protected function reorderTasks($columnId, $oldPos, $newPos, $taskId)
    {
        if ($oldPos < $newPos) {
            Task::where('column_id', $columnId)
                ->whereBetween('position', [$oldPos + 1, $newPos])
                ->decrement('position');
        } else {
            Task::where('column_id', $columnId)
                ->whereBetween('position', [$newPos, $oldPos - 1])
                ->increment('position');
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'column_id' => 'required|exists:columns,id',
            'flags' => 'nullable|string',
        ]);

        $maxPosition = Task::where('column_id', $validated['column_id'])->max('position');
        
        $task = Task::create([
            'title' => $validated['title'],
            'column_id' => $validated['column_id'],
            'position' => ($maxPosition !== null) ? $maxPosition + 1 : 0,
            'created_by' => Auth::id(),
            'priority' => 'medium',
            'status' => 'open',
        ]);

        if (!empty($validated['flags'])) {
            $flagNames = array_filter(array_map('trim', explode(',', $validated['flags'])));
            $flagIds = [];
            $colors = ['#b91c1c', '#1d4ed8', '#047857', '#b45309', '#6d28d9', '#be185d', '#0e7490', '#c2410c', '#4338ca', '#334155'];
            
            foreach ($flagNames as $name) {
                $flag = Flag::firstOrCreate(
                    ['name' => $name],
                    ['color' => $colors[array_rand($colors)]]
                );
                $flagIds[] = $flag->id;
            }
            $task->flags()->sync($flagIds);
        }

        return back();
    }

    public function update(Request $request, Task $task)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|in:low,medium,high,urgent',
            'status' => 'required|in:open,in_progress,completed,blocked',
            'due_date' => 'nullable|date',
            'flags' => 'nullable|string',
        ]);

        $task->update($validated);

        if (isset($validated['flags'])) {
            $flagNames = array_filter(array_map('trim', explode(',', $validated['flags'])));
            $flagIds = [];
            $colors = ['#b91c1c', '#1d4ed8', '#047857', '#b45309', '#6d28d9', '#be185d', '#0e7490', '#c2410c', '#4338ca', '#334155'];

            foreach ($flagNames as $name) {
                $flag = Flag::firstOrCreate(
                    ['name' => $name],
                    ['color' => $colors[array_rand($colors)]]
                );
                $flagIds[] = $flag->id;
            }
            $task->flags()->sync($flagIds);
        }

        return back();
    }

    public function destroy(Task $task)
    {
        $task->delete();

        return back();
    }

    public function bulkStore(Request $request, Column $column)
    {
        $validated = $request->validate([
            'tasks_text' => 'required|string',
        ]);

        $tasksTitles = array_filter(
            array_map('trim', explode("\n", $validated['tasks_text'])),
            fn($title) => !empty($title)
        );

        $maxPosition = Task::where('column_id', $column->id)->max('position') ?? -1;

        foreach ($tasksTitles as $index => $title) {
            Task::create([
                'title' => $title,
                'column_id' => $column->id,
                'position' => $maxPosition + $index + 1,
                'created_by' => Auth::id(),
                'priority' => 'medium',
                'status' => 'open',
            ]);
        }

        return back();
    }
}
