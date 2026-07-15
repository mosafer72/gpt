"use client";

import { useState, useEffect, useRef, FormEvent, useCallback } from "react";

import { useRouter } from "next/navigation";

import {
  sendMessageStream,
  getChats,
  deleteChat,
  getChat,
  getUserStatus,
} from "@/lib/api";

import { Chat, AIModel } from "@/types";

import Sidebar from "@/components/Sidebar/Sidebar";
import ChatMessage from "@/components/Chat/ChatMessage";
import ChatInput from "@/components/Chat/ChatInput";
import UserMenu from "@/components/Common/UserMenu";
import SubscriptionWarning from "@/components/Chat/SubscriptionWarning";
import TokenUsageBadge from "../Common/TokenUsageBadge";

import styles from "./ChatInterface.module.css";

interface ChatInterfaceProps {
  selectedModel?: AIModel | null;

  onNewChat: () => void;
}

function useMediaQuery(query: string) {
  const [matches, setMatches] = useState(false);

  useEffect(() => {
    const media = window.matchMedia(query);

    setMatches(media.matches);

    const listener = (e: MediaQueryListEvent) => setMatches(e.matches);

    media.addEventListener("change", listener);

    return () => {
      media.removeEventListener("change", listener);
    };
  }, [query]);

  return matches;
}

