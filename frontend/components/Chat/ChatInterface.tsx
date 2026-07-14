"use client";

import { useState, useEffect, useRef, FormEvent, useCallback } from "react";
import { sendMessageStream } from "@/lib/api";
import { useRouter } from "next/navigation";
import { Chat } from "@/types";
import { getChats, deleteChat, getChat, getUserStatus } from "@/lib/api";
import Sidebar from "@/components/Sidebar/Sidebar";
import ChatMessage from "@/components/Chat/ChatMessage";
import ChatInput from "@/components/Chat/ChatInput";
import UserMenu from "@/components/Common/UserMenu";
import SubscriptionWarning from "@/components/Chat/SubscriptionWarning";
import TokenUsageBadge from "../Common/TokenUsageBadge";
import styles from "./ChatInterface.module.css";

// هوک سفارشی برای تشخیص اندازه صفحه
function useMediaQuery(query: string): boolean {
  const [matches, setMatches] = useState(false);
  useEffect(() => {
    const media = window.matchMedia(query);
    if (media.matches !== matches) setMatches(media.matches);
    const listener = (e: MediaQueryListEvent) => setMatches(e.matches);
    media.addEventListener("change", listener);
    return () => media.removeEventListener("change", listener);
  }, [matches, query]);
  return matches;
}

