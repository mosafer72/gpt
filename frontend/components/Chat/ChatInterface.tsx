"use client";

import { useEffect, useRef, useState, FormEvent } from "react";
import { useRouter } from "next/navigation";
import { sendMessageStream, getChats, getChat, deleteChat } from "@/lib/api";
import { Chat, AIModel, Workspace } from "@/types";

import Sidebar from "@/components/Sidebar/Sidebar";
import ChatMessage from "@/components/Chat/ChatMessage";
import ChatInput from "@/components/Chat/ChatInput";
import UserMenu from "@/components/Common/UserMenu";
import TokenUsageBadge from "@/components/Common/TokenUsageBadge";

import styles from "./ChatInterface.module.css";

interface Props {
  workspace: Workspace;
  workspaceId: number;
  models?: AIModel[];
  onNewChat?: () => void;
  initialChat?: Chat | null;
  documents?: any[];
  selectedModel?: AIModel | null;
}

export default function ChatInterface({
  workspace,
  workspaceId,
  models = [],
  initialChat,
  onNewChat,
  selectedModel,
}: Props) {
  const router = useRouter();

  const [chats, setChats] = useState<Chat[]>([]);
  const [currentChat, setCurrentChat] = useState<Chat | null>(
    initialChat ?? null,
  );
  const [activeModel, setActiveModel] = useState<AIModel | null>(null);
  const [input, setInput] = useState("");
  const [loading, setLoading] = useState(false);

  // استیت‌های جدید برای فایل، تصویر و مدیریت استریم
  const [imageFile, setImageFile] = useState<File | null>(null);
  const [attachedFile, setAttachedFile] = useState<File | null>(null);

  const messagesRef = useRef<HTMLDivElement | null>(null);
  const abortRef = useRef<AbortController | null>(null);

  // انتخاب مدل پیش‌فرض
  useEffect(() => {
    if (!models.length) {
      setActiveModel(null);
      return;
    }
    const defaultModel =
      models.find((m) => m.pivot?.is_default === 1) ?? models[0];
    setActiveModel(defaultModel);
  }, [models]);

  // بارگذاری لیست چت‌ها
  useEffect(() => {
    loadChats();
  }, [workspaceId]);

  async function loadChats() {
    try {
      const data = await getChats(workspaceId);
      setChats(data);
    } catch (e) {
      console.error("Error loading chats:", e);
    }
  }

  async function selectChat(chat: Chat) {
    try {
      const data = await getChat(workspaceId, chat.id);
      setCurrentChat(data);
      router.push(`/workspace/${workspaceId}/chat/${chat.id}`);
    } catch (e) {
      console.error("Error selecting chat:", e);
    }
  }

  // مدیریت کلیک روی دکمه «چت جدید»
  const handleNewChat = () => {
    if (abortRef.current) {
      abortRef.current.abort();
      setLoading(false);
    }

    setCurrentChat(null);
    setInput("");
    setImageFile(null);
    setAttachedFile(null);
    router.push(`/workspace/${workspaceId}`);

    if (onNewChat) {
      onNewChat();
    }
  };

  function scrollBottom() {
    if (messagesRef.current) {
      messagesRef.current.scrollTop = messagesRef.current.scrollHeight;
    }
  }

  // تابع لغو استریم
  const cancelStream = () => {
    if (abortRef.current) {
      abortRef.current.abort();
      abortRef.current = null;
      setLoading(false);
    }
  };

  async function send(e: FormEvent) {
    e.preventDefault();

    if ((!input.trim() && !imageFile && !attachedFile) || !activeModel) return;

    setLoading(true);

    const controller = new AbortController();
    abortRef.current = controller;

    const text = input.trim();
    setInput("");
    setImageFile(null);
    setAttachedFile(null);

    const tempId = Date.now().toString();
    const isNewChat = !currentChat || currentChat.id === 0;

    setCurrentChat((prev) => {
      const base: Chat = prev ?? {
        id: 0,
        title: "چت جدید",
        messages: [],
        created_at: new Date().toISOString(),
      };

      return {
        ...base,
        messages: [
          ...base.messages,
          {
            id: tempId,
            chat_id: base.id,
            role: "user",
            content: text,
            created_at: new Date().toISOString(),
          },
          {
            id: `ai-${tempId}`,
            chat_id: base.id,
            role: "assistant",
            content: "",
            created_at: new Date().toISOString(),
          },
        ],
      };
    });

    setTimeout(scrollBottom, 50);

    try {
      await sendMessageStream(
        text,
        workspaceId,
        isNewChat ? undefined : currentChat?.id,
        null,
        activeModel.id,
        (pureTextChunk: string) => {
          setCurrentChat((prev) => {
            if (!prev) return prev;

            return {
              ...prev,
              messages: prev.messages.map((msg) => {
                if (String(msg.id) === `ai-${tempId}`) {
                  return {
                    ...msg,
                    content: msg.content + pureTextChunk,
                  };
                }
                return msg;
              }),
            };
          });

          scrollBottom();
        },
        controller.signal,
      );

      const latestChats = await getChats(workspaceId);
      setChats(latestChats);

      if (latestChats.length > 0) {
        const createdOrCurrent = isNewChat
          ? latestChats[0]
          : latestChats.find((c) => c.id === currentChat?.id);

        if (createdOrCurrent && isNewChat) {
          setCurrentChat(createdOrCurrent);
          router.push(`/workspace/${workspaceId}/chat/${createdOrCurrent.id}`);
        }
      }
    } catch (e: any) {
      if (e.name !== "AbortError") {
        console.error("Error sending message:", e);
      }
    } finally {
      setLoading(false);
      abortRef.current = null;
    }
  }

  return (
    <div className={styles.container}>
      <header className={styles.header}>
        <div className={styles.headerLeft}>
          <span className={styles.logo}>✨ اکانتسرا</span>
          <span className={styles.modelBadge}>
            🤖 {activeModel?.name ?? "بدون مدل"}
          </span>
        </div>

        <div className={styles.workspaceInfo}>
          <h1>{workspace.name}</h1>
          <span>
            {workspace.workspace_type?.icon}{" "}
            {workspace.workspace_type?.display_name}
          </span>
        </div>

        <div className={styles.headerRight}>
          <UserMenu />
          <TokenUsageBadge />
        </div>
      </header>

      <div className={styles.mainArea}>
        <div className={styles.sidebarWrapper}>
          <Sidebar
            chats={chats}
            currentChatId={currentChat?.id}
            onSelectChat={selectChat}
            onNewChat={handleNewChat}
            isLoading={false}
            onDeleteChat={async (id) => {
              await deleteChat(workspaceId, id);
              if (currentChat?.id === id) {
                handleNewChat();
              } else {
                await loadChats();
              }
            }}
          />
        </div>

        <main className={styles.main}>
          <div ref={messagesRef} className={styles.messagesContainer}>
            {currentChat?.messages?.map((msg) => (
              <ChatMessage key={msg.id} message={msg} />
            ))}
          </div>

          <ChatInput
            value={input}
            onChange={setInput}
            onSubmit={send}
            disabled={loading}
            isStreaming={loading}
            onCancel={cancelStream}
            imageFile={imageFile}
            onImageUpload={setImageFile}
            onImageRemove={() => setImageFile(null)}
            attachedFile={attachedFile}
            onFileUpload={setAttachedFile}
            onFileRemove={() => setAttachedFile(null)}
            onScrollToBottom={scrollBottom}
          />
        </main>
      </div>
    </div>
  );
}