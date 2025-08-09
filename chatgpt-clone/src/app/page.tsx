"use client";

import { useEffect, useMemo, useRef, useState } from "react";
import { v4 as uuidv4 } from "uuid";
import { Markdown } from "@/components/Markdown";
import { Plus, Send, Trash2 } from "lucide-react";

export type ChatMessage = {
  id: string;
  role: "system" | "user" | "assistant";
  content: string;
};

export type ChatThread = {
  id: string;
  title: string;
  createdAt: number;
  messages: ChatMessage[];
  model: string;
};

const DEFAULT_SYSTEM =
  "You are ChatGPT, a large language model. Answer helpfully, concisely, and use Markdown when appropriate.";

const DEFAULT_MODEL = "gpt-4o-mini";

function useLocalStorage<T>(key: string, initialValue: T) {
  const [value, setValue] = useState<T>(() => {
    try {
      const raw = localStorage.getItem(key);
      return raw ? (JSON.parse(raw) as T) : initialValue;
    } catch {
      return initialValue;
    }
  });
  useEffect(() => {
    try {
      localStorage.setItem(key, JSON.stringify(value));
    } catch {}
  }, [key, value]);
  return [value, setValue] as const;
}

function Sidebar({
  threads,
  activeId,
  onNew,
  onSelect,
  onDelete,
}: {
  threads: ChatThread[];
  activeId: string | null;
  onNew: () => void;
  onSelect: (id: string) => void;
  onDelete: (id: string) => void;
}) {
  return (
    <aside className="hidden md:flex md:flex-col w-64 shrink-0 border-r border-zinc-200 dark:border-zinc-800 bg-zinc-50/60 dark:bg-zinc-900/40">
      <div className="p-3 flex items-center gap-2 border-b border-zinc-200 dark:border-zinc-800">
        <button
          className="inline-flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 hover:opacity-90"
          onClick={onNew}
        >
          <Plus className="h-4 w-4" /> New chat
        </button>
      </div>
      <div className="flex-1 overflow-y-auto p-2">
        {threads.length === 0 ? (
          <div className="text-sm text-zinc-500 p-3">No conversations</div>
        ) : (
          <ul className="space-y-1">
            {threads.map((t) => (
              <li key={t.id}>
                <button
                  onClick={() => onSelect(t.id)}
                  className={`group w-full text-left px-3 py-2 rounded-md text-sm flex items-center justify-between ${
                    t.id === activeId
                      ? "bg-zinc-200/70 dark:bg-zinc-800/70"
                      : "hover:bg-zinc-200/50 dark:hover:bg-zinc-800/50"
                  }`}
                >
                  <span className="line-clamp-1 pr-2">{t.title || "New chat"}</span>
                  <span
                    className="opacity-0 group-hover:opacity-100 transition"
                    onClick={(e) => {
                      e.stopPropagation();
                      onDelete(t.id);
                    }}
                    title="Delete"
                  >
                    <Trash2 className="h-4 w-4 text-zinc-500 hover:text-red-500" />
                  </span>
                </button>
              </li>
            ))}
          </ul>
        )}
      </div>
    </aside>
  );
}

