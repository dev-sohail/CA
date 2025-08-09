"use client";

import React from "react";
import ReactMarkdown from "react-markdown";
import remarkGfm from "remark-gfm";
import remarkMath from "remark-math";
import rehypeKatex from "rehype-katex";
import rehypeRaw from "rehype-raw";
import rehypeHighlight from "rehype-highlight";

export function Markdown({ children }: { children: string }) {
  return (
    <div className="prose prose-zinc dark:prose-invert max-w-none">
      <ReactMarkdown
        remarkPlugins={[remarkGfm, remarkMath]}
        // Cast to unknown first to avoid any; libraries lack precise types here
        rehypePlugins={[rehypeRaw as unknown as never, rehypeKatex as unknown as never, rehypeHighlight as unknown as never]}
      >
        {children}
      </ReactMarkdown>
    </div>
  );
}