export default function ChatInterface({
  selectedModel,

  onNewChat,
}: ChatInterfaceProps) {
  const router = useRouter();

  const [chats, setChats] = useState<Chat[]>([]);

  const [isLoadingChats, setIsLoadingChats] = useState(true);

  const [userStatus, setUserStatus] = useState<any>(null);

  const inputRef = useRef<HTMLTextAreaElement | null>(null);

  const abortControllerRef = useRef<AbortController | null>(null);

  const lastMessageRef = useRef<HTMLDivElement | null>(null);
  const messagesContainerRef = useRef<HTMLDivElement | null>(null);
  const [backendError, setBackendError] = useState<string | null>(null);

  const streams = useRef<
    Record<
      string,
      {
        buffer: string;
        timer: NodeJS.Timeout | null;
        active: boolean;
        lastBufferSize: number;
      }
    >
  >({});

  const [currentChat, setCurrentChat] = useState<Chat | null>(null);
  const [loading, setLoading] = useState(false);
  const [input, setInput] = useState("");
  const [imageFile, setImageFile] = useState<File | null>(null);
  const [streamError, setStreamError] = useState<string | null>(null);

  useEffect(() => {
    if (!selectedModel) return;

    // ساخت چت جدید موقت با مدل انتخابی
    setCurrentChat({
      id: 0,
      title: "چت جدید",
      messages: [],
      created_at: new Date().toISOString(),
      model_id: selectedModel.id,
      model_name: selectedModel.name,
    } as Chat);

    setInput("");
    setImageFile(null);
    setStreamError(null);
    setTimeout(() => {
      scrollToBottomInstant();
    }, 100);
  }, [selectedModel]);

  function formatBackendError(error: any): string {
    if (!error) {
      return "خطای ناشناخته‌ای رخ داد";
    }

    const message =
      error?.message ||
      error?.response?.message ||
      error?.response?.data?.message ||
      "";

    if (message.includes("تعداد پیام روزانه")) {
      return "🚦 سقف پیام روزانه شما تمام شده است.\n\nلطفاً پلن خود را ارتقا دهید یا فردا دوباره امتحان کنید.";
    }

    if (message.includes("اعتبار توکن")) {
      return "💳 اعتبار توکن شما کافی نیست.\n\nلطفاً توکن خریداری کنید یا پلن خود را ارتقا دهید.";
    }

    if (message.includes("model") || message.includes("مدل")) {
      return "🤖 مدل هوش مصنوعی در دسترس نیست.\n\nلطفاً یک مدل دیگر انتخاب کنید.";
    }

    if (message.includes("payload") || message.includes("Decrypt")) {
      return "🔐 خطای امنیتی در ارتباط با سرور رخ داده است.";
    }

    if (message.includes("500") || message.includes("server")) {
      return "🔥 خطای داخلی سرور رخ داده است.\n\nلطفاً دوباره تلاش کنید.";
    }

    return "⚠️ " + message;
  }

  const isMobile = useMediaQuery("(max-width:767px)");

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

  async function fetchUserStatus() {
    try {
      const status = await getUserStatus();

      setUserStatus(status);
    } catch (e) {
      console.error(e);
    }
  }

  async function loadChats() {
    try {
      const data = await getChats();

      setChats(data);

      if (data.length && !currentChat) {
        setCurrentChat(data[0]);
      }
    } catch (e) {
      console.error(e);
    } finally {
      setIsLoadingChats(false);
    }
  }

  async function handleSelectChat(chat: Chat) {
    try {
      setStreamError(null);

      const full = await getChat(chat.id);

      setCurrentChat(full);

      if (isMobile) setSidebarOpen(false);
    } catch (e) {
      console.error(e);
    }
  }
  function startTyping(chatId: string, messageId: string) {
    const stream = streams.current[chatId];

    if (!stream) return;

    if (stream.timer) return;

    const render = () => {
      const currentStream = streams.current[chatId];

      if (!currentStream) return;

      const bufferLength = currentStream.buffer.length;

      if (bufferLength === 0) {
        currentStream.timer = null;

        return;
      }

      let chunkSize = 1;

      let delay = 40;

      if (bufferLength > 500) {
        chunkSize = 25;
        delay = 5;
      } else if (bufferLength > 200) {
        chunkSize = 15;
        delay = 10;
      } else if (bufferLength > 50) {
        chunkSize = 5;
        delay = 20;
      }

      const chars = currentStream.buffer.substring(0, chunkSize);

      currentStream.buffer = currentStream.buffer.substring(chunkSize);

      setCurrentChat((prev) => {
        if (!prev) return prev;

        return {
          ...prev,

          messages: prev.messages.map((m) => {
            if (String(m.id) !== messageId) {
              return m;
            }

            return {
              ...m,

              content: m.content + chars,
            };
          }),
        };
      });

      // همیشه آخرین پیام را نشان بده
      requestAnimationFrame(() => {
        scrollToBottomInstant();
      });

      currentStream.timer = setTimeout(render, delay);
    };

    render();
  }

  function stopTyping(chatId: string) {
    const stream = streams.current[chatId];

    if (!stream) return;

    if (stream.timer) {
      clearTimeout(stream.timer);
    }

    delete streams.current[chatId];
  }

  const handleScrollToBottom = useCallback(() => {
    const container = messagesContainerRef.current;

    if (!container) return;

    container.scrollTo({
      top: container.scrollHeight,
      behavior: "smooth",
    });
  }, []);

  const scrollToBottomInstant = useCallback(() => {
    const container = messagesContainerRef.current;

    if (!container) return;

    container.scrollTop = container.scrollHeight;
  }, []);

  async function handleSendMessage(e: FormEvent) {
    e.preventDefault();

    if (!input.trim() && !imageFile) return;

    if (loading) return;

    if (!selectedModel) {
      alert("ابتدا مدل را انتخاب کنید");

      return;
    }

    setStreamError(null);

    const text = input.trim();

    setInput("");

    setLoading(true);

    const controller = new AbortController();

    abortControllerRef.current = controller;

    const aiId = `ai-${Date.now()}`;

    const userId = `user-${Date.now()}`;

    const chatId = String(currentChat?.id || 0);

    const userMessage: any = {
      id: userId,

      role: "user",

      content: text,

      created_at: new Date().toISOString(),
    };

    const aiMessage: any = {
      id: aiId,

      role: "assistant",

      content: "",

      created_at: new Date().toISOString(),
    };

    setCurrentChat((prev) => {
      const base = prev || {
        id: 0,

        title: "چت جدید",

        messages: [],

        created_at: new Date().toISOString(),
      };

      return {
        ...base,

        messages: [...base.messages, userMessage, aiMessage],
      };
    });

    setTimeout(() => {
      scrollToBottomInstant();
    }, 50);

    streams.current[chatId] = {
      buffer: "",
      timer: null,
      active: true,
      lastBufferSize: 0,
    };

    try {
      await sendMessageStream(
        text,

        currentChat?.id,

        imageFile,

        selectedModel.id,

        (chunk) => {
          const stream = streams.current[chatId];

          if (!stream) return;

          stream.buffer += chunk.normalize("NFC");

          if (!stream.timer) {
            startTyping(chatId, aiId);
          }
        },

        controller.signal,
      );

      await loadChats();
    } catch (error: any) {
      const errorMessage = formatBackendError(error);

      setStreamError(errorMessage);
    } finally {
      // صبر کن تا تمام buffer رندر شود
      stopTyping(chatId);

      abortControllerRef.current = null;

      setLoading(false);

      setImageFile(null);

      inputRef.current?.focus();

      scrollToBottomInstant();

      stopTyping(chatId);
      abortControllerRef.current = null;
      setLoading(false);
      setImageFile(null);
      inputRef.current?.focus();
    }
  }

  function handleCancelStream() {
    abortControllerRef.current?.abort();

    stopTyping(String(currentChat?.id || 0));
  }

  async function handleDeleteChat(id: number) {
    if (!confirm("حذف شود؟")) return;

    await deleteChat(id);

    setChats((prev) => prev.filter((c) => c.id !== id));

    if (currentChat?.id === id) setCurrentChat(null);
  }

  return (
    <div className={styles.container}>
      <header className={styles.header}>
        <div className={styles.headerLeft}>
          {isMobile && (
            <button
              className={styles.hamburger}
              onClick={() => setSidebarOpen(!sidebarOpen)}
            >
              ☰
            </button>
          )}

          <span className={styles.logo}>✨ اکانتسرا</span>

          {selectedModel && (
            <div className={styles.modelBadge}>🤖 {selectedModel.name}</div>
          )}
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
          className={`${styles.sidebarWrapper}
${sidebarOpen ? styles.sidebarOpen : ""}`}
        >
          <Sidebar
            chats={chats}
            currentChatId={currentChat?.id}
            onSelectChat={handleSelectChat}
            onNewChat={onNewChat}
            onDeleteChat={handleDeleteChat}
            isLoading={isLoadingChats}
          />
        </div>

        <main className={styles.main}>
          <div ref={messagesContainerRef} className={styles.messagesContainer}>
            {streamError && (
              <div className={styles.aiErrorMessage}>
                <div className={styles.errorIcon}>⚠️</div>

                <div className={styles.errorContent}>
                  <strong>مشکلی پیش آمد</strong>

                  <p>{streamError}</p>

                  <button onClick={() => setStreamError(null)}>بستن</button>
                </div>
              </div>
            )}

            {!currentChat || currentChat.messages.length === 0 ? (
              <div className={styles.emptyState}>
                💬
                <h2>چت جدید</h2>
              </div>
            ) : (
              currentChat.messages.map((msg, index) => (
                <div
                  key={`${msg.id}-${index}`}
                  ref={
                    index === currentChat.messages.length - 1
                      ? lastMessageRef
                      : null
                  }
                >
                  <ChatMessage message={msg} />
                </div>
              ))
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