export default function Home() {
  const [threads, setThreads] = useLocalStorage<ChatThread[]>(
    "chatgpt-clone:threads",
    []
  );
  const [activeId, setActiveId] = useLocalStorage<string | null>(
    "chatgpt-clone:activeId",
    null
  );

  const activeThread = useMemo(
    () => threads.find((t) => t.id === activeId) || null,
    [threads, activeId]
  );

  const [input, setInput] = useState("");
  const [systemPrompt, setSystemPrompt] = useLocalStorage(
    "chatgpt-clone:system",
    DEFAULT_SYSTEM
  );
  const [isLoading, setIsLoading] = useState(false);
  const abortRef = useRef<AbortController | null>(null);
  const inputRef = useRef<HTMLTextAreaElement>(null);

  useEffect(() => {
    if (!activeThread) {
      handleNewChat();
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  function upsertThread(next: ChatThread) {
    setThreads((prev) => {
      const exists = prev.some((t) => t.id === next.id);
      const updated = exists ? prev.map((t) => (t.id === next.id ? next : t)) : [next, ...prev];
      return updated;
    });
  }

  function handleNewChat() {
    const id = uuidv4();
    const newThread: ChatThread = {
      id,
      title: "New chat",
      createdAt: Date.now(),
      messages: [
        { id: uuidv4(), role: "system", content: systemPrompt },
      ],
      model: DEFAULT_MODEL,
    };
    setActiveId(id);
    upsertThread(newThread);
    setInput("");
    abortRef.current?.abort();
    abortRef.current = null;
  }

  function handleDelete(id: string) {
    setThreads((prev) => prev.filter((t) => t.id !== id));
    if (activeId === id) {
      setActiveId(null);
    }
  }

  async function sendMessage() {
    if (!activeThread || !input.trim() || isLoading) return;

    const userMessage: ChatMessage = {
      id: uuidv4(),
      role: "user",
      content: input.trim(),
    };

    const placeholderAssistant: ChatMessage = {
      id: uuidv4(),
      role: "assistant",
      content: "",
    };

    const updated: ChatThread = {
      ...activeThread,
      title:
        activeThread.title === "New chat" && userMessage.content
          ? userMessage.content.slice(0, 40)
          : activeThread.title,
      messages: [...activeThread.messages, userMessage, placeholderAssistant],
    };

    upsertThread(updated);
    setInput("");
    setIsLoading(true);

    const controller = new AbortController();
    abortRef.current = controller;

    try {
      const response = await fetch("/api/chat", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          model: updated.model,
          messages: updated.messages
            .filter((m) => m.role !== "system" || m.content.trim().length > 0)
            .map((m) => ({ role: m.role, content: m.content })),
        }),
        signal: controller.signal,
      });

      if (!response.ok || !response.body) {
        throw new Error(`Request failed: ${response.status}`);
      }

      const reader = response.body.getReader();
      const decoder = new TextDecoder();

      let done = false;
      let assistantContent = "";

      while (!done) {
        const { value, done: doneReading } = await reader.read();
        done = doneReading;
        const chunk = decoder.decode(value || new Uint8Array(), { stream: true });
        if (chunk) {
          assistantContent += chunk;
          upsertThread({
            ...updated,
            messages: updated.messages.map((m) =>
              m.id === placeholderAssistant.id ? { ...m, content: assistantContent } : m
            ),
          });
        }
      }
    } catch (error) {
      upsertThread({
        ...activeThread,
        messages: [
          ...activeThread.messages,
          userMessage,
          {
            id: placeholderAssistant.id,
            role: "assistant",
            content:
              error instanceof Error
                ? `Error: ${error.message}`
                : "An error occurred.",
          },
        ],
      });
    } finally {
      setIsLoading(false);
      abortRef.current = null;
      inputRef.current?.focus();
    }
  }

  function handleKeyDown(e: React.KeyboardEvent<HTMLTextAreaElement>) {
    if (e.key === "Enter" && !e.shiftKey) {
      e.preventDefault();
      sendMessage();
    }
  }

  function setActiveModel(model: string) {
    if (!activeThread) return;
    upsertThread({ ...activeThread, model });
  }

  return (
    <div className="h-screen w-full grid grid-cols-1 md:grid-cols-[256px_1fr]">
      <Sidebar
        threads={threads}
        activeId={activeId}
        onNew={handleNewChat}
        onSelect={setActiveId}
        onDelete={handleDelete}
      />

      <main className="flex flex-col h-screen">
        {/* Header */}
        <div className="flex items-center justify-between h-14 px-3 border-b border-zinc-200 dark:border-zinc-800">
          <div className="flex items-center gap-2">
            <button
              className="md:hidden inline-flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900"
              onClick={handleNewChat}
              title="New chat"
            >
              <Plus className="h-4 w-4" />
            </button>
            <h1 className="text-sm font-medium text-zinc-700 dark:text-zinc-200">
              {activeThread?.title || "New chat"}
            </h1>
          </div>

          <div className="flex items-center gap-2">
            <select
              value={activeThread?.model || DEFAULT_MODEL}
              onChange={(e) => setActiveModel(e.target.value)}
              className="text-sm rounded-md border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-2 py-1"
            >
              <option value="gpt-4o-mini">gpt-4o-mini</option>
              <option value="gpt-4o">gpt-4o</option>
              <option value="o4-mini">o4-mini (Reasoning)</option>
            </select>
          </div>
        </div>

        {/* Messages */}
        <div className="flex-1 overflow-y-auto">
          <div className="mx-auto max-w-3xl px-3 py-6 space-y-6">
            {!activeThread || activeThread.messages.filter((m) => m.role !== "system").length === 0 ? (
              <div className="text-center text-zinc-500 text-sm">
                Start chatting by typing a message below.
              </div>
            ) : (
              activeThread.messages
                .filter((m) => m.role !== "system")
                .map((m) => (
                  <div
                    key={m.id}
                    className={`rounded-lg px-4 py-3 border ${
                      m.role === "assistant"
                        ? "bg-zinc-50/50 dark:bg-zinc-900/40 border-zinc-200 dark:border-zinc-800"
                        : "bg-white/70 dark:bg-zinc-950/50 border-zinc-200 dark:border-zinc-800"
                    }`}
                  >
                    <div className="text-xs uppercase tracking-wide text-zinc-500 mb-2">
                      {m.role}
                    </div>
                    <Markdown>{m.content}</Markdown>
                  </div>
                ))
            )}
          </div>
        </div>

        {/* Composer */}
        <div className="border-t border-zinc-200 dark:border-zinc-800 bg-white/60 dark:bg-zinc-950/60">
          <div className="mx-auto max-w-3xl p-3">
            <div className="flex items-end gap-2">
              <textarea
                ref={inputRef}
                value={input}
                onChange={(e) => setInput(e.target.value)}
                onKeyDown={handleKeyDown}
                placeholder="Send a message..."
                rows={1}
                className="flex-1 resize-none rounded-md border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-zinc-400 dark:focus:ring-zinc-600"
              />
              <button
                onClick={sendMessage}
                disabled={isLoading || !input.trim()}
                className="inline-flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 disabled:opacity-50"
                title="Send"
              >
                <Send className="h-4 w-4" />
              </button>
            </div>
            <div className="mt-2">
              <label className="text-xs text-zinc-500">System prompt</label>
              <textarea
                value={systemPrompt}
                onChange={(e) => setSystemPrompt(e.target.value)}
                rows={2}
                className="mt-1 w-full resize-none rounded-md border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-xs"
              />
            </div>
          </div>
        </div>
      </main>
    </div>
  );
}
