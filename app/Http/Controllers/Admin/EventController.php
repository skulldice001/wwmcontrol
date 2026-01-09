<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EventController extends Controller
{
    public function index()
    {
        return response()->json(Event::with(['creator:id,name', 'participants:id,name'])->get());
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin($request);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', Rule::in(['pvp', 'casual'])],
            'rules' => ['nullable', 'string'],
            'rewards' => ['nullable', 'string'],
            'start_time' => ['required', 'date'],
            'end_time' => ['nullable', 'date', 'after_or_equal:start_time'],
            'location' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['upcoming', 'ongoing', 'completed', 'cancelled'])],
            'participant_ids' => ['nullable', 'array'],
            'participant_ids.*' => ['exists:users,id'],
        ]);

        $validated['created_by'] = $request->user()->id;

        $event = Event::create($validated);

        if (!empty($validated['participant_ids'])) {
            $event->participants()->sync($validated['participant_ids']);
        }

        return response()->json($event->load('participants'), 201);
    }

    public function show(Event $event)
    {
        return response()->json($event->load(['creator:id,name', 'participants:id,name']));
    }

    public function update(Request $request, Event $event)
    {
        $this->authorizeAdmin($request);

        $validated = $request->validate([
            'title' => ['string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => [Rule::in(['pvp', 'casual'])],
            'rules' => ['nullable', 'string'],
            'rewards' => ['nullable', 'string'],
            'start_time' => ['date'],
            'end_time' => ['nullable', 'date', 'after_or_equal:start_time'],
            'location' => ['nullable', 'string', 'max:255'],
            'status' => [Rule::in(['upcoming', 'ongoing', 'completed', 'cancelled'])],
            'participant_ids' => ['nullable', 'array'],
            'participant_ids.*' => ['exists:users,id'],
        ]);

        $event->update($validated);

        if (isset($validated['participant_ids'])) {
            $event->participants()->sync($validated['participant_ids']);
        }

        return response()->json($event->load('participants'));
    }

    public function destroy(Request $request, Event $event)
    {
        $this->authorizeAdmin($request);

        $event->delete();

        return response()->json(null, 204);
    }

    protected function authorizeAdmin(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            abort(403, 'Only Master and Admin can perform this action.');
        }
    }
}
