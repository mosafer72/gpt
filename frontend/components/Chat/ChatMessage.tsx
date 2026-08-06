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

  let textContent = message.content || "";
  let jsonImage: string | null = null;

  /**
   * اصلاح شده برای پشتیبانی از پاسخ‌های استریم و فرمت‌های JSON ارسالی سرور
   */
  try {
    let trimmed = textContent.trim();

    // اگر پیام با data: شروع شده بود، آن را پاک کنیم تا به JSON برسیم
    if (trimmed.startsWith("data:")) {
      trimmed = trimmed.replace(/^data:\s*/, "").trim();
    }

    if (trimmed.startsWith("{") && trimmed.endsWith("}")) {
      const parsed = JSON.parse(trimmed);

      // بررسی ساختار choices (استریم یا پاسخ‌های پکیج‌شده)
      if (
        parsed.choices &&
        Array.isArray(parsed.choices) &&
        parsed.choices.length > 0
      ) {
        const delta =
          parsed.choices[0].delta || parsed.choices[0].message || {};
        if (delta.content !== undefined) {
          textContent = delta.content;
        }
        if (delta.image) {
          jsonImage = delta.image;
        }
      }

      // بررسی ساختار مستقیم text و image
      if (parsed.text !== undefined) {
        textContent = parsed.text;
      }

      if (parsed.image) {
        jsonImage = parsed.image;
      }
    }
  } catch (e) {
    // اگر JSON نبود، متن به همان شکل عادی رندر می‌شود
  }

  /**
   * تمام حالت‌های ممکن تصویر
   */
  const imageUrl =
    jsonImage ||
    // stream response
    (message as any).image ||
    // database
    (message as any).image_url ||
    (message as any).metadata?.image_url ||
    (message as any).metadata?.attachments?.image_base64 ||
    (message as any).metadata?.attachments?.url ||
    null;

  const paperId = (message as any).metadata?.paper_id;

  return (
    <div
      className={`
        ${styles.wrapper}
        ${isUser ? styles.user : styles.assistant}
        ${className || ""}
      `}
    >
      <div className={styles.avatar}>{isUser ? "👤" : "✨"}</div>

      <div className={styles.bubble}>
        {imageUrl && (
          <div
            style={{
              marginBottom: textContent ? "10px" : "0",
            }}
          >
            <ChatImage src={imageUrl} />
          </div>
        )}

        {paperId && <PaperAttachment paperId={paperId} />}

        {textContent && (
          <div className={styles.text}>
            {isUser ? (
              <div
                style={{
                  whiteSpace: "pre-wrap",
                  wordBreak: "break-word",
                  direction: "ltr",
                  textAlign: "left",
                  fontFamily:
                    "ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace",
                }}
              >
                {textContent}
              </div>
            ) : (
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
                {textContent}
              </ReactMarkdown>
            )}

            <CopyButton text={textContent} />
          </div>
        )}
      </div>
    </div>
  );
}

export default memo(ChatMessage);
