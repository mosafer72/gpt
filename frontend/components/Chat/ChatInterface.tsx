"use client";

import { useState, useEffect, useRef, FormEvent } from "react";
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
import TokenUsageBadge from "../Common/TokenUsageBadge";
import styles from "./ChatInterface.module.css";
import { uploadFile } from "@/lib/api";
interface ChatInterfaceProps {
  selectedModel?: AIModel | null;
  onNewChat: () => void;
  newChatTrigger?: number;
  initialChat?: Chat | null;
}

export default function ChatInterface({
  selectedModel,
  onNewChat,
  newChatTrigger,
  initialChat,
}: ChatInterfaceProps) {
  const router = useRouter();
  const [chats, setChats] = useState<Chat[]>([]);
  const [currentChat, setCurrentChat] = useState<Chat | null>(
    initialChat ?? null,
  );
  const [input, setInput] = useState("");
  const [loading, setLoading] = useState(false);
  const [imageFile, setImageFile] = useState<File | null>(null);
  const [userStatus, setUserStatus] = useState<any>(null);
  const [showEmptyState, setShowEmptyState] = useState(true);
  const [attachedFile, setAttachedFile] = useState<File | null>(null);

  const messagesContainerRef = useRef<HTMLDivElement | null>(null);
  const inputRef = useRef<HTMLTextAreaElement | null>(null);
  const abortControllerRef = useRef<AbortController | null>(null);
  const lastMessageRef = useRef<HTMLDivElement | null>(null);

  useEffect(() => {
    if (initialChat) {
      setCurrentChat(initialChat);
      setShowEmptyState(initialChat.messages.length === 0);
    }
  }, [initialChat]);

  useEffect(() => {
    if (!newChatTrigger) return;

    setShowEmptyState(true);
    setCurrentChat({
      id: 0,
      title: "چت جدید",
      messages: [],
      created_at: new Date().toISOString(),
    } as Chat);
    setInput("");
    setImageFile(null);
    router.push("/chat");
  }, [newChatTrigger]);

  useEffect(() => {
    async function init() {
      const token = localStorage.getItem("auth_token");
      if (!token) {
        router.push("/login");
        return;
      }
      await loadChats();
      await loadStatus();
    }
    init();
  }, []);

  async function loadStatus() {
    try {
      const status = await getUserStatus();
      setUserStatus(status);
    } catch (e) {}
  }

  async function loadChats() {
    try {
      const data = await getChats();
      setChats(data);
    } catch (e) {
      console.error(e);
    }
  }

  async function handleSelectChat(chat: Chat) {
    if (!chat.id) return;
    if (currentChat?.id === chat.id) return;

    try {
      const full = await getChat(chat.id);

      const sorted = {
        ...full,
        messages: [...full.messages].sort(
          (a, b) =>
            new Date(a.created_at).getTime() - new Date(b.created_at).getTime(),
        ),
      };

      setCurrentChat(sorted);
      setShowEmptyState(sorted.messages.length === 0);
      router.push(`/chat/${chat.id}`);
    } catch (error) {
      console.error("load chat failed:", error);
    }
  }

  function scrollToBottom() {
    const box = messagesContainerRef.current;
    if (!box) return;
    box.scrollTop = box.scrollHeight;
  }

  useEffect(() => {
    if (lastMessageRef.current) {
      lastMessageRef.current.scrollIntoView({
        behavior: "smooth",
        block: "end",
      });
    }
  }, [currentChat?.messages]);

  async function handleSendMessage(e: FormEvent) {
    
    e.preventDefault();
    let uploadedFile = null;

    if (attachedFile) {
      uploadedFile = await uploadFile(attachedFile);
    }
    if (!input.trim() && !imageFile) return;
    if (loading) return;
    if (!selectedModel) {
      alert("ابتدا مدل را انتخاب کنید");
      return;
    }

    const text = input.trim();
    setInput("");
    setLoading(true);

    const controller = new AbortController();
    abortControllerRef.current = controller;

    const isNewChat = !currentChat || currentChat.id === 0;

    if (isNewChat) {
      setShowEmptyState(false);
    }

    const userMsgId = `user-${Date.now()}`;
    const aiMsgId = `ai-${Date.now()}`;

    const chatId = currentChat?.id || 0;

    const imagePreview = imageFile ? URL.createObjectURL(imageFile) : null;

    const userMessage: any = {
      id: userMsgId,
      role: "user",
      content: text,
      image: imagePreview,
      created_at: new Date().toISOString(),
      chat_id: chatId,
    };

    const aiMessage: any = {
      id: aiMsgId,
      role: "assistant",
      content: "",
      created_at: new Date().toISOString(),
      chat_id: chatId,
    };

    setCurrentChat((prev) => {
      const base =
        prev ??
        ({
          id: 0,
          title: "چت جدید",
          messages: [],
          created_at: new Date().toISOString(),
        } as Chat);
      return {
        ...base,
        messages: [...base.messages, userMessage, aiMessage],
      };
    });
    setTimeout(scrollToBottom, 100);

    try {
      await sendMessageStream(
        text,
        isNewChat ? undefined : currentChat?.id,
        imageFile,
        selectedModel.id,
        (chunk) => {
          let data: any = chunk; // صریحاً any بدهیم

          try {
            data = JSON.parse(chunk);
          } catch {
            // متن عادی LLM است
          }

          setCurrentChat((prev) => {
            if (!prev) return prev;

            return {
              ...prev,

              messages: prev.messages.map((msg) => {
                if (msg.id !== aiMsgId) {
                  return msg;
                }

                // =========================
                // Image Response
                // =========================
                if (
                  typeof data === "object" &&
                  data !== null &&
                  data.type === "image"
                ) {
                  // اصلاح: استفاده از as any برای تطابق با تایپ Message
                  return {
                    ...msg,
                    content: "عکس ایجاد شد",
                    image_url: data.image_url,
                    metadata: {
                      type: "image",
                      image_url: data.image_url,
                    },
                  } as any;
                }

                // =========================
                // Text Stream
                // =========================
                if (typeof data === "object" && data !== null && data.content) {
                  return {
                    ...msg,
                    content: msg.content + data.content,
                  };
                }

                return {
                  ...msg,
                  content: msg.content + chunk,
                };
              }),
            };
          });

          setTimeout(scrollToBottom, 20);
        },
        controller.signal,
        uploadedFile
      );

      const updatedChats = await getChats();
      setChats(updatedChats);

      if (isNewChat) {
        const newChat = updatedChats[0];
        if (newChat) {
          setCurrentChat((prev) => ({
            ...prev!,
            id: newChat.id,
            title: newChat.title || prev?.title || "چت جدید",
          }));
        }
      }
    } catch (error: any) {
      console.error("Error in sendMessageStream:", error);
      setCurrentChat((prev) => {
        if (!prev) return prev;
        return {
          ...prev,
          messages: [
            ...prev.messages,
            {
              id: `error-${Date.now()}`,
              role: "assistant",
              content: `⚠️ خطا: ${error.message || "مشکل در ارتباط با سرور"}`,
              created_at: new Date().toISOString(),
              chat_id: prev.id || 0,
            },
          ],
        };
      });
    } finally {
      setAttachedFile(null);
      setLoading(false);
      setImageFile(null);
      abortControllerRef.current = null;
      inputRef.current?.focus();
      scrollToBottom();
    }
  }

  function cancelStream() {
    abortControllerRef.current?.abort();
    setLoading(false);
  }

  async function handleDeleteChat(id: number) {
    if (!confirm("حذف شود؟")) return;
    await deleteChat(id);
    const updated = await getChats();
    setChats(updated);
    if (currentChat?.id === id) {
      setCurrentChat({
        id: 0,
        title: "چت جدید",
        messages: [],
        created_at: new Date().toISOString(),
      } as Chat);
      setShowEmptyState(true);
      router.push("/chat");
    }
  }

  return (
    <div className={styles.container}>
      <header className={styles.header}>
        <div>
          <span className={styles.logo}>✨ اکانتسرا</span>
          {selectedModel && (
            <span className={styles.modelBadge}>🤖 {selectedModel.name}</span>
          )}
        </div>
        <div>
          <UserMenu />
          <TokenUsageBadge />
        </div>
      </header>

      <div className={styles.mainArea}>
        <div className={styles.sidebarWrapper}>
          <Sidebar
            chats={chats}
            currentChatId={currentChat?.id}
            onSelectChat={handleSelectChat}
            onNewChat={onNewChat}
            onDeleteChat={handleDeleteChat}
            isLoading={false}
          />
        </div>

        <main className={styles.main}>
          <div ref={messagesContainerRef} className={styles.messagesContainer}>
            {showEmptyState &&
            (!currentChat || currentChat.messages.length === 0) ? (
              <div className={styles.emptyState}>
                <div className={styles.emptyGlow} />
                <div className={styles.emptyIcon}>💬</div>
                <h1>شروع یک گفتگوی جدید</h1>
                <p>هر سوالی دارید بنویسید</p>
                {selectedModel && (
                  <div className={styles.modelHint}>
                    🤖 {selectedModel.name}
                  </div>
                )}
                <div className={styles.suggestions}>
                  <div>✨ خلاصه سازی متن</div>
                  <div>🧠 برنامه نویسی</div>
                  <div>📊 تحلیل داده</div>
                </div>
              </div>
            ) : (
              currentChat?.messages.map((msg, index) => (
                <div
                  key={`${msg.id}-${index}`}
                  ref={
                    index === (currentChat?.messages.length ?? 0) - 1
                      ? lastMessageRef
                      : null
                  }
                >
                  <ChatMessage message={msg} />
                </div>
              ))
            )}
            {loading && (
              <div className={styles.loadingIndicator}>
                <div className={styles.dots}>
                  <span></span>
                  <span></span>
                  <span></span>
                </div>
                <span>در حال دریافت پاسخ...</span>
              </div>
            )}
          </div>

          <ChatInput
            value={input}
            onChange={setInput}
            onSubmit={handleSendMessage}
            disabled={loading}
            isStreaming={loading}
            onCancel={cancelStream}
            imageFile={imageFile}
            onImageUpload={setImageFile}
            onImageRemove={() => setImageFile(null)}
            inputRef={inputRef}
            onScrollToBottom={scrollToBottom}
            attachedFile={attachedFile}
            onFileUpload={setAttachedFile}
            onFileRemove={() => setAttachedFile(null)}
          />
        </main>
      </div>
    </div>
  );
}
