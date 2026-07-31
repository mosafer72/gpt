"use client";

import { memo } from "react";
import ReactMarkdown from "react-markdown";
import remarkMath from "remark-math";
import rehypeKatex from "rehype-katex";

import ChatImage from "./ChatImage";
import CopyButton from "./CopyButton";
import CodeBlock from "./CodeBlock";
import PaperAttachment from "./PaperAttachment";

import { Message } from "@/types";

import styles from "./ChatMessage.module.css";

function ChatMessage({
  message,
  className,
}: {
  message: Message;
  className?: string;
}) {
  const isUser = message.role === "user";

  // ✅ پشتیبانی از چندین منبع تصویر
  const imageUrl =
    (message as any).image_url ||
    (message as any).metadata?.image_url ||
    (message as any).metadata?.attachments?.[0]?.url ||
    (message as any).image;
  const paperId = (message as any).metadata?.paper_id;

  return (
    <div
      className={`${styles.wrapper} ${isUser ? styles.user : styles.assistant} ${
        className || ""
      }`}
    >
      <div className={styles.avatar}>{isUser ? "👤" : "✨"}</div>

      <div className={styles.bubble}>
        {/* ✅ نمایش تصویر اگر وجود داشته باشد */}
        {imageUrl && <ChatImage src={imageUrl} />}
        
        {paperId && <PaperAttachment paperId={paperId} />}

        {/* ✅ نمایش متن */}
        {message.content && (
          <div className={styles.text}>
            <ReactMarkdown
              remarkPlugins={[remarkMath]}
              rehypePlugins={[rehypeKatex]}
              components={{
                p({ children }) {
                  return <div className={styles.paragraph}>{children}</div>;
                },
                pre({ children }) {
                  return <>{children}</>;
                },
                code({ inline, className, children }: any) {
                  const match = /language-(\w+)/.exec(className || "");
                  if (!inline) {
                    return (
                      <CodeBlock language={match?.[1]}>
                        {String(children).replace(/\n$/, "")}
                      </CodeBlock>
                    );
                  }
                  return <code className={className}>{children}</code>;
                },
              }}
            >
              {message.content}
            </ReactMarkdown>

            <CopyButton text={message.content} />
          </div>
        )}
      </div>
    </div>
  );
}

export default memo(ChatMessage);