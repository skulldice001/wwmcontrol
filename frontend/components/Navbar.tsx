"use client";

import { useRouter, usePathname } from "next/navigation";
import { useState } from "react";
import api from "@/lib/axios";
import InnerWayModal from "./InnerWayModal";

interface NavbarProps {
  user: any;
  innerWays: any[];
  onInnerWaysUpdate?: (updatedInnerWays: any[]) => void;
  onUserUpdate?: (updatedUser: any) => void;
}

export default function Navbar({ user, innerWays, onInnerWaysUpdate, onUserUpdate }: NavbarProps) {
  const router = useRouter();
  const pathname = usePathname();
  const [isModalOpen, setIsModalOpen] = useState(false);

  const handleLogout = async () => {
    try {
      await api.post("/logout");
      router.push("/");
    } catch (error) {
      console.error("Logout failed", error);
    }
  };

  return (
    <>
      <nav className="border-b border-[#1914001a] dark:border-[#fffaed1a] bg-white/50 dark:bg-[#161615]/50 backdrop-blur-md sticky top-0 z-50">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between h-16 items-center">
            <div className="flex items-center gap-6">
              <div className="flex items-center gap-2 cursor-pointer" onClick={() => router.push("/dashboard")}>
                <div className="w-8 h-8 bg-[#f53003] rounded-lg flex items-center justify-center text-white font-bold">
                  W
                </div>
                <span className="font-bold text-lg tracking-tight">WWM2</span>
              </div>

              <div className="hidden md:flex items-center gap-6 ml-8">
                <button
                  onClick={() => router.push("/dashboard")}
                  className={`text-sm font-medium transition-colors hover:text-[#f53003] ${
                    pathname === "/dashboard" ? "text-[#f53003]" : "text-[#706f6c] dark:text-[#A1A09A]"
                  }`}
                >
                  Dashboard
                </button>
                <button
                  onClick={() => router.push("/profile")}
                  className={`text-sm font-medium transition-colors hover:text-[#f53003] ${
                    pathname === "/profile" ? "text-[#f53003]" : "text-[#706f6c] dark:text-[#A1A09A]"
                  }`}
                >
                  Thông tin cá nhân
                </button>
                {innerWays && innerWays.length > 0 && (
                  <button
                    onClick={() => setIsModalOpen(true)}
                    className="text-sm font-medium text-[#706f6c] dark:text-[#A1A09A] hover:text-[#f53003] transition-colors"
                  >
                    Cập nhật võ công
                  </button>
                )}
                <button
                  onClick={() => router.push("/events")}
                  className={`text-sm font-medium transition-colors hover:text-[#f53003] ${
                    pathname === "/events" ? "text-[#f53003]" : "text-[#706f6c] dark:text-[#A1A09A]"
                  }`}
                >
                  Danh sách event
                </button>
              </div>
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

      {innerWays && innerWays.length > 0 && (
        <InnerWayModal
          isOpen={isModalOpen}
          onClose={() => setIsModalOpen(false)}
          initialInnerWays={innerWays}
          user={user}
          onUpdate={onInnerWaysUpdate || (() => {})}
          onUserUpdate={onUserUpdate || (() => {})}
        />
      )}
    </>
  );
}
