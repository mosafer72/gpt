"use client";

import ReactMarkdown from "react-markdown";
import remarkMath from "remark-math";
import rehypeKatex from "rehype-katex";
import ChatImage from "./ChatImage";

import { Prism as SyntaxHighlighter } from "react-syntax-highlighter";
import { oneLight } from "react-syntax-highlighter/dist/esm/styles/prism";
import CodeBlock from "./CodeBlock";

import { Message } from "@/types";
import styles from "./ChatMessage.module.css";

export default function ChatMessage({ message }: { message: Message }) {
  const isUser = message.role === "user";
  const isImage = message.content.startsWith("data:image/");
  const imageUrl =
    (message as any).image_url ||
    (message as any).metadata?.attachments?.[0]?.url;
  return (
    <div className={styles.bubble}>
      {imageUrl ? (
        <ChatImage src={imageUrl} />
      ) : (
        <div className={styles.text}>
          <ReactMarkdown
            remarkPlugins={[remarkMath]}
            rehypePlugins={[rehypeKatex]}
            components={{
              code({ className, children, ...props }) {
                const match = /language-(\w+)/.exec(className || "");

                if (match) {
                  return (
                    <SyntaxHighlighter language={match[1]}>
                      {String(children).replace(/\n$/, "")}
                    </SyntaxHighlighter>
                  );
                }

                return (
                  <code className={className} {...props}>
                    {children}
                  </code>
                );
              },

              p({ children }) {
                return <div className={styles.paragraph}>{children}</div>;
              },
            }}
          >
            {message.content}
          </ReactMarkdown>
        </div>
      )}
    </div>
  );
}
