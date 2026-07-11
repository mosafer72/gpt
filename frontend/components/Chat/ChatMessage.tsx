"use client";

import ReactMarkdown from "react-markdown";
import remarkMath from "remark-math";
import rehypeKatex from "rehype-katex";

import { Message } from "@/types";
import styles from "./ChatMessage.module.css";

export default function ChatMessage({ message }: { message: Message }) {
  const isUser = message.role === "user";
  const isImage = message.content.startsWith("data:image/");

  return (
    <div
      className={`${styles.wrapper} ${
        isUser ? styles.user : styles.assistant
      }`}
    >
      <div className={styles.avatar}>
        {isUser ? "👤" : "✨"}
      </div>

      <div className={styles.bubble}>
        {isImage ? (
          <img
            src={message.content}
            alt="تصویر"
            className={styles.image}
          />
        ) : (
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
  );
}