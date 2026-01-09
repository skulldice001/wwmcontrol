"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import api from "@/lib/axios";

export default function StaffDashboardPage() {
  const [staff, setStaff] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [staffs, setStaffs] = useState<any[]>([]);
  const [events, setEvents] = useState<any[]>([]);
  const [activeTab, setActiveTab] = useState("events");

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
    } catch (err: any) {
      alert(err.response?.data?.message || "Failed to save staff");
    }
  };

  const handleDeleteStaff = async (id: number) => {
    if (!confirm("Are you sure you want to delete this staff member?")) return;
    try {
      await api.delete(`/admin/staffs/${id}`);
      fetchData();
    } catch (err: any) {
      alert(err.response?.data?.message || "Failed to delete staff");
    }
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
    } catch (err: any) {
      alert(err.response?.data?.message || "Failed to create event");
    }
  };

  if (loading) return <div className="p-8 text-black">Loading...</div>;

  return (
    <div className="min-h-screen bg-gray-100 text-black p-4 md:p-8">
      <div className="max-w-6xl mx-auto">
        <div className="flex justify-between items-center mb-8 bg-white p-6 rounded-lg shadow-sm">
          <div>
            <h1 className="text-2xl font-bold">Staff Dashboard</h1>
            <div className="flex items-center gap-2 mt-1">
              <span className="text-gray-600">{staff.name}</span>
              <span className="px-2 py-0.5 bg-blue-100 text-blue-800 text-xs font-semibold rounded uppercase">{staff.role}</span>
            </div>
          </div>
          <button
            onClick={async () => {
              await api.post("/admin/logout");
              router.push("/admin/login");
            }}
            className="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600 transition"
          >
            Logout
          </button>
        </div>

        <div className="flex gap-4 mb-6 border-b border-gray-200">
          <button
            onClick={() => setActiveTab("events")}
            className={`pb-2 px-4 font-medium transition ${activeTab === "events" ? "border-b-2 border-blue-500 text-blue-600" : "text-gray-500 hover:text-gray-700"}`}
          >
            Events
          </button>
          {staff.role === "master" && (
            <button
              onClick={() => setActiveTab("staffs")}
              className={`pb-2 px-4 font-medium transition ${activeTab === "staffs" ? "border-b-2 border-blue-500 text-blue-600" : "text-gray-500 hover:text-gray-700"}`}
            >
              Staff Management
            </button>
          )}
        </div>

        {activeTab === "events" && (
          <div>
            <div className="flex justify-between items-center mb-4">
              <h2 className="text-xl font-semibold text-gray-800">Events List</h2>
              {(staff.role === "master" || staff.role === "admin") && (
                <button
                  onClick={() => setShowEventForm(!showEventForm)}
                  className="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition text-sm font-medium"
                >
                  {showEventForm ? "Cancel" : "Create Event"}
                </button>
              )}
            </div>

            {showEventForm && (
              <form onSubmit={handleCreateEvent} className="bg-white p-6 rounded-lg shadow-md mb-8 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div className="col-span-2">
                  <label className="block text-sm font-bold text-gray-700 mb-1">Title</label>
                  <input
                    type="text"
                    required
                    className="w-full px-3 py-2 border rounded"
                    value={newEvent.title}
                    onChange={(e) => setNewEvent({ ...newEvent, title: e.target.value })}
                  />
                </div>
                <div>
                  <label className="block text-sm font-bold text-gray-700 mb-1">Type</label>
                  <select
                    className="w-full px-3 py-2 border rounded"
                    value={newEvent.type}
                    onChange={(e) => setNewEvent({ ...newEvent, type: e.target.value })}
                  >
                    <option value="casual">Casual</option>
                    <option value="pvp">PVP</option>
                  </select>
                </div>
                <div>
                  <label className="block text-sm font-bold text-gray-700 mb-1">Start Time</label>
                  <input
                    type="datetime-local"
                    required
                    className="w-full px-3 py-2 border rounded"
                    value={newEvent.start_time}
                    onChange={(e) => setNewEvent({ ...newEvent, start_time: e.target.value })}
                  />
                </div>
                <div className="col-span-2">
                  <label className="block text-sm font-bold text-gray-700 mb-1">Description</label>
                  <textarea
                    className="w-full px-3 py-2 border rounded"
                    rows={2}
                    value={newEvent.description}
                    onChange={(e) => setNewEvent({ ...newEvent, description: e.target.value })}
                  />
                </div>
                <div className="col-span-2">
                  <label className="block text-sm font-bold text-gray-700 mb-1">Rules</label>
                  <textarea
                    className="w-full px-3 py-2 border rounded"
                    rows={2}
                    value={newEvent.rules}
                    onChange={(e) => setNewEvent({ ...newEvent, rules: e.target.value })}
                  />
                </div>
                <div className="col-span-2">
                  <label className="block text-sm font-bold text-gray-700 mb-1">Rewards</label>
                  <input
                    type="text"
                    className="w-full px-3 py-2 border rounded"
                    value={newEvent.rewards}
                    onChange={(e) => setNewEvent({ ...newEvent, rewards: e.target.value })}
                  />
                </div>
                <button type="submit" className="col-span-2 bg-blue-600 text-white py-2 rounded hover:bg-blue-700 font-bold">
                  Save Event
                </button>
              </form>
            )}

            <div className="grid grid-cols-1 gap-4">
              {events.length === 0 ? (
                <p className="text-gray-500 italic bg-white p-6 rounded shadow-sm text-center">No events found.</p>
              ) : (
                events.map((event) => (
                  <div key={event.id} className="bg-white p-4 rounded-lg shadow-sm border-l-4 border-blue-500">
                    <div className="flex justify-between items-start">
                      <div>
                        <h3 className="font-bold text-lg">{event.title}</h3>
                        <p className="text-sm text-gray-600 capitalize">Type: {event.type} | Status: {event.status}</p>
                      </div>
                      <div className="text-right">
                        <p className="text-xs text-gray-500">Starts: {new Date(event.start_time).toLocaleString()}</p>
                        <p className="text-xs text-gray-400">Created by: {event.creator?.name}</p>
                      </div>
                    </div>
                  </div>
                ))
              )}
            </div>
          </div>
        )}

        {activeTab === "staffs" && staff.role === "master" && (
          <div>
            <div className="flex justify-between items-center mb-4">
              <h2 className="text-xl font-semibold text-gray-800">Staff List</h2>
              <button
                onClick={() => {
                  if (showStaffForm) {
                    setEditingStaffId(null);
                    setNewStaff({ name: "", account: "", email: "", password: "", role: "admin" });
                  }
                  setShowStaffForm(!showStaffForm);
                }}
                className="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition text-sm font-medium"
              >
                {showStaffForm ? "Cancel" : "Add Staff"}
              </button>
            </div>

            {showStaffForm && (
              <form onSubmit={handleCreateStaff} className="bg-white p-6 rounded-lg shadow-md mb-8 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-bold text-gray-700 mb-1">Name</label>
                  <input
                    type="text"
                    required
                    className="w-full px-3 py-2 border rounded"
                    value={newStaff.name}
                    onChange={(e) => setNewStaff({ ...newStaff, name: e.target.value })}
                  />
                </div>
                <div>
                  <label className="block text-sm font-bold text-gray-700 mb-1">Account</label>
                  <input
                    type="text"
                    required
                    className="w-full px-3 py-2 border rounded"
                    value={newStaff.account}
                    onChange={(e) => setNewStaff({ ...newStaff, account: e.target.value })}
                  />
                </div>
                <div>
                  <label className="block text-sm font-bold text-gray-700 mb-1">Email</label>
                  <input
                    type="email"
                    required
                    className="w-full px-3 py-2 border rounded"
                    value={newStaff.email}
                    onChange={(e) => setNewStaff({ ...newStaff, email: e.target.value })}
                  />
                </div>
                <div>
                  <label className="block text-sm font-bold text-gray-700 mb-1">Password {editingStaffId && "(Leave blank to keep current)"}</label>
                  <input
                    type="password"
                    required={!editingStaffId}
                    className="w-full px-3 py-2 border rounded"
                    value={newStaff.password}
                    onChange={(e) => setNewStaff({ ...newStaff, password: e.target.value })}
                  />
                </div>
                <div>
                  <label className="block text-sm font-bold text-gray-700 mb-1">Role</label>
                  <select
                    className="w-full px-3 py-2 border rounded"
                    value={newStaff.role}
                    onChange={(e) => setNewStaff({ ...newStaff, role: e.target.value })}
                  >
                    <option value="master">Master</option>
                    <option value="admin">Admin</option>
                    <option value="observer">Observer</option>
                  </select>
                </div>
                <div className="md:col-span-2 flex items-end">
                  <button type="submit" className="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700 font-bold">
                    {editingStaffId ? "Update Staff Account" : "Create Staff Account"}
                  </button>
                </div>
              </form>
            )}

            <div className="bg-white rounded-lg shadow-sm overflow-hidden">
              <table className="min-w-full divide-y divide-gray-200">
                <thead className="bg-gray-50">
                  <tr>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Account</th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                    <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                  </tr>
                </thead>
                <tbody className="bg-white divide-y divide-gray-200">
                  {staffs.map((s) => (
                    <tr key={s.id}>
                      <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{s.name}</td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{s.account}</td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm">
                        <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${s.role === 'master' ? 'bg-purple-100 text-purple-800' : s.role === 'admin' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'}`}>
                          {s.role}
                        </span>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <button
                          onClick={() => handleEditStaff(s)}
                          className="text-blue-600 hover:text-blue-900 mr-4"
                        >
                          Edit
                        </button>
                        {s.id !== staff.id && (
                          <button
                            onClick={() => handleDeleteStaff(s.id)}
                            className="text-red-600 hover:text-red-900"
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
    </div>
  );
}
