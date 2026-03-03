<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Skill;
use App\Models\InnerWay;
use App\Constants\SkillRole;

class EventController extends Controller
{
    public function index()
    {
        $events = Event::with(['creator'])->withCount('participants')->get();
        return view('admin.events.index', compact('events'));
    }

    public function participants(Request $request, Event $event)
    {
        $query = $event->participants()->with(['innerWays', 'mainSkill', 'subSkill']);

        if ($request->filled('main_skill_id')) {
            $query->where('main_skill_id', $request->main_skill_id);
        }

        if ($request->filled('sub_skill_id')) {
            $query->where('sub_skill_id', $request->sub_skill_id);
        }

        if ($request->filled('inner_way_id')) {
            $query->whereHas('innerWays', function ($q) use ($request) {
                $q->where('inner_ways.id', $request->inner_way_id);
                if ($request->filled('inner_way_level')) {
                    $q->where('user_inner_way.level', '>=', $request->inner_way_level);
                }
            });
        }

        if ($request->filled('role')) {
            $role = $request->role;
            $query->whereHas('mainSkill', function ($q) use ($role) {
                $q->whereIn('slug', \App\Constants\SkillRole::getSlugsByRole($role));
            });
        }

        $totalParticipants = $event->participants()->count();
        $participants = $query->get();
        $skills = Skill::all();
        $innerWays = InnerWay::all();

        return view('admin.events.participants', compact('event', 'participants', 'skills', 'innerWays', 'totalParticipants'));
    }

    public function addParticipantsForm(Event $event)
    {
        // Get users who are not already participants
        $participantsIds = $event->participants()->pluck('users.id')->toArray();
        $users = User::with(['mainSkill', 'subSkill', 'innerWays'])
            ->whereNotIn('id', $participantsIds)
            ->get();

        return view('admin.events.add_participants', compact('event', 'users'));
    }

    public function addParticipants(Request $request, Event $event)
    {
        $validated = $request->validate([
            'participant_ids' => ['required', 'array'],
            'participant_ids.*' => ['exists:users,id'],
        ]);

        $event->participants()->syncWithoutDetaching($validated['participant_ids']);

        return redirect()->route('admin.events.index')->with('success', __('messages.add_participants_success'));
    }

    public function saveFormation(Request $request, Event $event)
    {
        if ($event->type !== 'guild_war') {
            return response()->json(['error' => 'Invalid event type'], 400);
        }

        if (in_array($event->status, ['completed', 'cancelled'])) {
            return response()->json(['error' => 'Cannot modify formation of a completed or cancelled event'], 400);
        }

        $validated = $request->validate([
            'formation_data' => 'required|array',
        ]);

        $event->update(['formation_data' => $validated['formation_data']]);

        return response()->json(['success' => true]);
    }

    public function formation(Event $event)
    {
        if ($event->type !== 'guild_war') {
            return redirect()->route('admin.events.index')->with('error', 'Only Guild War events have formation.');
        }

        if (in_array($event->status, ['completed', 'cancelled'])) {
            return redirect()->route('admin.events.index')->with('error', 'Cannot manage formation of a completed or cancelled event.');
        }

        $participantsRaw = $event->participants()->with(['innerWays', 'mainSkill', 'subSkill'])->orderBy('users.id', 'asc')->get();

        $participants = $participantsRaw->map(function ($user) {
            return [
                'id' => $user->id,
                'discord_id' => $user->discord_id,
                'discord_avatar' => $user->discord_avatar,
                'account' => $user->name,
                'ingame_name' => $user->ingame_name,
                'name' => $user->ingame_name ?? $user->name,
                'role' => SkillRole::getRole($user->mainSkill->slug ?? null),
                'team' => 'Unassigned', // Default team
                'weapon1' => $user->mainSkill->name ?? '',
                'weapon2' => $user->subSkill->name ?? '',
            ];
        });

        $skills = Skill::all();
        $innerWays = InnerWay::all();

        // Get past events with formation data
        $pastEvents = Event::where('type', 'guild_war')
            ->where('id', '!=', $event->id)
            ->whereNotNull('formation_data')
            ->orderBy('start_time', 'desc')
            ->take(10)
            ->get(['id', 'title', 'start_time', 'formation_data']);

        return view('admin.events.formation', compact('event', 'participants', 'skills', 'innerWays', 'pastEvents'));
    }

    public function create()
    {
        $users = User::all();

        return view('admin.events.create', compact('users'));
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin($request);

        $this->prepareGuildWarData($request);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'discord_id' => ['nullable', 'string'],
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
            $baseTime = Carbon::parse($validated['start_time']);

            // Create Saturday Event
            $satTime = $baseTime->copy()->startOfWeek(Carbon::MONDAY)->addDays(5);
            // Preserve time from input
            $satTime->setTime($baseTime->hour, $baseTime->minute, $baseTime->second);

            $satData = $validated;
            $satData['title'] = $validated['title'] . ' - Thứ 7';
            $satData['start_time'] = $satTime;
            // Handle end_time if present - assume same duration
            if (!empty($validated['end_time'])) {
                $endTime = Carbon::parse($validated['end_time']);
                $duration = $baseTime->diffInMinutes($endTime);
                $satData['end_time'] = $satTime->copy()->addMinutes($duration);
            }

            $eventSat = Event::create($satData);
            if (!empty($validated['participant_ids'])) {
                $eventSat->participants()->sync($validated['participant_ids']);
            }

            // Create Sunday Event
            $sunTime = $baseTime->copy()->startOfWeek(Carbon::MONDAY)->addDays(6);
            $sunTime->setTime($baseTime->hour, $baseTime->minute, $baseTime->second);

            $sunData = $validated;
            $sunData['title'] = $validated['title'] . ' - Chủ Nhật (' . $sunTime->format('d/m') . ')';
            $sunData['start_time'] = $sunTime;
             if (!empty($validated['end_time'])) {
                $endTime = Carbon::parse($validated['end_time']);
                $duration = $baseTime->diffInMinutes($endTime);
                $sunData['end_time'] = $sunTime->copy()->addMinutes($duration);
            }

            $eventSun = Event::create($sunData);
            if (!empty($validated['participant_ids'])) {
                $eventSun->participants()->sync($validated['participant_ids']);
            }

            return redirect()->route('admin.events.index')->with('success', 'Đã tạo sự kiện Bang chiến cho Thứ 7 và Chủ Nhật thành công.');
        }

