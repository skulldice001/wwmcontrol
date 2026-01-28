"use client";

import { useEffect, useState } from "react";
import api from "@/lib/axios";

interface InnerWay {
  name: string;
  slug: string;
  icon: string;
  color?: string;
  level: number;
}

interface Skill {
  id: number;
  name: string;
  slug: string;
  icon: string;
}

interface InnerWayModalProps {
  isOpen: boolean;
  onClose: () => void;
  initialInnerWays: InnerWay[];
  user: any;
  onUpdate: (updatedInnerWays: InnerWay[]) => void;
  onUserUpdate: (updatedUser: any) => void;
}

export default function InnerWayModal({ isOpen, onClose, initialInnerWays, user, onUpdate, onUserUpdate }: InnerWayModalProps) {
  const [innerWays, setInnerWays] = useState<InnerWay[]>(initialInnerWays);
  const [skills, setSkills] = useState<Skill[]>([]);
  const [mainSkillId, setMainSkillId] = useState<string | number>(user?.main_skill_id || "");
  const [subSkillId, setSubSkillId] = useState<string | number>(user?.sub_skill_id || "");
  const [saving, setSaving] = useState(false);
  const [message, setMessage] = useState("");

  useEffect(() => {
    if (isOpen) {
      const fetchSkills = async () => {
        try {
          const response = await api.get("/skills");
          setSkills(response.data.skills);
        } catch (error) {
          console.error("Failed to fetch skills", error);
        }
      };
      fetchSkills();
    }
  }, [isOpen]);

  useEffect(() => {
    setInnerWays(initialInnerWays);
    setMainSkillId(user?.main_skill_id || "");
    setSubSkillId(user?.sub_skill_id || "");
  }, [initialInnerWays, user]);

  if (!isOpen) return null;

  const handleLevelChange = (slug: string, newLevel: number) => {
    setInnerWays(prev =>
      prev.map(iw => (iw.slug === slug ? { ...iw, level: newLevel } : iw))
    );
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (mainSkillId && subSkillId && mainSkillId == subSkillId) {
      setMessage("Võ công chính và phụ không được trùng nhau.");
      return;
    }

    setSaving(true);
    setMessage("");

    try {
      const response = await api.post("/user/inner-ways", {
        inner_ways: innerWays,
        main_skill_id: mainSkillId || null,
        sub_skill_id: subSkillId || null,
      });
      setMessage("Cập nhật thành công!");
      onUpdate(response.data.inner_ways);
      onUserUpdate(response.data.user);
      setTimeout(() => {
        onClose();
        setMessage("");
      }, 1500);
    } catch (error: any) {
      console.error("Update failed", error);
      setMessage(error.response?.data?.message || "Có lỗi xảy ra khi cập nhật.");
    } finally {
      setSaving(false);
    }
  };

  return (
    <div className="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
      <div className="bg-white dark:bg-[#161615] w-full max-w-4xl max-h-[90vh] rounded-2xl border border-[#1914001a] dark:border-[#fffaed1a] shadow-2xl flex flex-col overflow-hidden animate-in fade-in zoom-in duration-200">
        <div className="px-6 py-4 border-b border-[#1914000a] dark:border-[#fffaed0a] flex items-center justify-between">
          <h2 className="text-xl font-bold">Cập nhật võ công</h2>
          <button onClick={onClose} className="text-[#706f6c] hover:text-[#1b1b18] dark:hover:text-[#EDEDEC] transition-colors">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
              <path d="M18 6 6 18"/><path d="m6 6 12 12"/>
            </svg>
          </button>
        </div>

        <form onSubmit={handleSubmit} className="flex-1 overflow-hidden flex flex-col">
          <div className="flex-1 overflow-y-auto p-6">
            {message && (
              <div className={`mb-6 p-4 rounded-xl text-sm font-medium ${message.includes('thành công') ? 'bg-green-500/10 text-green-500' : 'bg-red-500/10 text-red-500'}`}>
                {message}
              </div>
            )}

            {/* Skill Selection Section */}
            <div className="mb-8 grid grid-cols-1 md:grid-cols-2 gap-6 p-6 rounded-2xl bg-[#19140005] dark:bg-[#fffaed05] border border-[#1914000a] dark:border-[#fffaed0a]">
              <div>
                <label className="block text-sm font-bold mb-3 text-[#706f6c] dark:text-[#A1A09A] uppercase tracking-wider">
                  Võ công chính
                </label>
                <div className="relative">
                  <select
                    value={mainSkillId}
                    onChange={(e) => setMainSkillId(e.target.value)}
                    className="w-full pl-12 pr-4 py-3 bg-white dark:bg-[#161615] border border-[#1914001a] dark:border-[#fffaed1a] rounded-xl appearance-none focus:outline-none focus:ring-2 focus:ring-[#f53003] transition-all font-medium"
                  >
                    <option value="">Chọn võ công chính</option>
                    {skills.map((skill) => (
                      <option key={skill.id} value={skill.id}>
                        {skill.name}
                      </option>
                    ))}
                  </select>
                  <div className="absolute left-3 top-1/2 -translate-y-1/2 w-7 h-7 flex items-center justify-center">
                    {mainSkillId ? (
                      <img
                        src={`/icon/skill/${skills.find(s => s.id == mainSkillId)?.icon}`}
                        alt="skill"
                        className="w-full h-full object-contain"
                      />
                    ) : (
                      <div className="w-5 h-5 border-2 border-dashed border-[#1914001a] dark:border-[#fffaed1a] rounded-md" />
                    )}
                  </div>
                  <div className="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" className="text-[#706f6c]">
                      <path d="m6 9 6 6 6-6"/>
                    </svg>
                  </div>
                </div>
              </div>

              <div>
                <label className="block text-sm font-bold mb-3 text-[#706f6c] dark:text-[#A1A09A] uppercase tracking-wider">
                  Võ công phụ
                </label>
                <div className="relative">
                  <select
                    value={subSkillId}
                    onChange={(e) => setSubSkillId(e.target.value)}
                    className="w-full pl-12 pr-4 py-3 bg-white dark:bg-[#161615] border border-[#1914001a] dark:border-[#fffaed1a] rounded-xl appearance-none focus:outline-none focus:ring-2 focus:ring-[#f53003] transition-all font-medium"
                  >
                    <option value="">Chọn võ công phụ</option>
                    {skills.map((skill) => (
                      <option key={skill.id} value={skill.id}>
                        {skill.name}
                      </option>
                    ))}
                  </select>
                  <div className="absolute left-3 top-1/2 -translate-y-1/2 w-7 h-7 flex items-center justify-center">
                    {subSkillId ? (
                      <img
                        src={`/icon/skill/${skills.find(s => s.id == subSkillId)?.icon}`}
                        alt="skill"
                        className="w-full h-full object-contain"
                      />
                    ) : (
                      <div className="w-5 h-5 border-2 border-dashed border-[#1914001a] dark:border-[#fffaed1a] rounded-md" />
                    )}
                  </div>
                  <div className="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" className="text-[#706f6c]">
                      <path d="m6 9 6 6 6-6"/>
                    </svg>
                  </div>
                </div>
              </div>
            </div>

            <h3 className="text-sm font-bold mb-4 text-[#706f6c] dark:text-[#A1A09A] uppercase tracking-wider">
              Tâm Pháp Võ Công
            </h3>
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
              {innerWays.map((iw) => (
                <div key={iw.slug} className="p-4 rounded-xl border border-[#1914000a] dark:border-[#fffaed0a] bg-[#19140002] dark:bg-[#fffaed02] flex items-center gap-4">
                  <div className="w-12 h-12 flex-shrink-0">
                    <img src={`/icon/inner_way/${iw.icon}`} alt={iw.name} className="w-full h-full object-contain drop-shadow-sm" />
                  </div>
                  <div className="flex-1 min-w-0">
                    <div className="text-sm font-bold truncate mb-2 capitalize">{iw.name}</div>
                    <div className="flex items-center gap-1">
                      {[1, 2, 3, 4, 5, 6].map((lv) => (
                        <button
                          key={lv}
                          type="button"
                          onClick={() => handleLevelChange(iw.slug, lv)}
                          className={`w-7 h-7 rounded-lg text-[10px] font-bold transition-all ${
                            iw.level === lv
                              ? "bg-[#f53003] text-white"
                              : "bg-[#1914000a] dark:bg-[#fffaed0a] text-[#706f6c] dark:text-[#A1A09A] hover:bg-[#1914001a] dark:hover:bg-[#fffaed1a]"
                          }`}
                        >
                          {lv}
                        </button>
                      ))}
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </div>

          <div className="p-6 border-t border-[#1914000a] dark:border-[#fffaed0a] bg-[#19140002] dark:bg-[#fffaed02] flex justify-end gap-3">
            <button
              type="button"
              onClick={onClose}
              className="px-6 py-2 rounded-xl text-sm font-medium border border-[#1914001a] dark:border-[#fffaed1a] hover:bg-[#19140005] dark:hover:bg-[#fffaed05] transition-colors"
            >
              Hủy
            </button>
            <button
              type="submit"
              disabled={saving}
              className="px-8 py-2 bg-[#f53003] text-white rounded-xl text-sm font-bold hover:bg-[#d42a02] transition-colors disabled:opacity-50 flex items-center gap-2"
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
    </div>
  );
}
