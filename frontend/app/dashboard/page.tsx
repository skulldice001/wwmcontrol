"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import api from "@/lib/axios";

export default function Dashboard() {
  const router = useRouter();
  const [user, setUser] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [roles, setRoles] = useState<any[]>([]);

  useEffect(() => {
    const fetchData = async () => {
      try {
        const [userResponse, rolesResponse] = await Promise.all([
          api.get("/user"),
          api.get("/discord/roles")
        ]);
        setUser(userResponse.data);
        setRoles(rolesResponse.data.roles || []);
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
      {/* Navigation */}
      <nav className="border-b border-[#1914001a] dark:border-[#fffaed1a] bg-white/50 dark:bg-[#161615]/50 backdrop-blur-md sticky top-0 z-50">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between h-16 items-center">
            <div className="flex items-center gap-2">
              <div className="w-8 h-8 bg-[#f53003] rounded-lg flex items-center justify-center text-white font-bold">
                W
              </div>
              <span className="font-bold text-lg tracking-tight">WWM2</span>
            </div>

            <div className="flex items-center gap-4">
              <div className="flex items-center gap-3 px-3 py-1.5 rounded-full bg-[#19140005] dark:bg-[#fffaed05] border border-[#1914001a] dark:border-[#fffaed1a]">
                {user?.discord_avatar ? (
                  <img
                    src={user.discord_avatar}
                    alt={user.name}
                    className="w-7 h-7 rounded-full border border-[#1914001a] dark:border-[#fffaed1a]"
                  />
                ) : (
                  <div className="w-7 h-7 rounded-full bg-[#f53003]/10 flex items-center justify-center text-[#f53003] text-xs font-bold">
                    {user?.name?.charAt(0)}
                  </div>
                )}
                <span className="text-sm font-medium">{user?.name}</span>
              </div>

              <button
                onClick={handleLogout}
                className="text-sm font-medium text-[#706f6c] dark:text-[#A1A09A] hover:text-[#1b1b18] dark:hover:text-[#EDEDEC] transition-colors"
              >
                Logout
              </button>
            </div>
          </div>
        </div>
      </nav>

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
                  <div className="text-xs font-bold uppercase tracking-wider text-[#706f6c] dark:text-[#A1A09A]">Email Verified</div>
                  <div className="text-sm flex items-center gap-2">
                    <span className="w-2 h-2 rounded-full bg-green-500"></span>
                    Yes
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
