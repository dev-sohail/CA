"use client";

import React, { useEffect } from "react";
import ReactMarkdown from "react-markdown";
import remarkGfm from "remark-gfm";
import rehypeRaw from "rehype-raw";
import hljs from "highlight.js";
import "highlight.js/styles/github-dark-dimmed.css";

export type ChatMessage = {
  id: string;
  role: "user" | "assistant" | "system";
  content: string;
};

export function Message({ message }: { message: ChatMessage }) {
  useEffect(() => {
    document.querySelectorAll("pre code").forEach((block) => {
      try {
        hljs.highlightElement(block as HTMLElement);
      } catch {}
    });
  }, [message.content]);

  const isAssistant = message.role === "assistant";

  return (
    <div className={`w-full px-4 sm:px-6 md:px-8 py-6 ${isAssistant ? "bg-zinc-50 dark:bg-zinc-900/60" : "bg-transparent"}`}>
      <div className="mx-auto max-w-3xl">
        <div className="flex items-start gap-4">
          <div className={`h-8 w-8 shrink-0 rounded-md flex items-center justify-center text-sm font-semibold ${isAssistant ? "bg-emerald-600 text-white" : "bg-zinc-200 dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100"}`}>
            {isAssistant ? "G" : "U"}
          </div>
          <div className="prose prose-zinc dark:prose-invert max-w-none prose-pre:overflow-x-auto prose-pre:rounded-md prose-pre:bg-zinc-100 dark:prose-pre:bg-zinc-900 prose-code:before:content-[''] prose-code:after:content-['']">
            <ReactMarkdown remarkPlugins={[remarkGfm]} rehypePlugins={[rehypeRaw]}>{message.content}</ReactMarkdown>
          </div>
        </div>
      </div>
    </div>
  );
}