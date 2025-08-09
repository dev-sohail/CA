"use client";

import React, { useEffect, useRef, useState } from "react";

export function ChatInput({ onSend, disabled }: { onSend: (text: string) => void; disabled?: boolean }) {
  const [value, setValue] = useState("");
  const textareaRef = useRef<HTMLTextAreaElement | null>(null);

  useEffect(() => {
    const el = textareaRef.current;
    if (!el) return;
    el.style.height = "0px";
    const scrollHeight = el.scrollHeight;
    el.style.height = Math.min(scrollHeight, 200) + "px";
  }, [value]);

  const submit = () => {
    const text = value.trim();
    if (!text) return;
    onSend(text);
    setValue("");
  };

  const onKeyDown: React.KeyboardEventHandler<HTMLTextAreaElement> = (e) => {
    if (e.key === "Enter" && !e.shiftKey) {
      e.preventDefault();
      submit();
    }
  };

  return (
    <div className="w-full border-t border-zinc-200 dark:border-zinc-800 bg-white/80 dark:bg-zinc-950/80 backdrop-blur">
      <div className="mx-auto max-w-3xl px-4 sm:px-6 md:px-8 py-4">
        <div className="relative">
          <textarea
            ref={textareaRef}
            rows={1}
            className="w-full resize-none rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-4 py-3 pr-14 text-[15px] leading-6 outline-none focus:ring-2 focus:ring-emerald-500"
            placeholder="Send a message..."
            value={value}
            onChange={(e) => setValue(e.target.value)}
            onKeyDown={onKeyDown}
            disabled={disabled}
          />
          <button
            onClick={submit}
            disabled={disabled || value.trim().length === 0}
            className="absolute right-2 bottom-2 inline-flex h-9 items-center justify-center rounded-md bg-emerald-600 px-3 text-sm font-medium text-white enabled:hover:bg-emerald-700 disabled:opacity-50"
            aria-label="Send"
            title="Send"
          >
            Send
          </button>
        </div>
        <p className="mt-2 text-xs text-zinc-500">Press Enter to send, Shift+Enter for new line.</p>
      </div>
    </div>
  );
}