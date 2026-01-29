"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import api from "@/lib/axios";
import { useNotification } from "@/context/NotificationContext";

export default function StaffDashboardPage() {
  const { showNotification, showConfirm } = useNotification();
  const [staff, setStaff] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [staffs, setStaffs] = useState<any[]>([]);
  const [events, setEvents] = useState<any[]>([]);
  const [activeTab, setActiveTab] = useState("events");
  const [selectedEventForDetails, setSelectedEventForDetails] = useState<any>(null);
  const [participantSearch, setParticipantSearch] = useState("");
  const [skillFilter, setSkillFilter] = useState("");
  const [innerWayFilter, setInnerWayFilter] = useState("");
  const [minLevelFilter, setMinLevelFilter] = useState(1);

  // Form states for Staff
  const [showStaffForm, setShowStaffForm] = useState(false);
  const [newStaff, setNewStaff] = useState({ name: "", account: "", email: "", password: "", role: "admin" });
  const [editingStaffId, setEditingStaffId] = useState<number | null>(null);

  // Form states for Event
  const [showEventForm, setShowEventForm] = useState(false);
  const [newEvent, setNewEvent] = useState({ title: "", description: "", type: "casual", rules: "", rewards: "", start_time: "", status: "upcoming" });

  const router = useRouter();

  const fetchData = async () => {
    try {
      const userRes = await api.get("/admin/user");
      setStaff(userRes.data);

      const eventsRes = await api.get("/admin/events");
      setEvents(eventsRes.data);

      if (userRes.data.role === "master") {
        const staffsRes = await api.get("/admin/staffs");
        setStaffs(staffsRes.data);
      }
    } catch (err) {
      router.push("/admin/login");
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchData();
  }, [router]);

  const handleCreateStaff = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      if (editingStaffId) {
        await api.put(`/admin/staffs/${editingStaffId}`, newStaff);
        setEditingStaffId(null);
      } else {
        await api.post("/admin/staffs", newStaff);
      }
      setShowStaffForm(false);
      setNewStaff({ name: "", account: "", email: "", password: "", role: "admin" });
      fetchData();
      showNotification(editingStaffId ? "Updated staff" : "Created staff", { type: "success" });
    } catch (err: any) {
      showNotification(err.response?.data?.message || "Failed to save staff", { type: "error" });
    }
  };

  const handleDeleteStaff = (id: number) => {
    showConfirm({
      message: "Are you sure you want to delete this staff member?",
      onConfirm: async () => {
        try {
          await api.delete(`/admin/staffs/${id}`);
          fetchData();
          showNotification("Staff member deleted", { type: "success" });
        } catch (err: any) {
          showNotification(err.response?.data?.message || "Failed to delete staff", { type: "error" });
        }
      }
    });
  };

  const handleEditStaff = (s: any) => {
    setNewStaff({
      name: s.name,
      account: s.account,
      email: s.email,
      password: "", // Don't pre-fill password
      role: s.role
    });
    setEditingStaffId(s.id);
    setShowStaffForm(true);
  };

  const handleCreateEvent = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      await api.post("/admin/events", newEvent);
      setShowEventForm(false);
      setNewEvent({ title: "", description: "", type: "casual", rules: "", rewards: "", start_time: "", status: "upcoming" });
      fetchData();
      showNotification("Event created successfully", { type: "success" });
    } catch (err: any) {
      showNotification(err.response?.data?.message || "Failed to create event", { type: "error" });
    }
  };

  const updateEventType = (type: string) => {
    let updates: any = { type };
    if (type === "guild_war") {
      const now = new Date();
      const day = now.getDay();
      const mondayDiff = day === 0 ? -6 : 1 - day;
      const saturday = new Date(now);
      saturday.setDate(now.getDate() + mondayDiff + 5);
      const sunday = new Date(now);
      sunday.setDate(now.getDate() + mondayDiff + 6);

      const formatDate = (date: Date) => {
        return date.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit' });
      };

      updates.title = `Bang Chiến ngày ${formatDate(saturday)} - ${formatDate(sunday)}`;

      // Set start time to Saturday at 20:00 (default for guild war)
      const startTime = new Date(saturday);
      startTime.setHours(20, 0, 0, 0);

      // Format to YYYY-MM-DDTHH:mm for datetime-local input
      const pad = (n: number) => n.toString().padStart(2, '0');
      updates.start_time = `${startTime.getFullYear()}-${pad(startTime.getMonth() + 1)}-${pad(startTime.getDate())}T${pad(startTime.getHours())}:${pad(startTime.getMinutes())}`;
      updates.rules = "";
      updates.rewards = "";
    } else {
      updates.title = "";
      updates.start_time = "";
      updates.rules = "";
      updates.rewards = "";
    }
    setNewEvent(prev => ({ ...prev, ...updates }));
  };

  if (loading) return <div className="p-8 text-black font-medium">Loading...</div>;

  return (
    <div className="min-h-screen bg-gray-50 text-gray-900 p-4 md:p-8">
      <div className="max-w-6xl mx-auto">
        <div className="flex justify-between items-center mb-8 bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
          <div>
            <h1 className="text-2xl font-black tracking-tight">Staff Dashboard</h1>
            <div className="flex items-center gap-2 mt-1">
              <span className="text-gray-500 text-sm font-medium">{staff.name}</span>
              <span className="px-2 py-0.5 bg-blue-100 text-blue-700 text-[10px] font-bold rounded-full uppercase tracking-wider">{staff.role}</span>
            </div>
          </div>
          <button
            onClick={async () => {
              await api.post("/admin/logout");
              router.push("/admin/login");
            }}
            className="px-6 py-2 bg-red-50 text-red-600 rounded-xl hover:bg-red-100 transition font-bold text-sm"
          >
            Logout
          </button>
        </div>

        <div className="flex gap-8 mb-8 border-b border-gray-200">
          <button
            onClick={() => setActiveTab("events")}
            className={`pb-4 px-2 font-bold text-sm transition relative ${activeTab === "events" ? "text-blue-600 after:absolute after:bottom-0 after:left-0 after:right-0 after:h-0.5 after:bg-blue-600" : "text-gray-400 hover:text-gray-600"}`}
          >
            Events
          </button>
          {staff.role === "master" && (
            <button
              onClick={() => setActiveTab("staffs")}
              className={`pb-4 px-2 font-bold text-sm transition relative ${activeTab === "staffs" ? "text-blue-600 after:absolute after:bottom-0 after:left-0 after:right-0 after:h-0.5 after:bg-blue-600" : "text-gray-400 hover:text-gray-600"}`}
            >
              Staff Management
            </button>
          )}
        </div>

        {activeTab === "events" && (
          <div className="space-y-6">
            <div className="flex justify-between items-center">
              <h2 className="text-xl font-bold text-gray-800">Events List</h2>
              {(staff.role === "master" || staff.role === "admin") && (
                <button
                  onClick={() => setShowEventForm(!showEventForm)}
                  className={`px-6 py-2 rounded-xl transition text-sm font-bold shadow-sm ${showEventForm ? "bg-gray-100 text-gray-600 hover:bg-gray-200" : "bg-green-600 text-white hover:bg-green-700"}`}
                >
                  {showEventForm ? "Cancel" : "Create Event"}
                </button>
              )}
            </div>

            {showEventForm && (
              <form onSubmit={handleCreateEvent} className="bg-white p-8 rounded-2xl shadow-xl border border-gray-100 mb-12 grid grid-cols-1 md:grid-cols-2 gap-6 animate-in fade-in slide-in-from-top-4 duration-300">
                <div className="col-span-2">
                  <label className="block text-xs font-bold text-gray-500 mb-2 uppercase tracking-widest">Type</label>
                  <select
                    className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none transition-all font-medium text-gray-900"
                    value={newEvent.type}
                    onChange={(e) => updateEventType(e.target.value)}
                  >
                    <option value="casual">Casual</option>
                    <option value="guild_war">Bang chiến</option>
                  </select>
                </div>

                {newEvent.type === "casual" && (
                  <>
                    <div className="col-span-2">
                      <label className="block text-xs font-bold text-gray-500 mb-2 uppercase tracking-widest">Title</label>
                      <input
                        type="text"
                        required
                        className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none transition-all font-medium text-gray-900"
                        value={newEvent.title}
                        onChange={(e) => setNewEvent(prev => ({ ...prev, title: e.target.value }))}
                        placeholder="Enter event title..."
                      />
                    </div>
                    <div>
                      <label className="block text-xs font-bold text-gray-500 mb-2 uppercase tracking-widest">Start Time</label>
                      <input
                        type="datetime-local"
                        required
                        className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none transition-all font-medium text-gray-900"
                        value={newEvent.start_time}
                        onChange={(e) => setNewEvent(prev => ({ ...prev, start_time: e.target.value }))}
                      />
                    </div>
                  </>
                )}

                {newEvent.type === "guild_war" && (
                  <div className="col-span-2 p-4 bg-blue-50 border border-blue-100 rounded-xl text-sm text-blue-800 space-y-1">
                    <p className="flex items-center gap-2">
                      <span className="font-bold uppercase text-[10px] bg-blue-200 px-1.5 py-0.5 rounded">Auto Title</span>
                      <span>{newEvent.title}</span>
                    </p>
                  </div>
                )}

                <div className="col-span-2">
                  <label className="block text-xs font-bold text-gray-500 mb-2 uppercase tracking-widest">Description</label>
                  <textarea
                    className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none transition-all font-medium text-gray-900 min-h-[100px]"
                    rows={3}
                    value={newEvent.description}
                    onChange={(e) => setNewEvent(prev => ({ ...prev, description: e.target.value }))}
                    placeholder="Enter event description..."
                  />
                </div>

                {newEvent.type === "casual" && (
                  <>
                    <div className="col-span-2">
                      <label className="block text-xs font-bold text-gray-500 mb-2 uppercase tracking-widest">Rules</label>
                      <textarea
                        className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none transition-all font-medium text-gray-900"
                        rows={2}
                        value={newEvent.rules}
                        onChange={(e) => setNewEvent(prev => ({ ...prev, rules: e.target.value }))}
                        placeholder="Enter rules (optional)..."
                      />
                    </div>
                    <div className="col-span-2">
                      <label className="block text-xs font-bold text-gray-500 mb-2 uppercase tracking-widest">Rewards</label>
                      <input
                        type="text"
                        className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none transition-all font-medium text-gray-900"
                        value={newEvent.rewards}
                        onChange={(e) => setNewEvent(prev => ({ ...prev, rewards: e.target.value }))}
                        placeholder="Enter rewards (optional)..."
                      />
                    </div>
                  </>
                )}
                <div className="col-span-2 pt-4">
                  <button type="submit" className="w-full bg-blue-600 text-white py-4 rounded-xl hover:bg-blue-700 font-bold transition-all shadow-lg shadow-blue-500/20 active:scale-[0.98]">
                    Save Event
                  </button>
                </div>
              </form>
            )}

            <div className="grid grid-cols-1 gap-4">
              {events.length === 0 ? (
                <div className="text-gray-400 italic bg-white p-12 rounded-2xl shadow-sm text-center border border-dashed border-gray-200">
                  <div className="text-4xl mb-2">empty</div>
                  No events found.
                </div>
              ) : (
                events.map((event) => (
                  <div key={event.id} className="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow group">
                    <div className="flex justify-between items-start">
                      <div className="space-y-2">
                        <div className="flex items-center gap-3">
                          <h3 className="font-black text-lg text-gray-900 group-hover:text-blue-600 transition-colors">{event.title}</h3>
                          <span className={`px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider ${
                            event.type === 'guild_war' ? 'bg-amber-100 text-amber-700' : 'bg-blue-100 text-blue-700'
                          }`}>
                            {event.type === 'guild_war' ? 'Bang chiến' : 'Casual'}
                          </span>
                          <span className="px-2 py-0.5 bg-gray-100 text-gray-600 rounded-full text-[10px] font-bold">
                            {event.participants?.length || 0}/30
                          </span>
                        </div>
                        <p className="text-sm text-gray-500 line-clamp-2">{event.description || 'No description provided.'}</p>
                        <div className="flex items-center gap-4 text-xs font-medium text-gray-400 pt-2">
                          {event.type !== 'guild_war' && (
                            <span className="flex items-center gap-1">
                              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                              {new Date(event.start_time).toLocaleString('vi-VN')}
                            </span>
                          )}
                          <span className="flex items-center gap-1">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            {event.creator?.name}
                          </span>
                        </div>
                      </div>
                      <div className="flex flex-col items-end gap-2">
                        <span className={`px-3 py-1 rounded-lg text-xs font-bold uppercase ${
                          event.status === 'upcoming' ? 'bg-green-100 text-green-700' :
                          event.status === 'ongoing' ? 'bg-blue-100 text-blue-700' :
                          'bg-gray-100 text-gray-600'
                        }`}>
                          {event.status}
                        </span>
                        <button
                          onClick={() => setSelectedEventForDetails(event)}
                          className="px-6 py-2 bg-blue-600 text-white rounded-xl text-sm font-bold hover:bg-blue-700 transition shadow-sm"
                        >
                          Danh sách báo danh
                        </button>
                      </div>
                    </div>
                  </div>
                ))
              )}
            </div>
          </div>
        )}

        {activeTab === "staffs" && staff.role === "master" && (
          <div className="space-y-6">
            <div className="flex justify-between items-center">
              <h2 className="text-xl font-bold text-gray-800">Staff List</h2>
              <button
                onClick={() => {
                  if (showStaffForm) {
                    setEditingStaffId(null);
                    setNewStaff({ name: "", account: "", email: "", password: "", role: "admin" });
                  }
                  setShowStaffForm(!showStaffForm);
                }}
                className={`px-6 py-2 rounded-xl transition text-sm font-bold shadow-sm ${showStaffForm ? "bg-gray-100 text-gray-600 hover:bg-gray-200" : "bg-green-600 text-white hover:bg-green-700"}`}
              >
                {showStaffForm ? "Cancel" : "Add Staff"}
              </button>
            </div>

            {showStaffForm && (
              <form onSubmit={handleCreateStaff} className="bg-white p-8 rounded-2xl shadow-xl border border-gray-100 mb-12 grid grid-cols-1 md:grid-cols-2 gap-6 animate-in fade-in slide-in-from-top-4 duration-300">
                <div>
                  <label className="block text-xs font-bold text-gray-500 mb-2 uppercase tracking-widest">Name</label>
                  <input
                    type="text"
                    required
                    className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none transition-all font-medium text-gray-900"
                    value={newStaff.name}
                    onChange={(e) => setNewStaff(prev => ({ ...prev, name: e.target.value }))}
                    placeholder="Enter full name..."
                  />
                </div>
                <div>
                  <label className="block text-xs font-bold text-gray-500 mb-2 uppercase tracking-widest">Account</label>
                  <input
                    type="text"
                    required
                    className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none transition-all font-medium text-gray-900"
                    value={newStaff.account}
                    onChange={(e) => setNewStaff(prev => ({ ...prev, account: e.target.value }))}
                    placeholder="Enter username/account..."
                  />
                </div>
                <div>
                  <label className="block text-xs font-bold text-gray-500 mb-2 uppercase tracking-widest">Email</label>
                  <input
                    type="email"
                    required
                    className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none transition-all font-medium text-gray-900"
                    value={newStaff.email}
                    onChange={(e) => setNewStaff(prev => ({ ...prev, email: e.target.value }))}
                    placeholder="Enter email address..."
                  />
                </div>
                <div>
                  <label className="block text-xs font-bold text-gray-500 mb-2 uppercase tracking-widest">Password {editingStaffId && "(Leave blank to keep current)"}</label>
                  <input
                    type="password"
                    required={!editingStaffId}
                    className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none transition-all font-medium text-gray-900"
                    value={newStaff.password}
                    onChange={(e) => setNewStaff(prev => ({ ...prev, password: e.target.value }))}
                    placeholder="Enter password..."
                  />
                </div>
                <div>
                  <label className="block text-xs font-bold text-gray-500 mb-2 uppercase tracking-widest">Role</label>
                  <select
                    className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none transition-all font-medium text-gray-900"
                    value={newStaff.role}
                    onChange={(e) => setNewStaff(prev => ({ ...prev, role: e.target.value }))}
                  >
                    <option value="master">Master</option>
                    <option value="admin">Admin</option>
                    <option value="observer">Observer</option>
                  </select>
                </div>
                <div className="md:col-span-2 pt-4">
                  <button type="submit" className="w-full bg-blue-600 text-white py-4 rounded-xl hover:bg-blue-700 font-bold transition-all shadow-lg shadow-blue-500/20 active:scale-[0.98]">
                    {editingStaffId ? "Update Staff Account" : "Create Staff Account"}
                  </button>
                </div>
              </form>
            )}

            <div className="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
              <table className="min-w-full divide-y divide-gray-200">
                <thead className="bg-gray-50">
                  <tr>
                    <th className="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-widest">Name</th>
                    <th className="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-widest">Account</th>
                    <th className="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-widest">Role</th>
                    <th className="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-widest">Actions</th>
                  </tr>
                </thead>
                <tbody className="bg-white divide-y divide-gray-100">
                  {staffs.map((s) => (
                    <tr key={s.id} className="hover:bg-gray-50 transition-colors">
                      <td className="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">{s.name}</td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-medium">{s.account}</td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm">
                        <span className={`px-2.5 py-0.5 inline-flex text-[10px] leading-5 font-bold rounded-full uppercase tracking-wider ${
                          s.role === 'master' ? 'bg-purple-100 text-purple-700' :
                          s.role === 'admin' ? 'bg-blue-100 text-blue-700' :
                          'bg-gray-100 text-gray-600'
                        }`}>
                          {s.role}
                        </span>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-bold">
                        <button
                          onClick={() => handleEditStaff(s)}
                          className="text-blue-600 hover:text-blue-800 mr-6 transition-colors"
                        >
                          Edit
                        </button>
                        {s.id !== staff.id && (
                          <button
                            onClick={() => handleDeleteStaff(s.id)}
                            className="text-red-600 hover:text-red-800 transition-colors"
                          >
                            Delete
                          </button>
                        )}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        )}
      </div>

      {selectedEventForDetails && (
        (() => {
          const filteredParticipants = selectedEventForDetails.participants?.filter((user: any) => {
            const searchLower = participantSearch.toLowerCase();
            const matchesSearch =
              (user.ingame_name || user.name || "").toLowerCase().includes(searchLower) ||
              (user.ingame_id || user.id || "").toString().includes(searchLower);

            const matchesSkill = !skillFilter ||
              user.main_skill === skillFilter ||
              user.sub_skill === skillFilter;

            const matchesInnerWay = !innerWayFilter ||
              (user.gold_inner_ways || []).some((iw: any) => iw.name === innerWayFilter && iw.level >= minLevelFilter);

            return matchesSearch && matchesSkill && matchesInnerWay;
          }) || [];

          return (
            <div className="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-3xl shadow-2xl w-full max-w-4xl overflow-hidden animate-in fade-in zoom-in duration-200">
            <div className="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
              <div>
                <h3 className="font-black text-xl text-gray-900">{selectedEventForDetails.title}</h3>
                <p className="text-sm text-gray-500 font-medium">Danh sách báo danh ({selectedEventForDetails.participants?.length || 0}/30)</p>
              </div>
              <button
                onClick={() => {
                  setSelectedEventForDetails(null);
                  setParticipantSearch("");
                  setSkillFilter("");
                  setInnerWayFilter("");
                  setMinLevelFilter(1);
                }}
                className="p-2 hover:bg-gray-100 rounded-full transition-colors"
              >
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" className="text-gray-400"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              </button>
            </div>

            <div className="p-6 border-b border-gray-100 bg-white">
              <div className="flex flex-col gap-4">
                <div className="flex flex-col md:flex-row gap-4">
                  <div className="flex-1 relative">
                    <input
                      type="text"
                      placeholder="Tìm theo tên hoặc ID..."
                      className="w-full pl-10 pr-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                      value={participantSearch}
                      onChange={(e) => setParticipantSearch(e.target.value)}
                    />
                    <svg className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                  </div>
                  <div className="md:w-48">
                    <select
                      className="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                      value={skillFilter}
                      onChange={(e) => setSkillFilter(e.target.value)}
                    >
                      <option value="">Tất cả võ công</option>
                      {Array.from(new Set([
                        ...selectedEventForDetails.participants.map((p: any) => p.main_skill),
                        ...selectedEventForDetails.participants.map((p: any) => p.sub_skill),
                      ])).filter(Boolean).sort().map((skill: any) => (
                        <option key={skill} value={skill}>{skill}</option>
                      ))}
                    </select>
                  </div>
                </div>

                <div className="flex flex-col md:flex-row gap-4">
                  <div className="flex-1">
                    <select
                      className="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                      value={innerWayFilter}
                      onChange={(e) => setInnerWayFilter(e.target.value)}
                    >
                      <option value="">Tất cả tâm pháp vàng</option>
                      {Array.from(new Set([
                        ...selectedEventForDetails.participants.flatMap((p: any) => (p.gold_inner_ways || []).map((iw: any) => iw.name))
                      ])).filter(Boolean).sort().map((iwName: any) => (
                        <option key={iwName} value={iwName}>{iwName}</option>
                      ))}
                    </select>
                  </div>
                  <div className="md:w-48 flex items-center gap-2">
                    <label className="text-xs font-bold text-gray-500 whitespace-nowrap">Level tối thiểu:</label>
                    <select
                      className="flex-1 px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                      value={minLevelFilter}
                      onChange={(e) => setMinLevelFilter(parseInt(e.target.value))}
                    >
                      {[1, 2, 3, 4, 5, 6].map(lv => (
                        <option key={lv} value={lv}>Lv.{lv}</option>
                      ))}
                    </select>
                  </div>
                </div>
              </div>
            </div>

            <div className="overflow-x-auto max-h-[50vh]">
              {selectedEventForDetails.participants && selectedEventForDetails.participants.length > 0 ? (
                <>
                  <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50 sticky top-0 z-10">
                      <tr>
                        <th className="px-6 py-3 text-left text-[10px] font-bold text-gray-500 uppercase tracking-widest">STT</th>
                        <th className="px-6 py-3 text-left text-[10px] font-bold text-gray-500 uppercase tracking-widest">Thành viên</th>
                        <th className="px-6 py-3 text-left text-[10px] font-bold text-gray-500 uppercase tracking-widest">Võ công chính</th>
                        <th className="px-6 py-3 text-left text-[10px] font-bold text-gray-500 uppercase tracking-widest">Võ công phụ</th>
                        <th className="px-6 py-3 text-left text-[10px] font-bold text-gray-500 uppercase tracking-widest">Tâm pháp vàng</th>
                        <th className="px-6 py-3 text-left text-[10px] font-bold text-gray-500 uppercase tracking-widest">Giờ mong muốn</th>
                      </tr>
                    </thead>
                    <tbody className="bg-white divide-y divide-gray-100">
                      {filteredParticipants.map((user: any, idx: number) => (
                        <tr key={user.id} className="hover:bg-gray-50 transition-colors">
                          <td className="px-6 py-4 whitespace-nowrap">
                            <div className="w-6 h-6 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold text-[10px]">
                              {idx + 1}
                            </div>
                          </td>
                          <td className="px-6 py-4 whitespace-nowrap">
                            <div className="flex flex-col">
                              <span className="font-bold text-gray-900 text-sm">{user.ingame_name || user.name}</span>
                              <span className="text-[10px] text-gray-400 font-bold">#{user.ingame_id || user.id}</span>
                            </div>
                          </td>
                          <td className="px-6 py-4 whitespace-nowrap">
                            <span className="px-2 py-1 bg-blue-50 text-blue-700 rounded-lg text-xs font-bold border border-blue-100">
                              {user.main_skill || '---'}
                            </span>
                          </td>
                          <td className="px-6 py-4 whitespace-nowrap">
                            <span className="px-2 py-1 bg-purple-50 text-purple-700 rounded-lg text-xs font-bold border border-purple-100">
                              {user.sub_skill || '---'}
                            </span>
                          </td>
                          <td className="px-6 py-4 whitespace-nowrap">
                            <div className="flex flex-wrap gap-2 max-w-[240px]">
                              {user.gold_inner_ways && user.gold_inner_ways.length > 0 ? (
                                user.gold_inner_ways.map((iw: any) => (
                                  <div key={iw.name} className="flex flex-col items-center group/iw relative" title={`${iw.name} - Lv.${iw.level}`}>
                                    <div className="w-8 h-8 rounded-lg bg-amber-50 border border-amber-200 p-0.5 flex items-center justify-center relative overflow-hidden shadow-sm group-hover/iw:border-amber-400 transition-colors">
                                      <img
                                        src={`/icon/inner_way/${iw.icon}`}
                                        alt={iw.name}
                                        className="w-full h-full object-contain drop-shadow-sm"
                                      />
                                      <div className="absolute bottom-0 right-0 bg-red-600 text-white text-[7px] font-black px-0.5 min-w-[12px] text-center rounded-tl-sm">
                                        {iw.level}
                                      </div>
                                    </div>
                                  </div>
                                ))
                              ) : (
                                <span className="text-gray-300 italic text-xs">Không có</span>
                              )}
                            </div>
                          </td>
                          <td className="px-6 py-4 whitespace-nowrap">
                            {user.preferred_time ? (
                              <span className="px-2 py-1 bg-amber-50 text-amber-700 rounded-lg text-[10px] font-black border border-amber-100">
                                {user.preferred_time}
                              </span>
                            ) : (
                              <span className="text-gray-300">---</span>
                            )}
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                  {filteredParticipants.length === 0 && (
                    <div className="text-center py-12 text-gray-400 italic">
                      Không tìm thấy thành viên nào phù hợp.
                    </div>
                  )}
                </>
              ) : (
                <div className="text-center py-12 text-gray-400 italic">
                  Chưa có ai báo danh.
                </div>
              )}
            </div>
            <div className="p-6 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
              <div className="text-sm font-bold text-gray-500">
                Tìm thấy: <span className="text-blue-600 font-black">{filteredParticipants.length}</span> / {selectedEventForDetails.participants?.length || 0}
              </div>
              <button
                onClick={() => {
                  setSelectedEventForDetails(null);
                  setParticipantSearch("");
                  setSkillFilter("");
                  setInnerWayFilter("");
                  setMinLevelFilter(1);
                }}
                className="px-8 py-3 bg-white border border-gray-200 text-gray-700 rounded-xl font-bold hover:bg-gray-50 transition shadow-sm"
              >
                Đóng
              </button>
            </div>
          </div>
        </div>
          );
        })()
      )}
    </div>
  );
}
