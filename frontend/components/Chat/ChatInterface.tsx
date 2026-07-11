"use client";

import { useState, useEffect, useRef, FormEvent } from "react";
import { sendMessageStream } from "@/lib/api";
import { useRouter } from "next/navigation";
import { Chat } from "@/types";
import {
  getChats,
  sendMessage,
  deleteChat,
  getChat,
  getUserStatus,
} from "@/lib/api";
import Sidebar from "@/components/Sidebar/Sidebar";
import ChatMessage from "@/components/Chat/ChatMessage";
import ChatInput from "@/components/Chat/ChatInput";
import UserMenu from "@/components/Common/UserMenu";
import styles from "./ChatInterface.module.css";
import SubscriptionWarning from "@/components/Chat/SubscriptionWarning";
export default function ChatInterface() {
  const router = useRouter();
  const [chats, setChats] = useState<Chat[]>([]);
  const [currentChat, setCurrentChat] = useState<Chat | null>(null);
  const [input, setInput] = useState("");
  const [loading, setLoading] = useState(false);
  const [isLoadingChats, setIsLoadingChats] = useState(true);
  const [imageFile, setImageFile] = useState<File | null>(null);
  const messagesEndRef = useRef<HTMLDivElement>(null);
  const inputRef = useRef<HTMLTextAreaElement>(null);
  const [userStatus, setUserStatus] = useState<{
    has_subscription: boolean;
    daily_message_limit: number;
    today_messages_count: number;
    remaining_messages: number;
  } | null>(null);

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
      console.error("Error fetching user status:", error);
    }
  };

  useEffect(() => {
    messagesEndRef.current?.scrollIntoView({ behavior: "smooth" });
  }, [currentChat?.messages]);

  const loadChats = async () => {
    try {
      const data = await getChats();
      setChats(data);
      if (data.length > 0 && !currentChat) {
        setCurrentChat(data[0]);
      }
    } catch (error) {
      console.error("Error loading chats:", error);
    } finally {
      setIsLoadingChats(false);
    }
  };

  const handleSelectChat = async (chat: Chat) => {
    try {
      const fullChat = await getChat(chat.id);
      setCurrentChat(fullChat);
    } catch (error) {
      console.error("Error loading chat:", error);
    }
  };

  const handleSendMessage = async (e: FormEvent) => {
    e.preventDefault();
    if ((!input.trim() && !imageFile) || loading) return;

    if (userStatus) {
      const { has_subscription, remaining_messages } = userStatus;
      if (!has_subscription && remaining_messages <= 0) {
        alert("⛔ پیام‌های رایگان شما تمام شده است. لطفاً اشتراک تهیه کنید.");
        return;
      }
    }

    const messageText = input.trim();
    setInput("");
    setLoading(true);
    try {
      // const result = await sendMessage(messageText, currentChat?.id, imageFile);
      // setCurrentChat(result.chat);

      const previewUrl = imageFile ? URL.createObjectURL(imageFile) : null;

      const tempUserMessage = {
        id: `temp-user-${Date.now()}`,
        chat_id: currentChat?.id || 0,
        role: "user" as const,
        content: messageText,
        image_url: previewUrl || undefined,
        created_at: new Date().toISOString(),
      };

      const tempAssistantMessage = {
        id: `temp-ai-${Date.now()}`,
        chat_id: currentChat?.id || 0,
        role: "assistant" as const,
        content: "",
        created_at: new Date().toISOString(),
      };

      setCurrentChat((prev) => ({
        id: prev?.id || 0,

        title: prev?.title || "چت جدید",

        created_at: prev?.created_at || new Date().toISOString(),

        messages: [
          ...(prev?.messages || []),

          tempUserMessage,

          tempAssistantMessage,
        ],
      }));

      let targetText = "";

      let displayedText = "";

      // تایمر افکت تایپ

      const typingInterval = setInterval(() => {
        if (displayedText.length < targetText.length) {
          displayedText += targetText[displayedText.length];

          setCurrentChat((prev) => {
            if (!prev) return prev;

            return {
              ...prev,

              messages: prev.messages.map((m) =>
                m.id === tempAssistantMessage.id
                  ? { ...m, content: displayedText }
                  : m,
              ),
            };
          });
        }
      }, 15);

      try {
        const result = await sendMessage(
          messageText,
          currentChat?.id,
          imageFile,
        );
        setCurrentChat((prev) => {
          if (!prev) return result.chat;
          const realUserMessage = result.chat.messages.find(
            (m) => m.role === "user",
          );
          const realAssistantMessage = result.chat.messages.find(
            (m) => m.role === "assistant",
          );
          return {
            ...prev,
            id: result.chat.id,
            title: result.chat.title,
            messages: prev.messages.map((m) => {
              if (m.id === tempUserMessage.id) {
                return {
                  ...realUserMessage!,
                  image_url: previewUrl || undefined,
                };
              }
              if (m.id === tempAssistantMessage.id) {
                return realAssistantMessage!;
              }
              return m;
            }),
          };
        });

      } finally {
        // صبر کن تا همه حروف نمایش داده شوند

        const waitUntilFinished = setInterval(() => {
          if (displayedText.length >= targetText.length) {
            clearInterval(waitUntilFinished);

            clearInterval(typingInterval);
          }
        }, 20);
      }

      setChats((prev) => {
        const chatId = currentChat?.id || 0;

        const exists = prev.some((c) => c.id === chatId);

        if (exists) return prev;

        return [
          {
            id: chatId,

            title: messageText.slice(0, 50),

            created_at: new Date().toISOString(),

            messages: [],
          },

          ...prev,
        ];
      });

      setImageFile(null);
    } catch (error: any) {
      alert(error.message || "خطا در ارسال پیام");
      setInput(messageText);
    } finally {
      setLoading(false);
      inputRef.current?.focus();
    }
  };

  const handleDeleteChat = async (chatId: number) => {
    if (!confirm("آیا از حذف این چت اطمینان دارید؟")) return;
    try {
      await deleteChat(chatId);
      setChats((prev) => prev.filter((c) => c.id !== chatId));
      if (currentChat?.id === chatId) {
        const nextChat = chats.find((c) => c.id !== chatId);
        setCurrentChat(nextChat || null);
      }
    } catch (error) {
      console.error("Error deleting chat:", error);
    }
  };

  return (
    <div className={styles.container}>
      {/* هدر جدید با منوی کاربر */}
      <header className={styles.header}>
        <div className={styles.headerLeft}>
          <span className={styles.logo}>✨ اکانتسرا</span>
        </div>
        <div className={styles.headerRight}>
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
      {/* بخش اصلی (سایدبار + چت) */}
      <div className={styles.mainArea}>
        <Sidebar
          chats={chats}
          currentChatId={currentChat?.id}
          onSelectChat={handleSelectChat}
          onNewChat={() => {
            setCurrentChat(null);
            setInput("");
            setImageFile(null);
            inputRef.current?.focus();
          }}
          onDeleteChat={handleDeleteChat}
          isLoading={isLoadingChats}
        />
        <main className={styles.main}>
          <div className={styles.messagesContainer}>
            {!currentChat || currentChat.messages.length === 0 ? (
              <div className={styles.emptyState}>
                <span className={styles.emptyIcon}>💬</span>
                <h2>چت جدید</h2>
                <p>سوال خود را بپرسید یا یک عکس آپلود کنید.</p>
              </div>
            ) : (
              currentChat.messages.map((msg) => (
                <ChatMessage key={msg.id} message={msg} />
              ))
            )}
            {loading && (
              <div className={styles.typingIndicator}>
                <span></span>
                <span></span>
                <span></span>
              </div>
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
            disabled={false}
            inputRef={inputRef}
          />
        </main>
      </div>
    </div>
  );
}