export default function ChatInterface() {
  const router = useRouter();
  const cancelledRef = useRef(false);

  const [chats, setChats] = useState<Chat[]>([]);
  const [currentChat, setCurrentChat] = useState<Chat | null>(null);
  const [input, setInput] = useState("");
  const [loading, setLoading] = useState(false);
  const [isLoadingChats, setIsLoadingChats] = useState(true);
  const [imageFile, setImageFile] = useState<File | null>(null);
  const inputRef = useRef<HTMLTextAreaElement>(null);
  const abortControllerRef = useRef<AbortController | null>(null);
  const [userStatus, setUserStatus] = useState<{
    has_subscription: boolean;
    daily_message_limit: number;
    today_messages_count: number;
    remaining_messages: number;
  } | null>(null);

  // ref برای آخرین پیام (برای اسکرول با دکمه)
  const lastMessageRef = useRef<HTMLDivElement | null>(null);

  // state برای نمایش خطاهای استریم
  const [streamError, setStreamError] = useState<string | null>(null);

  const streams = useRef<
    Record<
      string,
      {
        buffer: string;
        timer: any;
        active: boolean;
      }
    >
  >({});

  const isMobile = useMediaQuery("(max-width: 767px)");
  const [sidebarOpen, setSidebarOpen] = useState(false);

  useEffect(() => {
    const token = localStorage.getItem("auth_token");
    if (!token) {
      router.push("/login");
      return;
    }
    loadChats();
    fetchUserStatus();
  }, []);

  const fetchUserStatus = async () => {
    try {
      const status = await getUserStatus();
      setUserStatus(status);
    } catch (error) {
      console.error(error);
    }
  };

  // اسکرول خودکار حذف شد (فقط با کلیک دکمه)

  const loadChats = async () => {
    try {
      const data = await getChats();
      setChats(data);
      if (data.length && !currentChat) {
        setCurrentChat(data[0]);
      }
    } catch (error) {
      console.error(error);
    } finally {
      setIsLoadingChats(false);
    }
  };

  const handleSelectChat = async (chat: Chat) => {
    try {
      // پاک کردن خطا هنگام انتخاب چت جدید
      setStreamError(null);
      const full = await getChat(chat.id);
      setCurrentChat(full);
      if (isMobile) setSidebarOpen(false);
    } catch (error) {
      console.error(error);
    }
  };

  const stopTyping = (chatId: string) => {
    const stream = streams.current[chatId];
    if (!stream) return;
    if (stream.timer) {
      clearInterval(stream.timer);
      stream.timer = null;
    }
    stream.buffer = "";
  };

  const startTyping = (chatId: string, messageId: string) => {
    const stream = streams.current[chatId];
    if (!stream) return;
    if (stream.timer) return;
    stream.timer = setInterval(() => {
      if (!stream.buffer.length) {
        clearInterval(stream.timer);
        stream.timer = null;
        return;
      }
      const char = stream.buffer.substring(0, 1);
      stream.buffer = stream.buffer.substring(1);
      setCurrentChat((prev) => {
        if (!prev || String(prev.id) !== chatId) return prev;
        return {
          ...prev,
          messages: prev.messages.map((m) => {
            if (String(m.id) !== messageId) return m;
            return {
              ...m,
              content: m.content + char,
            };
          }),
        };
      });
    }, 15);
  };

  // تابع اسکرول به پایین (فقط با کلیک دکمه)
  const handleScrollToBottom = useCallback(() => {
    if (lastMessageRef.current) {
      lastMessageRef.current.scrollIntoView({
        behavior: "smooth",
        block: "end",
      });
    }
  }, []);

  const handleSendMessage = async (e: FormEvent) => {
    e.preventDefault();
    if (!input.trim() && !imageFile) return;
    if (loading) return;

    // پاک کردن خطاهای قبلی
    setStreamError(null);

    const text = input.trim();
    const file = imageFile;
    setInput("");
    setLoading(true);
    cancelledRef.current = false;

    const controller = new AbortController();
    abortControllerRef.current = controller;

    const aiId = `ai-${Date.now()}`;
    const userId = `user-${Date.now()}`;
    const chatId = String(currentChat?.id || 0);

    const userMessage: any = {
      id: userId,
      chat_id: Number(chatId),
      role: "user",
      content: text,
      created_at: new Date().toISOString(),
    };

    const aiMessage: any = {
      id: aiId,
      chat_id: Number(chatId),
      role: "assistant",
      content: "",
      created_at: new Date().toISOString(),
    };

    setCurrentChat((prev) => {
      const base = prev || {
        id: 0,
        title: "چت جدید",
        created_at: new Date().toISOString(),
        messages: [],
      };
      return {
        ...base,
        messages: [...base.messages, userMessage, aiMessage],
      };
    });

    streams.current[chatId] = {
      buffer: "",
      timer: null,
      active: true,
    };

    try {
      await sendMessageStream(
        text,
        currentChat?.id,
        file,
        (chunk) => {
          const stream = streams.current[chatId];
          if (!stream || !stream.active) return;
          stream.buffer += chunk.normalize("NFC");
          startTyping(chatId, aiId);
        },
        controller.signal,
      );

      while (streams.current[chatId]?.buffer.length) {
        await new Promise((resolve) => setTimeout(resolve, 50));
      }
      await loadChats();
    } catch (error: any) {
      // اگر خطا مربوط به لغو شدن توسط کاربر باشد، پیام خاصی نشان نمی‌دهیم
      if (error.name === "AbortError") {
        cancelledRef.current = true;
        setCurrentChat((prev) => {
          if (!prev) return prev;
          return {
            ...prev,
            messages: prev.messages.map((msg) => {
              if (msg.id === aiId) {
                return {
                  ...msg,
                  content: "⏹ پاسخ لغو شد",
                };
              }
              return msg;
            }),
          };
        });
        return; // از ادامه کد خارج می‌شویم
      }

      // نمایش خطای زیبا به کاربر
      const errorMessage =
        error?.message ||
        error?.error ||
        "خطا در برقراری ارتباط با سرور. لطفاً دوباره تلاش کنید.";
      setStreamError(errorMessage);

      // حذف پیام دستیار ناقص
      setCurrentChat((prev) => {
        if (!prev) return prev;
        return {
          ...prev,
          messages: prev.messages.filter((msg) => msg.id !== aiId),
        };
      });
    } finally {
      stopTyping(chatId);
      if (streams.current[chatId]) {
        streams.current[chatId].buffer = "";
      }
      delete streams.current[chatId];
      abortControllerRef.current = null;
      setLoading(false);
      setImageFile(null);
      inputRef.current?.focus();
    }
  };

  const handleCancelStream = () => {
    cancelledRef.current = true;
    abortControllerRef.current?.abort();
    const chatId = String(currentChat?.id || 0);
    stopTyping(chatId);
    if (streams.current[chatId]) {
      streams.current[chatId].active = false;
    }
  };

  const handleDeleteChat = async (chatId: number) => {
    if (!confirm("حذف شود؟")) return;
    await deleteChat(chatId);
    setChats((prev) => prev.filter((c) => c.id !== chatId));
    if (currentChat?.id === chatId) {
      setCurrentChat(null);
    }
  };

  const toggleSidebar = () => setSidebarOpen((prev) => !prev);

  return (
    <div className={styles.container}>
      <header className={styles.header}>
        <div className={styles.headerLeft}>
          {isMobile && (
            <button
              className={styles.hamburger}
              onClick={toggleSidebar}
              aria-label="Toggle sidebar"
            >
              <span className={styles.hamburgerLine} />
              <span className={styles.hamburgerLine} />
              <span className={styles.hamburgerLine} />
            </button>
          )}
          <span className={styles.logo}>✨ اکانتسرا</span>
        </div>

        <div className={styles.headerRight}>
          <TokenUsageBadge />
          <UserMenu />
        </div>
      </header>

      {userStatus && (
        <SubscriptionWarning
          hasSubscription={userStatus.has_subscription}
          remainingMessages={userStatus.remaining_messages}
          dailyLimit={userStatus.daily_message_limit}
        />
      )}

      <div className={styles.mainArea}>
        <div
          className={`${styles.sidebarWrapper} ${
            isMobile ? styles.sidebarMobile : ""
          } ${sidebarOpen ? styles.sidebarOpen : ""}`}
        >
          <Sidebar
            chats={chats}
            currentChatId={currentChat?.id}
            onSelectChat={handleSelectChat}
            onNewChat={() => {
              setCurrentChat(null);
              setInput("");
              setImageFile(null);
              setStreamError(null); // پاک کردن خطا در چت جدید
              if (isMobile) setSidebarOpen(false);
            }}
            onDeleteChat={handleDeleteChat}
            isLoading={isLoadingChats}
          />
        </div>

        {isMobile && sidebarOpen && (
          <div className={styles.backdrop} onClick={() => setSidebarOpen(false)} />
        )}

        <main className={styles.main}>
          <div className={styles.messagesContainer}>
            {/* نمایش بنر خطا در بالای لیست پیام‌ها */}
            {streamError && (
              <div className={styles.errorBanner}>
                <span className={styles.errorIcon}>⚠️</span>
                <div className={styles.errorContent}>
                  <span className={styles.errorTitle}>خطا در ارسال پیام</span>
                  <span className={styles.errorMessage}>{streamError}</span>
                  <span className={styles.errorHint}>به زودی برطرف خواهد شد</span>
                </div>
                <button
                  className={styles.errorDismiss}
                  onClick={() => setStreamError(null)}
                  aria-label="بستن"
                >
                  ✕
                </button>
              </div>
            )}

            {!currentChat || currentChat.messages.length === 0 ? (
              <div className={styles.emptyState}>
                <span>💬</span>
                <h2>چت جدید</h2>
              </div>
            ) : (
              currentChat.messages.map((msg, index) => {
                const isLast = index === currentChat.messages.length - 1;
                return (
                  <div
                    key={`${msg.role}-${msg.id}`}
                    ref={isLast ? lastMessageRef : null}
                  >
                    <ChatMessage message={msg} />
                  </div>
                );
              })
            )}
          </div>

          <ChatInput
            value={input}
            onChange={setInput}
            onSubmit={handleSendMessage}
            onImageUpload={setImageFile}
            onImageRemove={() => setImageFile(null)}
            imageFile={imageFile}
            disabled={loading}
            isStreaming={loading}
            onCancel={handleCancelStream}
            inputRef={inputRef}
            onScrollToBottom={handleScrollToBottom}
          />
        </main>
      </div>
    </div>
  );
}