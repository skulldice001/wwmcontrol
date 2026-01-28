"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import api from "@/lib/axios";
import Navbar from "@/components/Navbar";

export default function Profile() {
  const router = useRouter();
  const [user, setUser] = useState<any>(null);
  const [innerWays, setInnerWays] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [message, setMessage] = useState("");

  const [formData, setFormData] = useState({
    country: "",
    online_from: "00:00",
    online_to: "00:00",
    ingame_name: "",
    ingame_id: "",
  });

  // Generate time options every 30 minutes
  const timeOptions = [];
  for (let i = 0; i < 24; i++) {
    for (let j = 0; j < 60; j += 30) {
      const hours = i.toString().padStart(2, '0');
      const minutes = j.toString().padStart(2, '0');
      timeOptions.push(`${hours}:${minutes}`);
    }
  }

  useEffect(() => {
    const fetchData = async () => {
      try {
        const [userResponse, innerWaysResponse] = await Promise.all([
          api.get("/user"),
          api.get("/user/inner-ways")
        ]);

        const userData = userResponse.data;
        setUser(userData);
        setInnerWays(innerWaysResponse.data.inner_ways || []);

        setFormData({
          country: userData.country || "",
          online_from: userData.online_from || "00:00",
          online_to: userData.online_to || "00:00",
          ingame_name: userData.ingame_name || "",
          ingame_id: userData.ingame_id || "",
        });
      } catch (error) {
        router.push("/");
      } finally {
        setLoading(false);
      }
    };
    fetchData();
  }, [router]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setSaving(true);
    setMessage("");

    try {
      const response = await api.post("/user/profile", formData);
      setMessage("Cập nhật thông tin thành công!");
      setUser(response.data.user);
    } catch (error) {
      console.error("Update profile failed", error);
      setMessage("Có lỗi xảy ra khi cập nhật thông tin.");
    } finally {
      setSaving(false);
    }
  };

  const handleLogout = async () => {
    try {
      await api.post("/logout");
      router.push("/");
    } catch (error) {
      console.error("Logout failed", error);
    }
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

      <main className="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div className="mb-8">
          <h1 className="text-3xl font-bold tracking-tight mb-2">Thông tin cá nhân</h1>
          <p className="text-[#706f6c] dark:text-[#A1A09A]">
            Quản lý thông tin tài khoản và tùy chỉnh hồ sơ của bạn.
          </p>
        </div>

        <div className="bg-white dark:bg-[#161615] rounded-2xl border border-[#1914001a] dark:border-[#fffaed1a] shadow-sm overflow-hidden">
          <form onSubmit={handleSubmit} className="p-8 space-y-6">
            {message && (
              <div className={`p-4 rounded-xl text-sm font-medium ${message.includes('thành công') ? 'bg-green-500/10 text-green-500' : 'bg-red-500/10 text-red-500'}`}>
                {message}
              </div>
            )}

            <div className="flex items-center gap-6 pb-6 border-b border-[#1914000a] dark:border-[#fffaed0a]">
              {user?.discord_avatar ? (
                <img
                  src={user.discord_avatar}
                  alt={user.name}
                  className="w-20 h-20 rounded-2xl border-2 border-[#1914001a] dark:border-[#fffaed1a]"
                />
              ) : (
                <div className="w-20 h-20 rounded-2xl bg-[#f53003]/10 flex items-center justify-center text-[#f53003] text-2xl font-bold">
                  {user?.name?.charAt(0)}
                </div>
              )}
              <div>
                <div className="text-2xl font-bold">{user?.name}</div>
                <div className="text-[#706f6c] dark:text-[#A1A09A]">{user?.email}</div>
              </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-6 pt-2">
              <div className="space-y-2">
                <label className="text-sm font-medium text-[#706f6c] dark:text-[#A1A09A]">
                  Quốc gia hiện tại
                </label>
                <input
                  type="text"
                  value={formData.country}
                  onChange={(e) => setFormData({ ...formData, country: e.target.value })}
                  className="w-full px-4 py-2 rounded-xl border border-[#1914001a] dark:border-[#fffaed1a] bg-transparent focus:outline-none focus:ring-2 focus:ring-[#f53003]/20 focus:border-[#f53003] transition-all"
                  placeholder="Ví dụ: Việt Nam"
                />
              </div>

              <div className="space-y-2">
                <label className="text-sm font-medium text-[#706f6c] dark:text-[#A1A09A]">
                  Khung giờ online (GMT+7)
                </label>
                <div className="flex items-center gap-2">
                  <select
                    value={formData.online_from}
                    onChange={(e) => setFormData({ ...formData, online_from: e.target.value })}
                    className="flex-1 px-4 py-2 rounded-xl border border-[#1914001a] dark:border-[#fffaed1a] bg-transparent focus:outline-none focus:ring-2 focus:ring-[#f53003]/20 focus:border-[#f53003] transition-all"
                  >
                    {timeOptions.map(time => (
                      <option key={`from-${time}`} value={time} className="dark:bg-[#161615] text-[#1b1b18] dark:text-[#EDEDEC]">{time}</option>
                    ))}
                  </select>
                  <span className="text-[#706f6c]">đến</span>
                  <select
                    value={formData.online_to}
                    onChange={(e) => setFormData({ ...formData, online_to: e.target.value })}
                    className="flex-1 px-4 py-2 rounded-xl border border-[#1914001a] dark:border-[#fffaed1a] bg-transparent focus:outline-none focus:ring-2 focus:ring-[#f53003]/20 focus:border-[#f53003] transition-all"
                  >
                    {timeOptions.map(time => (
                      <option key={`to-${time}`} value={time} className="dark:bg-[#161615] text-[#1b1b18] dark:text-[#EDEDEC]">{time}</option>
                    ))}
                  </select>
                </div>
              </div>

              <div className="space-y-2">
                <label className="text-sm font-medium text-[#706f6c] dark:text-[#A1A09A]">
                  Ingame Name
                </label>
                <input
                  type="text"
                  value={formData.ingame_name}
                  onChange={(e) => setFormData({ ...formData, ingame_name: e.target.value })}
                  className="w-full px-4 py-2 rounded-xl border border-[#1914001a] dark:border-[#fffaed1a] bg-transparent focus:outline-none focus:ring-2 focus:ring-[#f53003]/20 focus:border-[#f53003] transition-all"
                  placeholder="Nhập tên nhân vật"
                />
              </div>

              <div className="space-y-2">
                <label className="text-sm font-medium text-[#706f6c] dark:text-[#A1A09A]">
                  Ingame ID
                </label>
                <input
                  type="text"
                  value={formData.ingame_id}
                  onChange={(e) => setFormData({ ...formData, ingame_id: e.target.value })}
                  className="w-full px-4 py-2 rounded-xl border border-[#1914001a] dark:border-[#fffaed1a] bg-transparent focus:outline-none focus:ring-2 focus:ring-[#f53003]/20 focus:border-[#f53003] transition-all"
                  placeholder="Nhập ID nhân vật"
                />
              </div>
            </div>

            <div className="pt-6 border-t border-[#1914000a] dark:border-[#fffaed0a] flex items-center justify-between">
              <div className="text-xs text-[#706f6c] dark:text-[#A1A09A]">
                Discord ID: <span className="font-mono">{user?.discord_id}</span>
              </div>
              <button
                type="submit"
                disabled={saving}
                className="px-8 py-3 bg-[#f53003] text-white rounded-xl font-bold hover:bg-[#d42a02] transition-colors disabled:opacity-50 flex items-center gap-2"
              >
                {saving ? (
                  <>
                    <div className="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                    Đang lưu...
                  </>
                ) : "Lưu thay đổi"}
              </button>
            </div>
          </form>
        </div>
      </main>
    </div>
  );
}
