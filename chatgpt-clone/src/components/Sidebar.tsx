"use client";

import React, { useEffect, useMemo, useState } from "react";
import { ChatMessage } from "./Message";

export type Conversation = {
  id: string;
  title: string;
  messages: ChatMessage[];
  createdAt: number;
  updatedAt: number;
};

function loadConversations(): Conversation[] {
  if (typeof window === "undefined") return [];
  try {
    const raw = localStorage.getItem("conversations");
    if (!raw) return [];
    const parsed = JSON.parse(raw) as Conversation[];
    return Array.isArray(parsed) ? parsed : [];
  } catch {
    return [];
  }
}

function saveConversations(conversations: Conversation[]) {
  try {
    localStorage.setItem("conversations", JSON.stringify(conversations));
  } catch {}
}

export function Sidebar({
  conversations,
  activeId,
  onSelect,
  onNew,
  onDelete,
}: {
  conversations: Conversation[];
  activeId?: string | null;
  onSelect: (id: string) => void;
  onNew: () => void;
  onDelete: (id: string) => void;
}) {
  return (
    <aside className="hidden md:flex md:w-72 lg:w-80 shrink-0 flex-col border-r border-zinc-200 dark:border-zinc-800 bg-zinc-50/60 dark:bg-zinc-900/40">
      <div className="p-4 border-b border-zinc-200 dark:border-zinc-800">
        <button onClick={onNew} className="w-full h-9 rounded-md bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">
          + New chat
        </button>
      </div>
      <div className="flex-1 overflow-y-auto p-2">
        {conversations.length === 0 ? (
          <p className="px-2 py-3 text-sm text-zinc-500">No conversations yet.</p>
        ) : (
          <ul className="space-y-1">
            {conversations.map((c) => (
              <li key={c.id}>
                <button
                  className={`group w-full flex items-center justify-between gap-2 rounded-md px-3 py-2 text-left text-sm hover:bg-zinc-200/80 dark:hover:bg-zinc-800/60 ${activeId === c.id ? "bg-zinc-200/80 dark:bg-zinc-800/60" : ""}`}
                  onClick={() => onSelect(c.id)}
                  title={c.title}
                >
                  <span className="truncate">{c.title}</span>
                  <span
                    className="opacity-0 group-hover:opacity-100 text-zinc-500 hover:text-red-600"
                    onClick={(e) => {
                      e.stopPropagation();
                      onDelete(c.id);
                    }}
                  >
                    ✕
                  </span>
                </button>
              </li>
            ))}
          </ul>
        )}
      </div>
      <div className="p-4 border-t border-zinc-200 dark:border-zinc-800 text-xs text-zinc-500">
        ChatGPT-like clone. Not affiliated with OpenAI.
      </div>
    </aside>
  );
}

export function useLocalConversations() {
  const [conversations, setConversations] = useState<Conversation[]>([]);
  const [activeId, setActiveId] = useState<string | null>(null);

  useEffect(() => {
    const initial = loadConversations();
    setConversations(initial);
    setActiveId(initial[0]?.id ?? null);
  }, []);

  useEffect(() => {
    saveConversations(conversations);
  }, [conversations]);

  const active = useMemo(() => conversations.find((c) => c.id === activeId) ?? null, [conversations, activeId]);

  const newChat = () => {
    const id = crypto.randomUUID();
    const now = Date.now();
    const newConv: Conversation = { id, title: "New chat", messages: [], createdAt: now, updatedAt: now };
    setConversations((prev) => [newConv, ...prev]);
    setActiveId(id);
  };

  const select = (id: string) => setActiveId(id);

  const remove = (id: string) => {
    setConversations((prev) => prev.filter((c) => c.id !== id));
    setActiveId((prev) => (prev === id ? null : prev));
  };

  const updateActive = (updater: (conv: Conversation) => Conversation) => {
    setConversations((prev) => prev.map((c) => (c.id === activeId ? updater(c) : c)));
  };

  return { conversations, activeId, active, newChat, select, remove, updateActive, setActiveId } as const;
}