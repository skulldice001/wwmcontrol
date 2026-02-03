<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserEventController extends Controller
{
    public function index(Request $request)
    {
        $events = Event::whereIn('status', ['upcoming', 'ongoing'])
            ->with(['participants' => function ($query) use ($request) {
                $query->where('users.id', $request->user()->id);
            }])
            ->orderBy('start_time', 'asc')
            ->get()
            ->map(function ($event) {
                $event->is_registered = $event->participants->isNotEmpty();
                if ($event->is_registered) {
                    $event->preferred_time = $event->participants->first()->pivot->preferred_time;
                }
                // Don't unset participants as we might need it for count or other logic in view, 
                // but for this specific logic it was unsetting relation. 
                // We can keep it or remove it. Let's keep it clean.
                // unset($event->participants); 
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
