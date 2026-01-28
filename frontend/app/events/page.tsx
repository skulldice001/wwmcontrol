"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import api from "@/lib/axios";
import Navbar from "@/components/Navbar";
import { useNotification } from "@/context/NotificationContext";

export default function Events() {
  const router = useRouter();
  const { showNotification, showConfirm } = useNotification();
  const [user, setUser] = useState<any>(null);
  const [innerWays, setInnerWays] = useState<any[]>([]);
  const [events, setEvents] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [registeringId, setRegisteringId] = useState<number | null>(null);
  const [preferredTimes, setPreferredTimes] = useState<Record<number, string>>({});

  const fetchData = async () => {
    try {
      const [userResponse, innerWaysResponse, eventsResponse] = await Promise.all([
        api.get("/user"),
        api.get("/user/inner-ways"),
        api.get("/events")
      ]);
      setUser(userResponse.data);
      setInnerWays(innerWaysResponse.data.inner_ways || []);
      setEvents(eventsResponse.data);

      // Initialize preferred times from registered events
      const times: Record<number, string> = {};
      eventsResponse.data.forEach((e: any) => {
        if (e.preferred_time) {
          times[e.id] = e.preferred_time;
        } else if (e.type === 'guild_war') {
          times[e.id] = "19:00 - 21:00"; // Default range
        }
      });
      setPreferredTimes(times);
    } catch (error) {
      router.push("/");
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchData();
  }, [router]);

  const handleRegister = async (eventId: number) => {
    setRegisteringId(eventId);
    try {
      const event = events.find(e => e.id === eventId);
      const data: any = {};
      if (event?.type === 'guild_war') {
        data.preferred_time = preferredTimes[eventId] || "20:00";
      }

      await api.post(`/events/${eventId}/register`, data);
      await fetchData();
      showNotification("Báo danh thành công!", { type: "success" });
    } catch (error: any) {
      showNotification(error.response?.data?.message || "Báo danh thất bại", { type: "error" });
    } finally {
      setRegisteringId(null);
    }
  };

  const handleUnregister = (eventId: number) => {
    showConfirm({
      message: "Bạn có chắc chắn muốn hủy báo danh?",
      onConfirm: async () => {
        setRegisteringId(eventId);
        try {
          await api.post(`/events/${eventId}/unregister`);
          await fetchData();
          showNotification("Đã hủy báo danh", { type: "success" });
        } catch (error: any) {
          showNotification(error.response?.data?.message || "Hủy báo danh thất bại", { type: "error" });
        } finally {
          setRegisteringId(null);
        }
      }
    });
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-screen bg-[#FDFDFC] dark:bg-[#0a0a0a]">
        <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-[#f53003]"></div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] dark:text-[#EDEDEC]">
      <Navbar user={user} innerWays={innerWays} onInnerWaysUpdate={setInnerWays} onUserUpdate={setUser} />

      <main className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div className="flex items-center justify-between mb-12">
          <div>
            <h1 className="text-4xl font-black tracking-tight mb-2">Sự kiện</h1>
            <p className="text-[#706f6c] dark:text-[#A1A09A]">Tham gia các sự kiện để nhận phần thưởng hấp dẫn.</p>
          </div>
          <div className="text-5xl">📅</div>
        </div>

        <div className="space-y-6">
          {events.length === 0 ? (
            <div className="bg-white dark:bg-[#161615] rounded-3xl border border-[#1914001a] dark:border-[#fffaed1a] p-16 text-center shadow-sm">
              <div className="text-6xl mb-6 opacity-20 grayscale">📅</div>
              <h2 className="text-2xl font-bold mb-2">Chưa có event nào</h2>
              <p className="text-[#706f6c] dark:text-[#A1A09A]">
                Hiện tại không có sự kiện nào đang diễn ra. Hãy quay lại sau nhé!
              </p>
            </div>
          ) : (
            events.map((event) => (
              <div key={event.id} className="bg-white dark:bg-[#161615] rounded-3xl border border-[#1914001a] dark:border-[#fffaed1a] overflow-hidden shadow-sm hover:shadow-md transition-shadow">
                <div className="p-8">
                  <div className="flex flex-col md:flex-row md:items-start justify-between gap-6">
                    <div className="flex-1 space-y-4">
                      <div className="flex items-center gap-3">
                        <span className={`px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider ${
                          event.type === 'guild_war' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' : 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'
                        }`}>
                          {event.type === 'guild_war' ? 'Bang chiến' : 'Casual'}
                        </span>
                        <span className={`px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider ${
                          event.status === 'upcoming' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'
                        }`}>
                          {event.status}
                        </span>
                      </div>

                      <h3 className="text-2xl font-black leading-tight">{event.title}</h3>

                      <p className="text-[#706f6c] dark:text-[#A1A09A] whitespace-pre-wrap">{event.description || 'Không có mô tả.'}</p>

                      {event.type !== 'guild_war' && (
                        <div className="flex items-center gap-2 text-sm font-bold text-[#706f6c] dark:text-[#A1A09A]">
                          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                          Bắt đầu: {new Date(event.start_time).toLocaleString('vi-VN')}
                        </div>
                      )}
                    </div>

                    <div className="md:w-64 flex flex-col gap-4">
                      {event.type === 'guild_war' && !event.is_registered && (
                        <div className="space-y-2">
                          <label className="text-[10px] font-black uppercase tracking-widest text-[#706f6c] dark:text-[#A1A09A]">Khung giờ mong muốn</label>
                          <div className="flex items-center gap-2">
                            <select
                              className="flex-1 bg-[#FDFDFC] dark:bg-[#0a0a0a] border border-[#1914001a] dark:border-[#fffaed1a] rounded-xl px-2 py-2 text-xs font-bold focus:outline-none focus:ring-2 focus:ring-[#f53003]"
                              value={preferredTimes[event.id]?.split(' - ')[0] || "19:00"}
                              onChange={(e) => {
                                const start = e.target.value;
                                let end = preferredTimes[event.id]?.split(' - ')[1] || "21:00";
                                if (end <= start) {
                                  const nextTime = ["20:00", "21:00", "22:00", "23:00"].find(t => t > start);
                                  if (nextTime) end = nextTime;
                                }
                                setPreferredTimes(prev => ({ ...prev, [event.id]: `${start} - ${end}` }));
                              }}
                            >
                              {["19:00", "20:00", "21:00", "22:00"].map(t => <option key={t} value={t}>{t}</option>)}
                            </select>
                            <span className="text-[10px] font-bold text-[#706f6c]">đến</span>
                            <select
                              className="flex-1 bg-[#FDFDFC] dark:bg-[#0a0a0a] border border-[#1914001a] dark:border-[#fffaed1a] rounded-xl px-2 py-2 text-xs font-bold focus:outline-none focus:ring-2 focus:ring-[#f53003]"
                              value={preferredTimes[event.id]?.split(' - ')[1] || "21:00"}
                              onChange={(e) => {
                                const start = preferredTimes[event.id]?.split(' - ')[0] || "19:00";
                                setPreferredTimes(prev => ({ ...prev, [event.id]: `${start} - ${e.target.value}` }));
                              }}
                            >
                              {["20:00", "21:00", "22:00", "23:00"].filter(t => t > (preferredTimes[event.id]?.split(' - ')[0] || "19:00")).map(t => <option key={t} value={t}>{t}</option>)}
                            </select>
                          </div>
                        </div>
                      )}

                      {event.is_registered ? (
                        <div className="space-y-4">
                          <div className="bg-green-50 dark:bg-green-900/10 border border-green-100 dark:border-green-900/30 rounded-2xl p-4 flex items-center justify-between">
                            <div className="text-green-600 dark:text-green-400 font-black text-xs uppercase">Đã báo danh</div>
                            {event.preferred_time && (
                              <div className="text-[10px] text-green-500 font-bold">Giờ: {event.preferred_time}</div>
                            )}
                          </div>
                          {event.status === 'upcoming' && (
                            <button
                              disabled={registeringId === event.id}
                              onClick={() => handleUnregister(event.id)}
                              className="w-full py-3 rounded-2xl bg-white dark:bg-[#161615] border border-red-100 dark:border-red-900/30 text-red-600 dark:text-red-400 font-black text-sm hover:bg-red-50 dark:hover:bg-red-900/10 transition-colors disabled:opacity-50"
                            >
                              Hủy báo danh
                            </button>
                          )}
                        </div>
                      ) : (
                        <button
                          disabled={registeringId === event.id}
                          onClick={() => handleRegister(event.id)}
                          className="w-full py-4 rounded-2xl bg-[#f53003] text-white font-black text-sm hover:bg-[#d62a02] transition-colors shadow-lg shadow-[#f5300326] disabled:opacity-50"
                        >
                          {registeringId === event.id ? "Đang xử lý..." : "Báo danh tham gia"}
                        </button>
                      )}
                    </div>
                  </div>
                </div>
              </div>
            ))
          )}
        </div>
      </main>
    </div>
  );
}
