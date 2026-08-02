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

  // ✅ بررسی و استخراج محتوا در صورتی که به صورت JSON (متن + تصویر) ذخیره شده باشد
  let textContent = message.content || "";
  let jsonImage = null;

  try {
    if (textContent.trim().startsWith("{") && textContent.trim().endsWith("}")) {
      const parsed = JSON.parse(textContent);
      if (parsed.text !== undefined) {
        textContent = parsed.text;
      }
      if (parsed.image) {
        jsonImage = parsed.image;
      }
    }
  } catch (e) {
    // اگر JSON نبود، به صورت متن معمولی در نظر گرفته می‌شود
  }

  // ✅ پشتیبانی کامل از چندین منبع تصویر (Base64 یا URL مستقیم)
  const imageUrl =
    jsonImage ||
    (message as any).image_url ||
    (message as any).metadata?.image_url ||
    (message as any).metadata?.attachments?.image_base64 ||
    (message as any).metadata?.attachments?.[0]?.url ||
    (message as any).image;

  const paperId = (message as (any)).metadata?.paper_id;

  return (
    <div
      className={`${styles.wrapper} ${isUser ? styles.user : styles.assistant} ${
        className || ""
      }`}
    >
      <div className={styles.avatar}>{isUser ? "👤" : "✨"}</div>

      <div className={styles.bubble}>
        {/* ✅ نمایش تصویر با استفاده از کامپوننت قبلی ChatImage */}
        {imageUrl && (
          <div style={{ marginBottom: textContent ? "10px" : "0" }}>
            <ChatImage src={imageUrl} />
          </div>
        )}
        
        {paperId && <PaperAttachment paperId={paperId} />}

        {/* ✅ نمایش متن (اگر وجود داشته باشد) */}
        {textContent && (
          <div className={styles.text}>
            {isUser ? (
              // اگر پیام کاربر باشد: حفظ اینترها + چپ‌چین و LTR کردن برای نمایش صحیح کدها
              <div 
                style={{ 
                  whiteSpace: "pre-wrap", 
                  wordBreak: "break-word",
                  direction: "ltr",
                  textAlign: "left",
                  fontFamily: "ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace"
                }}
              >
                {textContent}
              </div>
            ) : (
              // برای پاسخ مدل از مارک‌داون کامل استفاده می‌شود
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