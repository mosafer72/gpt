"use client";

import { useState, useEffect, useRef, FormEvent } from "react";

import { sendMessageStream } from "@/lib/api";
import { useRouter } from "next/navigation";

import { Chat } from "@/types";

import { getChats, deleteChat, getChat, getUserStatus } from "@/lib/api";

import Sidebar from "@/components/Sidebar/Sidebar";
import ChatMessage from "@/components/Chat/ChatMessage";
import ChatInput from "@/components/Chat/ChatInput";
import UserMenu from "@/components/Common/UserMenu";

import styles from "./ChatInterface.module.css";

import SubscriptionWarning from "@/components/Chat/SubscriptionWarning";
import TokenUsageBadge from "../Common/TokenUsageBadge";

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

  const messagesEndRef = useRef<HTMLDivElement>(null);

  const abortControllerRef = useRef<AbortController | null>(null);

  const [userStatus, setUserStatus] = useState<{
    has_subscription: boolean;
    daily_message_limit: number;
    today_messages_count: number;
    remaining_messages: number;
  } | null>(null);

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

  useEffect(() => {
    const container = document.querySelector(`.${styles.messagesContainer}`);

    if (!container) return;

    const distance =
      container.scrollHeight - container.scrollTop - container.clientHeight;

    if (distance < 150) {
      messagesEndRef.current?.scrollIntoView({
        behavior: "smooth",
      });
    }
  }, [currentChat?.messages]);

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
      const full = await getChat(chat.id);

      setCurrentChat(full);
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

  const handleSendMessage = async (e: FormEvent) => {
    e.preventDefault();

    if (!input.trim() && !imageFile) return;

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
      } else {
        alert(error.message || "خطا در ارسال");
      }
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

  return (
    <div className={styles.container}>
      <header className={styles.header}>
        <div className={styles.headerLeft}>
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
        <Sidebar
          chats={chats}
          currentChatId={currentChat?.id}
          onSelectChat={handleSelectChat}
          onNewChat={() => {
            setCurrentChat(null);

            setInput("");

            setImageFile(null);
          }}
          onDeleteChat={handleDeleteChat}
          isLoading={isLoadingChats}
        />

        <main className={styles.main}>
          <div className={styles.messagesContainer}>
            {!currentChat || currentChat.messages.length === 0 ? (
              <div className={styles.emptyState}>
                <span>💬</span>

                <h2>چت جدید</h2>
              </div>
            ) : (
              currentChat.messages.map((msg) => (
                <ChatMessage key={`${msg.role}-${msg.id}`} message={msg} />
              ))
            )}

            <div ref={messagesEndRef} />
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
          />
        </main>
      </div>
    </div>
  );
}
