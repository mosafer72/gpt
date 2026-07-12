"use client";

import ReactMarkdown from "react-markdown";
import remarkMath from "remark-math";
import rehypeKatex from "rehype-katex";
import ChatImage from "./ChatImage";
import { memo } from "react";


import { Prism as SyntaxHighlighter } from "react-syntax-highlighter";

import { Message } from "@/types";
import styles from "./ChatMessage.module.css";

function ChatMessage({ message }: { message: Message }) {

  const isUser = message.role === "user";

  const imageUrl =
    (message as any).image_url ||
    (message as any).metadata?.attachments?.[0]?.url;

  return (
    <div
      className={`${styles.wrapper} ${isUser ? styles.user : styles.assistant}`}
    >
      <div className={styles.avatar}>{isUser ? "👤" : "✨"}</div>

      <div className={styles.bubble}>
        <div className={styles.bubble}>
          {imageUrl && <ChatImage src={imageUrl} />}

          {message.content && (
            <div className={styles.text}>
              <ReactMarkdown
                remarkPlugins={[remarkMath]}
                rehypePlugins={[rehypeKatex]}
              >
                {message.content}
              </ReactMarkdown>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
export default memo(ChatMessage);