        $event = Event::create($validated);

        if (!empty($validated['participant_ids'])) {
            $event->participants()->sync($validated['participant_ids']);
        }

        return redirect()->route('admin.events.index')->with('success', 'Event created successfully.');
    }

    public function edit(Event $event)
    {
        if ($event->status === 'completed' || $event->status === 'cancelled') {
            return redirect()->route('admin.events.index')->with('error', 'Không thể sửa sự kiện đã kết thúc hoặc đã hủy.');
        }

        return view('admin.events.edit', compact('event'));
    }

    public function update(Request $request, Event $event)
    {
        $this->authorizeAdmin($request);

        if ($event->status === 'completed' || $event->status === 'cancelled') {
            return redirect()->route('admin.events.index')->with('error', 'Không thể sửa sự kiện đã kết thúc hoặc đã hủy.');
        }

        $this->prepareGuildWarData($request, $event);

        $validated = $request->validate([
            'title' => ['string', 'max:255'],
            'description' => ['nullable', 'string'],
            'discord_id' => ['nullable', 'string'],
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
            // No longer checking for uniqueness per week
        }

        $event->update($validated);

        if (isset($validated['participant_ids'])) {
            $event->participants()->sync($validated['participant_ids']);
        }

        return redirect()->route('admin.events.index')->with('success', 'Event updated successfully.');
    }

    public function destroy(Request $request, Event $event)
    {
        $this->authorizeAdmin($request);

        if ($event->status === 'completed' || $event->status === 'cancelled') {
            return redirect()->route('admin.events.index')->with('error', 'Không thể xóa sự kiện đã kết thúc hoặc đã hủy.');
        }

        $event->delete();
        return redirect()->route('admin.events.index')->with('success', 'Event deleted successfully.');
    }

    public function complete(Request $request, Event $event)
    {
        $this->authorizeAdmin($request);

        if ($event->status === 'completed' || $event->status === 'cancelled') {
            return redirect()->route('admin.events.index')->with('error', 'Sự kiện đã kết thúc hoặc đã hủy.');
        }

        if (!$event->end_time) {
            $event->end_time = Carbon::now();
        }

        $event->status = 'completed';
        $event->save();

        return redirect()->route('admin.events.index')->with('success', 'Đã kết thúc sự kiện.');
    }

    protected function authorizeAdmin(Request $request)
    {
        if (!$request->user()->isAdmin() && !$request->user()->isMaster()) {
            abort(403, 'Only Admin or Master can perform this action.');
        }
    }

    protected function prepareGuildWarData(Request $request, Event $event = null)
    {
        if ($request->input('type') === 'guild_war') {
            $now = Carbon::now();

            // Determine next Saturday (include today if it's Saturday and before 19:30)
            if ($now->dayOfWeek === Carbon::SATURDAY && $now->hour < 19) {
                $saturday = $now->copy();
            } else {
                $saturday = $now->next(Carbon::SATURDAY);
            }

            $startTime = $saturday->copy()->setTime(19, 30, 0);
            $endTime = $saturday->copy()->addDay()->setTime(22, 30, 0); // Sunday 22:30

            // Format: "Guild war ngày [x - y] tháng z"
            // x: Saturday date, y: Sunday date, z: Month
            // Note: If month changes between Sat and Sun (e.g. 31 Jan - 1 Feb), handling might need to be specific,
            // but prompt says "tháng z", implying one month. Let's assume start month or format "x/z - y/z".
            // User request: "Guild war ngày [x - y] tháng z" -> implies single month.
            // If cross-month, maybe "Guild war ngày 30/1 - 1/2"?
            // Let's stick to the user's exact format for same month, and maybe adapt if different.
            // But strict adherence: "Guild war ngày 10 - 11 tháng 2"

            $satDay = $startTime->day;
            $sunDay = $endTime->day;
            $month = $startTime->month; // Use start month

            if ($startTime->month != $endTime->month) {
                 // Fallback for cross-month: "Guild war ngày 31/1 - 1/2"
                 $title = "Guild war ngày {$satDay}/{$startTime->month} - {$sunDay}/{$endTime->month}";
            } else {
                 $title = "Guild war ngày {$satDay} - {$sunDay} tháng {$month}";
            }

            $request->merge([
                'title' => $title,
                'start_time' => $startTime->format('Y-m-d H:i:s'),
                'end_time' => $endTime->format('Y-m-d H:i:s'),
            ]);
        }
    }

    private function formatEvent($event)
    {
        // ... (Keep or remove, mainly for API formatting)
    }
}
