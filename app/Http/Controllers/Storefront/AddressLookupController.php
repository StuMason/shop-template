<?php

namespace App\Http\Controllers\Storefront;

use App\AddressLookup\AddressLookupManager;
use App\AddressLookup\Suggestion;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class AddressLookupController extends Controller
{
    public function __construct(
        private readonly AddressLookupManager $lookup,
    ) {}

    /**
     * Type-ahead suggestions. Provider failures degrade to an empty list —
     * the customer just types their address manually.
     */
    public function suggest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:3', 'max:120'],
            'country' => ['required', 'string', 'size:2'],
            'session' => ['required', 'string', 'max:40'],
        ]);

        if (! $this->lookup->enabled()) {
            return response()->json(['suggestions' => []]);
        }

        try {
            $suggestions = $this->lookup->driver()->suggest(
                $validated['q'],
                strtoupper($validated['country']),
                $validated['session'],
            );
        } catch (Throwable) {
            $suggestions = [];
        }

        return response()->json([
            'suggestions' => array_map(
                fn (Suggestion $suggestion): array => $suggestion->toArray(),
                $suggestions,
            ),
        ]);
    }

    /**
     * Resolve a suggestion to a full address snapshot.
     */
    public function resolve(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => ['required', 'string', 'max:512'],
            'session' => ['required', 'string', 'max:40'],
        ]);

        abort_unless($this->lookup->enabled(), 404);

        try {
            $address = $this->lookup->driver()->resolve($validated['id'], $validated['session']);
        } catch (Throwable) {
            $address = null;
        }

        abort_if($address === null, 404);

        return response()->json(['address' => $address->toArray()]);
    }
}
