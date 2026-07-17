"use client";

import { useEffect, useState } from "react";
import { useParams, useRouter } from "next/navigation";
import { adminApi } from "@/lib/admin_api";
import type { Chat, Message } from "@/types/admin_types";
import styles from "./page.module.css";

export default function ChatDetailPage() {
  const params = useParams();
  const router = useRouter();
  const chatId = Number(params.id);

  const [chat, setChat] = useState<Chat | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (isNaN(chatId) || chatId <= 0) {
      setError("شناسه چت نامعتبر");
      setLoading(false);
      return;
    }

    adminApi.chats
      .get(chatId)
      .then((data) => {
        console.log(data)
        setChat(data);
        setLoading(false);
      })
      .catch((err) => {
        setError(err.message);
        setLoading(false);
      });
  }, [chatId]);

  // دریافت اطلاعات متادیتا از پیام
  const getMessageMetadata = (msg: Message) => {
    const metadata = (msg as any).metadata;
    if (!metadata) return null;
    return {
      model: metadata.model || "نامشخص",
      tokenUsage: metadata.token_usage || null,
    };
  };

  // فرمت زمان
  const formatTime = (dateStr: string) => {
    return new Date(dateStr).toLocaleString("fa-IR", {
      hour: "2-digit",
      minute: "2-digit",
      second: "2-digit",
    });
  };

  if (loading) return <div className={styles.loading}>⏳ در حال بارگذاری...</div>;
  if (error) return <div className={styles.error}>❌ {error}</div>;
  if (!chat) return <div className={styles.error}>❌ چت یافت نشد</div>;

  return (
    <div className={styles.page}>
      <div className={styles.header}>
        <button className={styles.backBtn} onClick={() => router.back()}>
          ← بازگشت
        </button>
        <h1 className={styles.title}>
          💬 {chat.title || "چت بدون عنوان"}
        </h1>
      </div>

      <div className={styles.infoGrid}>
        <div className={styles.card}>
          <span className={styles.label}>شناسه</span>
          <span className={styles.value}>#{chat.id}</span>
        </div>
        <div className={styles.card}>
          <span className={styles.label}>کاربر</span>
          <span className={styles.value}>
            {chat.user?.name || chat.user?.phone || "کاربر مهمان"}
          </span>
        </div>
        <div className={styles.card}>
          <span className={styles.label}>تاریخ ایجاد</span>
          <span className={styles.value}>
            {new Date(chat.created_at).toLocaleDateString("fa-IR")}
          </span>
        </div>
        <div className={styles.card}>
          <span className={styles.label}>تعداد پیام</span>
          <span className={styles.value}>{chat.messages?.length || 0}</span>
        </div>
      </div>

      <div className={styles.messagesSection}>
        <h2>📝 پیام‌ها</h2>
        {chat.messages && chat.messages.length > 0 ? (
          <div className={styles.messagesList}>
            {chat.messages.map((msg) => {
              const meta = getMessageMetadata(msg);
              return (
                <div
                  key={msg.id}
                  className={`${styles.messageItem} ${
                    msg.role === "user" ? styles.user : styles.assistant
                  }`}
                >
                  <div className={styles.messageAvatar}>
                    {msg.role === "user" ? "👤" : "✨"}
                  </div>
                  <div className={styles.messageBubble}>
                    <div className={styles.messageContent}>{msg.content}</div>
                    <div className={styles.messageMeta}>
                      <span className={styles.messageTime}>
                        {formatTime(msg.created_at)}
                      </span>
                      {meta && (
                        <>
                          <span className={styles.modelBadge}>
                            🤖 {meta.model}
                          </span>
                          {meta.tokenUsage && (
                            <span className={styles.tokenBadge}>
                              🪙 {meta.tokenUsage.total_tokens || 0} توکن
                              <span className={styles.tokenDetail}>
                                (ورودی: {meta.tokenUsage.prompt_tokens || 0} | 
                                خروجی: {meta.tokenUsage.completion_tokens || 0})
                              </span>
                            </span>
                          )}
                        </>
                      )}
                    </div>
                  </div>
                </div>
              );
            })}
          </div>
        ) : (
          <div className={styles.emptyMessages}>هیچ پیامی در این چت وجود ندارد</div>
        )}
      </div>
    </div>
  );
}