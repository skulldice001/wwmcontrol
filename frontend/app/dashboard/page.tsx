"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import api from "@/lib/axios";
import Navbar from "@/components/Navbar";

export default function Dashboard() {
  const router = useRouter();
  const [user, setUser] = useState<any>(null);
  const [innerWays, setInnerWays] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [roles, setRoles] = useState<any[]>([]);
  const [selectedIcon, setSelectedIcon] = useState<any>(null);

  useEffect(() => {
    const fetchData = async () => {
      try {
        const [userResponse, rolesResponse, innerWaysResponse] = await Promise.all([
          api.get("/user"),
          api.get("/discord/roles"),
          api.get("/user/inner-ways")
        ]);
        setUser(userResponse.data);
        setRoles(rolesResponse.data.roles || []);
        setInnerWays(innerWaysResponse.data.inner_ways || []);
      } catch (error) {
        // If unauthorized, redirect to home/login
        router.push("/");
      } finally {
        setLoading(false);
      }
    };

    fetchData();
  }, [router]);

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

      <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        {/* Welcome Header */}
        <div className="mb-12">
          <h1 className="text-4xl font-bold tracking-tight mb-4">
            Welcome back, {user?.name.split(' ')[0]}! 👋
          </h1>
          <p className="text-lg text-[#706f6c] dark:text-[#A1A09A] max-w-2xl">
            You are successfully logged in via Discord. Here's what's happening with your account today.
          </p>
        </div>

        {/* Stats Grid */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
          {[
            { label: 'Discord Status', value: 'Connected', icon: '✅' },
            { label: 'Account Type', value: 'Standard', icon: '👤' },
            { label: 'Member Since', value: new Date(user?.created_at).toLocaleDateString(), icon: '📅' },
          ].map((stat, i) => (
            <div key={i} className="p-6 bg-white dark:bg-[#161615] rounded-xl border border-[#1914001a] dark:border-[#fffaed1a] shadow-sm">
              <div className="text-2xl mb-2">{stat.icon}</div>
              <div className="text-sm text-[#706f6c] dark:text-[#A1A09A] mb-1">{stat.label}</div>
              <div className="text-xl font-bold">{stat.value}</div>
            </div>
          ))}
        </div>

        {/* Martial Arts Section */}
        <div className="grid grid-cols-1 md:grid-cols-2 gap-8 mb-12">
          {/* Main Skill */}
          <div className="bg-white dark:bg-[#161615] rounded-3xl border border-[#1914001a] dark:border-[#fffaed1a] p-8 shadow-sm flex items-center gap-6">
            <div className="w-24 h-24 rounded-2xl bg-amber-400/10 flex items-center justify-center border-2 border-amber-400/20">
              {user?.main_skill ? (
                <img src={`/icon/skill/${user.main_skill.icon}`} alt={user.main_skill.name} className="w-20 h-20 object-contain drop-shadow-md" />
              ) : (
                <div className="text-4xl">⚔️</div>
              )}
            </div>
            <div>
              <div className="text-xs font-bold text-amber-500 uppercase tracking-widest mb-1">Võ công chính</div>
              <div className="text-2xl font-black capitalize">{user?.main_skill?.name || 'Chưa chọn'}</div>
              <div className="text-sm text-[#706f6c] dark:text-[#A1A09A] mt-1">Sức mạnh chủ đạo của nhân vật</div>
            </div>
          </div>

          {/* Sub Skill */}
          <div className="bg-white dark:bg-[#161615] rounded-3xl border border-[#1914001a] dark:border-[#fffaed1a] p-8 shadow-sm flex items-center gap-6">
            <div className="w-24 h-24 rounded-2xl bg-blue-500/10 flex items-center justify-center border-2 border-blue-500/20">
              {user?.sub_skill ? (
                <img src={`/icon/skill/${user.sub_skill.icon}`} alt={user.sub_skill.name} className="w-20 h-20 object-contain drop-shadow-md" />
              ) : (
                <div className="text-4xl">🛡️</div>
              )}
            </div>
            <div>
              <div className="text-xs font-bold text-blue-500 uppercase tracking-widest mb-1">Võ công phụ</div>
              <div className="text-2xl font-black capitalize">{user?.sub_skill?.name || 'Chưa chọn'}</div>
              <div className="text-sm text-[#706f6c] dark:text-[#A1A09A] mt-1">Kỹ năng bổ trợ linh hoạt</div>
            </div>
          </div>
        </div>

        {/* Inner Ways Section */}
        {innerWays && innerWays.length > 0 && (
          <div className="mb-12">
            <h2 className="text-2xl font-bold mb-6">Tâm Pháp Võ Công</h2>
            <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
              {innerWays.map((iw: any) => (
                <div key={iw.slug} className="p-4 flex flex-col items-center text-center group cursor-pointer" onClick={() => setSelectedIcon(iw)}>
                  <div className={`w-24 h-24 mb-3 relative transition-transform duration-300 group-hover:scale-110 rounded-2xl flex items-center justify-center ${
                    iw.color === 'gold' ? 'bg-amber-400/10' :
                    iw.color === 'purple' ? 'bg-purple-500/10' :
                    'bg-blue-500/10'
                  }`}>
                    <img
                      src={`/icon/inner_way/${iw.icon}`}
                      alt={iw.name}
                      className="w-20 h-20 object-contain drop-shadow-md"
                    />
                    <div className="absolute top-0 right-0 bg-[#f53003] text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full border-2 border-white dark:border-[#161615] shadow-sm">
                      Lv.{iw.level}
                    </div>
                  </div>
                  <div className="text-sm font-bold capitalize truncate w-full group-hover:text-[#f53003] transition-colors" title={iw.name}>
                    {iw.name}
                  </div>
                  <div className="mt-2 w-16 bg-[#1914000a] dark:bg-[#fffaed0a] h-1.5 rounded-full overflow-hidden">
                    <div
                      className="bg-[#f53003] h-full transition-all duration-500"
                      style={{ width: `${(iw.level / 6) * 100}%` }}
                    ></div>
                  </div>
                </div>
              ))}
            </div>
          </div>
        )}

        {/* Icon Zoom Modal */}
        {selectedIcon && (
          <div
            className="fixed inset-0 z-[200] flex items-center justify-center p-4 bg-black/80 backdrop-blur-md animate-in fade-in duration-200"
            onClick={() => setSelectedIcon(null)}
          >
            <div
              className="relative max-w-sm w-full bg-white dark:bg-[#161615] rounded-3xl p-8 flex flex-col items-center shadow-2xl animate-in zoom-in duration-300"
              onClick={(e) => e.stopPropagation()}
            >
              <button
                onClick={() => setSelectedIcon(null)}
                className="absolute top-4 right-4 p-2 rounded-full hover:bg-black/5 dark:hover:bg-white/5 transition-colors"
              >
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
                  <path d="M18 6 6 18"/><path d="m6 6 12 12"/>
                </svg>
              </button>

              <div className={`w-48 h-48 mb-6 relative rounded-3xl flex items-center justify-center ${
                selectedIcon.color === 'gold' ? 'bg-amber-400/20' :
                selectedIcon.color === 'purple' ? 'bg-purple-500/20' :
                'bg-blue-500/20'
              }`}>
                <img
                  src={`/icon/inner_way/${selectedIcon.icon}`}
                  alt={selectedIcon.name}
                  className="w-40 h-40 object-contain drop-shadow-2xl"
                />
                <div className="absolute -top-3 -right-3 bg-[#f53003] text-white text-xl font-bold px-4 py-1.5 rounded-full border-4 border-white dark:border-[#161615] shadow-xl">
                  Lv.{selectedIcon.level}
                </div>
              </div>

              <h3 className="text-2xl font-black capitalize mb-2">{selectedIcon.name}</h3>
              <p className="text-[#706f6c] dark:text-[#A1A09A] font-medium mb-6">
                Phẩm chất: <span className={`capitalize ${
                  selectedIcon.color === 'gold' ? 'text-amber-500' :
                  selectedIcon.color === 'purple' ? 'text-purple-500' :
                  'text-blue-500'
                }`}>{selectedIcon.color === 'gold' ? 'Vàng' : selectedIcon.color === 'purple' ? 'Tím' : 'Xanh'}</span>
              </p>

              <div className="w-full bg-[#1914000a] dark:bg-[#fffaed0a] h-3 rounded-full overflow-hidden mb-2">
                <div
                  className="bg-[#f53003] h-full transition-all duration-1000"
                  style={{ width: `${(selectedIcon.level / 6) * 100}%` }}
                ></div>
              </div>
              <div className="text-xs font-bold text-[#706f6c] dark:text-[#A1A09A] uppercase tracking-widest">
                Tiến độ tu luyện: {selectedIcon.level}/6
              </div>
            </div>
          </div>
        )}

        {/* Content Section */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
          {/* User Profile Card */}
          <div className="bg-white dark:bg-[#161615] rounded-2xl border border-[#1914001a] dark:border-[#fffaed1a] overflow-hidden shadow-sm">
            <div className="px-8 py-6 border-b border-[#1914000a] dark:border-[#fffaed0a] bg-[#19140002] dark:bg-[#fffaed02]">
              <h2 className="font-bold text-xl">Profile Information</h2>
            </div>
            <div className="p-8 space-y-6">
              <div className="flex items-center gap-6">
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

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-6 pt-4">
                <div className="space-y-1">
                  <div className="text-xs font-bold uppercase tracking-wider text-[#706f6c] dark:text-[#A1A09A]">Discord ID</div>
                  <div className="font-mono text-sm p-2 bg-[#19140005] dark:bg-[#fffaed05] rounded border border-[#1914000a] dark:border-[#fffaed0a]">
                    {user?.discord_id}
                  </div>
                </div>
                <div className="space-y-1">
                  <div className="text-xs font-bold uppercase tracking-wider text-[#706f6c] dark:text-[#A1A09A]">Quốc gia</div>
                  <div className="text-sm p-2 bg-[#19140005] dark:bg-[#fffaed05] rounded border border-[#1914000a] dark:border-[#fffaed0a]">
                    {user?.country || 'Chưa cập nhật'}
                  </div>
                </div>
                <div className="space-y-1">
                  <div className="text-xs font-bold uppercase tracking-wider text-[#706f6c] dark:text-[#A1A09A]">Online (GMT+7)</div>
                  <div className="text-sm p-2 bg-[#19140005] dark:bg-[#fffaed05] rounded border border-[#1914000a] dark:border-[#fffaed0a]">
                    {user?.online_from && user?.online_to ? `${user.online_from} - ${user.online_to}` : 'Chưa cập nhật'}
                  </div>
                </div>
                <div className="space-y-1">
                  <div className="text-xs font-bold uppercase tracking-wider text-[#706f6c] dark:text-[#A1A09A]">Ingame</div>
                  <div className="text-sm p-2 bg-[#19140005] dark:bg-[#fffaed05] rounded border border-[#1914000a] dark:border-[#fffaed0a]">
                    {user?.ingame_name ? `${user.ingame_name} (${user.ingame_id || 'N/A'})` : 'Chưa cập nhật'}
                  </div>
                </div>
              </div>

              {roles && roles.length > 0 && (
                <div className="pt-4 border-t border-[#1914000a] dark:border-[#fffaed0a]">
                  <div className="text-xs font-bold uppercase tracking-wider text-[#706f6c] dark:text-[#A1A09A] mb-3">Guild Roles</div>
                  <div className="flex flex-wrap gap-2">
                    {roles.map((role: any) => (
                      <span
                        key={role.id || role}
                        className="px-3 py-1 bg-[#f53003]/10 text-[#f53003] text-xs font-bold rounded-full border border-[#f53003]/20"
                      >
                        {role.name || role}
                      </span>
                    ))}
                  </div>
                </div>
              )}
            </div>
          </div>

          {/* Quick Actions / Getting Started */}
          <div className="space-y-6">
            <div className="bg-[#f53003] rounded-2xl p-8 text-white shadow-lg shadow-[#f53003]/20 relative overflow-hidden group">
              <div className="relative z-10">
                <h2 className="text-2xl font-bold mb-2">Ready to build?</h2>
                <p className="text-white/80 mb-6">Explore the documentation to start building your amazing application.</p>
                <a
                  href="https://laravel.com/docs"
                  target="_blank"
                  className="inline-flex items-center gap-2 px-6 py-3 bg-white text-[#f53003] rounded-xl font-bold hover:bg-white/90 transition-colors"
                >
                  Read Docs
                  <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M1 13L13 1M13 1H4M13 1V10" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                  </svg>
                </a>
              </div>
              <div className="absolute -right-8 -bottom-8 w-48 h-48 bg-white/10 rounded-full blur-3xl group-hover:bg-white/20 transition-colors"></div>
            </div>

            <div className="bg-white dark:bg-[#161615] rounded-2xl border border-[#1914001a] dark:border-[#fffaed1a] p-8 shadow-sm">
              <h3 className="font-bold text-lg mb-4">Quick Links</h3>
              <div className="grid grid-cols-2 gap-4">
                {['Account Settings', 'Privacy Policy', 'Support', 'API Keys'].map((item) => (
                  <button key={item} className="text-left px-4 py-3 rounded-xl border border-[#1914000a] dark:border-[#fffaed0a] hover:bg-[#19140005] dark:hover:bg-[#fffaed05] transition-colors text-sm font-medium">
                    {item}
                  </button>
                ))}
              </div>
            </div>
          </div>
        </div>
      </main>

      <footer className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 border-t border-[#1914001a] dark:border-[#fffaed1a] mt-12 text-center text-sm text-[#706f6c] dark:text-[#A1A09A]">
        &copy; {new Date().getFullYear()} WWM2 Project. All rights reserved.
      </footer>
    </div>
  );
}
