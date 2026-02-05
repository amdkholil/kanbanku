<?php

namespace App\Http\Controllers;

use App\Models\Column;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ColumnController extends Controller
{
    public function update(Request $request, Column $column)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'color' => 'sometimes|nullable|string|max:7',
        ]);

        if (isset($validated['name'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $column->update($validated);

        return back();
    }

    public function move(Request $request, Column $column)
    {
        $request->validate([
            'direction' => 'required|in:left,right',
        ]);

        $direction = $request->direction;
        $currentPosition = $column->position;
        $boardId = $column->board_id;

        if ($direction === 'left' && $currentPosition > 0) {
            $swapColumn = Column::where('board_id', $boardId)
                ->where('position', $currentPosition - 1)
                ->first();

            if ($swapColumn) {
                $swapColumn->update(['position' => $currentPosition]);
                $column->update(['position' => $currentPosition - 1]);
            }
        } elseif ($direction === 'right') {
            $swapColumn = Column::where('board_id', $boardId)
                ->where('position', $currentPosition + 1)
                ->first();

            if ($swapColumn) {
                $swapColumn->update(['position' => $currentPosition]);
                $column->update(['position' => $currentPosition + 1]);
            }
        }

        return back();
    }
}
