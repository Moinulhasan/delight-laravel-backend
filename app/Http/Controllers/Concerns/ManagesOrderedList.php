<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** What the drag-to-reorder lists share: a page of rows, and saving a new order within that page. */
trait ManagesOrderedList
{
    /**
     * The page's rows take the same set of order numbers in the new sequence, so rows on other
     * pages keep their place.
     */
    private function saveOrder(Request $request, string $model): JsonResponse
    {
        $table = (new $model)->getTable();

        $ids = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'distinct', "exists:{$table},id"],
        ])['ids'];

        DB::transaction(function () use ($ids, $model) {
            $orders = $model::whereKey($ids)->orderBy('sort_order')->pluck('sort_order')->all();

            foreach ($ids as $index => $id) {
                $model::whereKey($id)->update(['sort_order' => $orders[$index]]);
            }
        });

        return response()->json(['message' => 'Order saved.']);
    }

    /** After deleting the last row of the last page, go to the new last page instead of an empty one. */
    private function pageOverflow(LengthAwarePaginator $rows, Request $request): ?RedirectResponse
    {
        if ($rows->isNotEmpty() || $request->integer('page') <= 1) {
            return null;
        }

        // The "deleted" message was meant for this request; keep it for the page it ends up on.
        session()->reflash();

        return redirect($rows->url(max(1, $rows->lastPage())));
    }

    /** The page reloads after these, and the layout shows this message as a toast. */
    private function done(string $message): JsonResponse
    {
        session()->flash('status', $message);

        return response()->json(['message' => $message]);
    }
}
