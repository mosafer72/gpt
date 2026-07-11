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

  return (
    <div
      className={`${styles.wrapper} ${isUser ? styles.user : styles.assistant}`}
    >
      <div className={styles.avatar}>{isUser ? "👤" : "✨"}</div>

      <div className={styles.bubble}>
        {isImage ? (
          <ChatImage src={message.content} />
        ) : (
          <div className={styles.text}>
            <ReactMarkdown
              remarkPlugins={[remarkMath]}
              rehypePlugins={[rehypeKatex]}
              components={{
                code({ inline, className, children }) {
                  const match = /language-(\w+)/.exec(className || "");

                  if (!inline && match) {
                    return (
                      <CodeBlock language={match[1]}>
                        {String(children)}
                      </CodeBlock>
                    );
                  }

                  return <code>{children}</code>;
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
    </div>
  );
}
