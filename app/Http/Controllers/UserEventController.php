<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserEventController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $events = Event::whereIn('status', ['upcoming', 'ongoing'])
            ->with(['participants' => function ($query) use ($userId) {
                $query->where('users.id', $userId);
            }])
            ->orderBy('start_time', 'asc')
            ->get()
            ->map(function ($event) use ($userId) {
                $event->is_registered = $event->participants->isNotEmpty();
                if ($event->is_registered) {
                    $event->preferred_time = $event->participants->first()->pivot->preferred_time;
                }

                if ($event->type === 'guild_war') {
                    $formation = $event->formation_data ?? null;
                    $roster = $formation['roster'] ?? null;

                    if ($roster && !empty($roster['assignments']) && !empty($roster['teams'])) {
                        $assignments = $roster['assignments'];
                        $teams = $roster['teams'];
                        $teamId = $assignments[$userId] ?? null;

                        if ($teamId) {
                            $team = collect($teams)->firstWhere('id', $teamId);

                            if ($team) {
                                $event->team_name = $team['name'] ?? null;
                                $event->team_mission = $team['description'] ?? null;

                                if (!empty($team['captainId'])) {
                                    $captain = User::find($team['captainId']);
                                    $event->team_captain_name = $captain ? ($captain->ingame_name ?? $captain->name) : null;
                                }
                            }
                        }
                    }
                }

                return $event;
            });

        return view('events.index', compact('events'));
    }

    public function register(Request $request, Event $event)
    {
        if ($event->status !== 'upcoming' && $event->status !== 'ongoing') {
            return redirect()->back()->with('error', 'Sự kiện này không còn nhận báo danh.');
        }

        $validated = $request->validate([
            'preferred_time' => [
                Rule::requiredIf($event->type === 'guild_war'),
                'nullable',
                'string',
                'max:20',
            ],
        ]);

        $user = $request->user();

        if ($event->participants()->where('user_id', $user->id)->exists()) {
            // Update if already registered
            $event->participants()->updateExistingPivot($user->id, [
                'preferred_time' => $validated['preferred_time'] ?? null
            ]);
            return redirect()->back()->with('success', 'Cập nhật báo danh thành công.');
        }

        $event->participants()->attach($user->id, [
            'preferred_time' => $validated['preferred_time'] ?? null
        ]);

        return redirect()->back()->with('success', 'Báo danh thành công.');
    }

    public function unregister(Request $request, Event $event)
    {
        if ($event->status !== 'upcoming') {
            return redirect()->back()->with('error', 'Không thể hủy báo danh khi sự kiện đã bắt đầu hoặc kết thúc.');
        }

        $event->participants()->detach($request->user()->id);

        return redirect()->back()->with('success', 'Đã hủy báo danh.');
    }
}
