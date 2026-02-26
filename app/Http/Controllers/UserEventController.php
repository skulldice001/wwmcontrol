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

                $event->is_placed = false;

                if ($event->type === 'guild_war') {
                    $formation = $event->formation_data ?? null;

                    // Check if user is placed on map
                    if ($formation) {
                        $members = $formation['members'] ?? [];
                        $groups = $formation['groups'] ?? [];

                        // Check placed members
                        if (collect($members)->contains('memberId', $userId)) {
                            $event->is_placed = true;
                        }

                        // Check placed groups if not found in members
                        if (!$event->is_placed) {
                            foreach ($groups as $group) {
                                if (in_array($userId, $group['memberIds'] ?? [])) {
                                    $event->is_placed = true;
                                    break;
                                }
                            }
                        }
                    }

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

    public function map(Request $request, Event $event)
    {
        if ($event->type !== 'guild_war') {
            return redirect()->route('events.index')->with('error', 'Chỉ có Bang chiến mới có bản đồ.');
        }

        $userId = $request->user()->id;
        $formation = $event->formation_data ?? null;

        if (!$formation) {
            return redirect()->route('events.index')->with('error', 'Chưa có đội hình.');
        }

        $members = $formation['members'] ?? [];
        $groups = $formation['groups'] ?? [];
        $userPosition = null;

        // Check members
        $placedMember = collect($members)->firstWhere('memberId', $userId);
        if ($placedMember) {
            $userPosition = $placedMember;
        }

        // Check groups
        if (!$userPosition) {
            foreach ($groups as $group) {
                if (in_array($userId, $group['memberIds'] ?? [])) {
                    $userPosition = $group;
                    $userPosition['is_group'] = true;
                    break;
                }
            }
        }

        if (!$userPosition) {
            return redirect()->route('events.index')->with('error', 'Bạn chưa được xếp vị trí trên bản đồ.');
        }

        // Prepare static markers
        $staticMarkers = [
            'objectives' => $formation['objectives'] ?? [],
            'bosses' => $formation['bosses'] ?? [],
            'blueTowers' => $formation['blueTowers'] ?? [],
            'redTowers' => $formation['redTowers'] ?? [],
            'blueTrees' => $formation['blueTrees'] ?? [],
            'redTrees' => $formation['redTrees'] ?? [],
            'blueGeese' => $formation['blueGeese'] ?? [],
            'redGeese' => $formation['redGeese'] ?? [],
            'enemies' => $formation['enemies'] ?? [],
        ];

        // Team info
        $teamInfo = [
            'name' => 'Chưa có đội',
            'captain' => 'Chưa có',
        ];

        $roster = $formation['roster'] ?? null;
        if ($roster) {
            $assignments = $roster['assignments'] ?? [];
            $teams = $roster['teams'] ?? [];
            $teamId = $assignments[$userId] ?? null;

            if ($teamId) {
                $team = collect($teams)->firstWhere('id', $teamId);
                if ($team) {
                    $teamInfo['name'] = $team['name'];
                    if (!empty($team['captainId'])) {
                        $captain = User::find($team['captainId']);
                        $teamInfo['captain'] = $captain ? ($captain->ingame_name ?? $captain->name) : 'Chưa có';
                    }
                }
            }
        }

        // Pass user details for marker rendering
        $user = $request->user();
        $userPosition['name'] = $user->ingame_name ?? $user->name;
        // Check if mainSkill exists before accessing slug
        $userPosition['role'] = $user->mainSkill ? \App\Constants\SkillRole::getRole($user->mainSkill->slug) : 'Unknown';
        $userPosition['weapon1'] = $user->mainSkill->name ?? '';
        $userPosition['weapon2'] = $user->subSkill->name ?? '';

        // Filter drawings for this user
        $allDrawings = $formation['drawings'] ?? [];
        $userDrawings = [];
        if (!empty($allDrawings)) {
            foreach ($allDrawings as $drawing) {
                // If drawing has memberId, only show if it matches user
                if (isset($drawing['memberId'])) {
                    if ($drawing['memberId'] == $userId) {
                        $userDrawings[] = $drawing;
                    }
                }
                // Optional: Include global drawings (no memberId)?
                // For now, let's stick to user-specific as requested "chỉ hiển thị đường đã được vẽ" for the user
            }
        }

        return view('events.map', compact('event', 'userPosition', 'staticMarkers', 'teamInfo', 'userDrawings'));
    }
}
