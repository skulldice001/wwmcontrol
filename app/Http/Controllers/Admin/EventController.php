<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class EventController extends Controller
{
    public function index()
    {
        return response()->json(Event::with(['creator:id,name', 'participants:id,name,ingame_name,ingame_id,main_skill_id,sub_skill_id', 'participants.mainSkill', 'participants.subSkill'])->get()->map(fn($e) => $this->formatEvent($e)));
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin($request);

        $this->prepareGuildWarData($request);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', Rule::in(['casual', 'guild_war'])],
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

        if ($validated['type'] === 'guild_war') {
            $startTime = Carbon::parse($validated['start_time']);
            $startOfWeek = $startTime->copy()->startOfWeek();
            $endOfWeek = $startTime->copy()->endOfWeek();

            $exists = Event::where('type', 'guild_war')
                ->whereBetween('start_time', [$startOfWeek, $endOfWeek])
                ->exists();

            if ($exists) {
                return response()->json(['message' => 'Đã có sự kiện Bang chiến trong tuần này.'], 422);
            }
        }

        $event = Event::create($validated);

        if (!empty($validated['participant_ids'])) {
            $event->participants()->sync($validated['participant_ids']);
        }

        return response()->json($this->formatEvent($event->load(['creator:id,name', 'participants:id,name,ingame_name,ingame_id,main_skill_id,sub_skill_id', 'participants.mainSkill', 'participants.subSkill'])), 201);
    }

    public function show(Event $event)
    {
        return response()->json($this->formatEvent($event->load(['creator:id,name', 'participants:id,name,ingame_name,ingame_id,main_skill_id,sub_skill_id', 'participants.mainSkill', 'participants.subSkill'])));
    }

    public function update(Request $request, Event $event)
    {
        $this->authorizeAdmin($request);

        $this->prepareGuildWarData($request, $event);

        $validated = $request->validate([
            'title' => ['string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => [Rule::in(['casual', 'guild_war'])],
            'rules' => ['nullable', 'string'],
            'rewards' => ['nullable', 'string'],
            'start_time' => ['date'],
            'end_time' => ['nullable', 'date', 'after_or_equal:start_time'],
            'location' => ['nullable', 'string', 'max:255'],
            'status' => [Rule::in(['upcoming', 'ongoing', 'completed', 'cancelled'])],
            'participant_ids' => ['nullable', 'array'],
            'participant_ids.*' => ['exists:users,id'],
        ]);

        $type = $validated['type'] ?? $event->type;
        $startTimeStr = $validated['start_time'] ?? $event->start_time;

        if ($type === 'guild_war') {
            $startTime = Carbon::parse($startTimeStr);
            $startOfWeek = $startTime->copy()->startOfWeek();
            $endOfWeek = $startTime->copy()->endOfWeek();

            $exists = Event::where('type', 'guild_war')
                ->where('id', '!=', $event->id)
                ->whereBetween('start_time', [$startOfWeek, $endOfWeek])
                ->exists();

            if ($exists) {
                return response()->json(['message' => 'Đã có sự kiện Bang chiến trong tuần này.'], 422);
            }
        }

        $event->update($validated);

        if (isset($validated['participant_ids'])) {
            $event->participants()->sync($validated['participant_ids']);
        }

        return response()->json($this->formatEvent($event->load(['creator:id,name', 'participants:id,name,ingame_name,ingame_id,main_skill_id,sub_skill_id', 'participants.mainSkill', 'participants.subSkill'])));
    }

    public function destroy(Request $request, Event $event)
    {
        $this->authorizeAdmin($request);

        $event->delete();

        return response()->json(null, 204);
    }

    protected function formatEvent(Event $event)
    {
        $data = $event->toArray();
        $data['creator'] = $event->creator ? $event->creator->only(['id', 'name']) : null;
        $data['participants'] = $event->participants->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'ingame_name' => $user->ingame_name,
                'ingame_id' => $user->ingame_id,
                'main_skill' => $user->mainSkill ? $user->mainSkill->name : null,
                'sub_skill' => $user->subSkill ? $user->subSkill->name : null,
                'preferred_time' => $user->pivot ? $user->pivot->preferred_time : null
            ];
        })->toArray();

        return $data;
    }

    protected function prepareGuildWarData(Request $request, $existingEvent = null)
    {
        $type = $request->input('type', $existingEvent ? $existingEvent->type : null);

        if ($type === 'guild_war') {
            if (!$request->filled('start_time')) {
                if ($existingEvent && $existingEvent->type === 'guild_war') {
                    $saturday = Carbon::parse($existingEvent->start_time);
                } else {
                    $saturday = Carbon::now()->startOfWeek()->addDays(5)->setTime(20, 0);
                    $request->merge(['start_time' => $saturday->toDateTimeString()]);
                }
            } else {
                try {
                    $saturday = Carbon::parse($request->start_time);
                } catch (\Exception $e) {
                    $saturday = Carbon::now()->startOfWeek()->addDays(5)->setTime(20, 0);
                    $request->merge(['start_time' => $saturday->toDateTimeString()]);
                }
            }

            if (!$request->filled('title')) {
                $sunday = $saturday->copy()->addDay();
                $title = "Bang Chiến ngày " . $saturday->format('d/m') . " - " . $sunday->format('d/m');
                $request->merge(['title' => $title]);
            }
        }
    }

    protected function authorizeAdmin(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            abort(403, 'Only Master and Admin can perform this action.');
        }
    }
}
