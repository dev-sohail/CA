"use client";

import React, { useEffect, useRef, useState } from "react";
import { Message, ChatMessage } from "./Message";
import { ChatInput } from "./ChatInput";
import { Sidebar, useLocalConversations } from "./Sidebar";

function createUserMessage(text: string): ChatMessage {
  return { id: crypto.randomUUID(), role: "user", content: text };
}

function createAssistantMessage(): ChatMessage {
  return { id: crypto.randomUUID(), role: "assistant", content: "" };
}

export default function Chat() {
  const containerRef = useRef<HTMLDivElement | null>(null);
  const { conversations, activeId, active, newChat, select, remove, updateActive } = useLocalConversations();

  useEffect(() => {
    if (activeId === null && conversations.length === 0) {
      newChat();
    }
  }, [activeId, conversations.length, newChat]);

  const [isLoading, setIsLoading] = useState(false);

  useEffect(() => {
    const el = containerRef.current;
    if (!el) return;
    el.scrollTop = el.scrollHeight;
  }, [active?.messages]);

  const send = async (text: string) => {
    if (!active) return;

    // Append user message and a placeholder assistant message
    updateActive((c) => ({
      ...c,
      title: c.messages.length === 0 ? text.slice(0, 60) : c.title,
      messages: [...c.messages, createUserMessage(text), createAssistantMessage()],
      updatedAt: Date.now(),
    }));

    setIsLoading(true);

    const apiMessages = (active.messages ?? []).map((m) => ({ role: m.role, content: m.content }));
    apiMessages.push({ role: "user", content: text });

    try {
      const res = await fetch("/api/chat", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ messages: apiMessages }),
      });

      if (!res.ok || !res.body) {
        throw new Error(`Request failed: ${res.status}`);
      }

      const reader = res.body.getReader();
      const decoder = new TextDecoder();

      let assistantText = "";

      while (true) {
        const { value, done } = await reader.read();
        if (done) break;
        assistantText += decoder.decode(value, { stream: true });
        const currentText = assistantText;
        updateActive((c) => ({
          ...c,
          messages: c.messages.map((m, idx, arr) => (idx === arr.length - 1 ? { ...m, content: currentText } : m)),
          updatedAt: Date.now(),
        }));
      }
    } catch (error) {
      const message = error instanceof Error ? error.message : String(error);
      updateActive((c) => ({
        ...c,
        messages: c.messages.map((m, idx, arr) => (idx === arr.length - 1 ? { ...m, content: `Error: ${message}` } : m)),
      }));
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="flex min-h-screen w-full">
      <Sidebar
        conversations={conversations}
        activeId={activeId}
        onSelect={select}
        onNew={newChat}
        onDelete={remove}
      />
      <main className="flex-1 flex flex-col">
        <header className="sticky top-0 z-10 border-b border-zinc-200 dark:border-zinc-800 bg-white/80 dark:bg-zinc-950/80 backdrop-blur">
          <div className="mx-auto max-w-3xl px-4 sm:px-6 md:px-8 h-14 flex items-center justify-between">
            <div className="flex items-center gap-2">
              <span className="text-sm text-zinc-500">Chat</span>
              {active?.title && <span className="text-sm font-medium text-zinc-900 dark:text-zinc-100">{active.title}</span>}
            </div>
            <button onClick={newChat} className="text-sm text-emerald-600 hover:underline">New chat</button>
          </div>
        </header>
        <div ref={containerRef} className="flex-1 overflow-y-auto bg-white dark:bg-zinc-950">
          <div className="mx-auto max-w-3xl">
            {active?.messages?.length ? (
              active.messages.map((m) => <Message key={m.id} message={m} />)
            ) : (
              <div className="px-4 sm:px-6 md:px-8 py-16 text-center">
                <h1 className="text-2xl font-semibold mb-2">ChatGPT Clone</h1>
                <p className="text-zinc-500">Start by typing a message below.</p>
              </div>
            )}
          </div>
        </div>
        <ChatInput onSend={send} disabled={!active || isLoading} />
      </main>
    </div>
  );
}