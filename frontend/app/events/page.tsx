"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import api from "@/lib/axios";
import Navbar from "@/components/Navbar";

export default function Events() {
  const router = useRouter();
  const [user, setUser] = useState<any>(null);
  const [innerWays, setInnerWays] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchData = async () => {
      try {
        const [userResponse, innerWaysResponse] = await Promise.all([
          api.get("/user"),
          api.get("/user/inner-ways")
        ]);
        setUser(userResponse.data);
        setInnerWays(innerWaysResponse.data.inner_ways || []);
      } catch (error) {
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
        <h1 className="text-4xl font-bold tracking-tight mb-8">Danh sách event</h1>
        <div className="bg-white dark:bg-[#161615] rounded-2xl border border-[#1914001a] dark:border-[#fffaed1a] p-12 text-center shadow-sm">
          <div className="text-6xl mb-4">📅</div>
          <h2 className="text-2xl font-bold mb-2">Chưa có event nào</h2>
          <p className="text-[#706f6c] dark:text-[#A1A09A]">
            Hiện tại không có sự kiện nào đang diễn ra. Hãy quay lại sau nhé!
          </p>
        </div>
      </main>
    </div>
  );
}
