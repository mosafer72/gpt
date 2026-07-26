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
  selectedModel?: any;
}

export default function ChatInterface({
  workspace,
  workspaceId,
  models = [],
  initialChat,
  onNewChat,
  documents,
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

  const messagesRef = useRef<HTMLDivElement | null>(null);
  const abortRef = useRef<AbortController | null>(null);

  useEffect(() => {
    if (!models.length) {
      setActiveModel(null);
      return;
    }
    const defaultModel =
      models.find((m: any) => m.pivot?.is_default === 1) ?? models[0];
    setActiveModel(defaultModel);
  }, [models]);

  useEffect(() => {
    loadChats();
  }, []);

  async function loadChats() {
    try {
      const data = await getChats(workspaceId);
      setChats(data);
    } catch (e) {
      console.log(e);
    }
  }

  async function selectChat(chat: Chat) {
    try {
      const data = await getChat(workspaceId, chat.id);
      setCurrentChat(data);
      router.push(`/workspace/${workspaceId}/chat/${chat.id}`);
    } catch (e) {
      console.log(e);
    }
  }

  function scrollBottom() {
    if (messagesRef.current) {
      messagesRef.current.scrollTop = messagesRef.current.scrollHeight;
    }
  }

  async function send(e: FormEvent) {
    e.preventDefault();

    if (!input.trim()) return;

    if (!activeModel) {
      alert("مدلی برای این Workspace وجود ندارد");
      return;
    }

    setLoading(true);

    const controller = new AbortController();
    abortRef.current = controller;

    const text = input.trim();
    setInput("");

    const tempId = Date.now();
    const isNewChat = !currentChat || currentChat.id === 0;

    setCurrentChat((prev) => {
      const base =
        prev ??
        ({
          id: 0,
          title: "چت جدید",
          messages: [],
        } as Chat);

      return {
        ...base,
        messages: [
          ...base.messages,
          {
            id: tempId,
            role: "user",
            content: text,
            created_at: new Date().toISOString(),
          } as any,
          {
            id: `ai-${tempId}`,
            role: "assistant",
            content: "",
            created_at: new Date().toISOString(),
          } as any,
        ],
      };
    });

    scrollBottom();

    try {
      await sendMessageStream(
        text,
        workspaceId,
        isNewChat ? undefined : currentChat?.id,
        null,
        activeModel.id,
        (chunk) => {
          let textChunk = chunk;

          try {
            const json = JSON.parse(chunk);
            if (json && typeof json.content === "string") {
              textChunk = json.content;
            }
          } catch {}

          setCurrentChat((prev) => {
            if (!prev) return prev;

            return {
              ...prev,
              messages: prev.messages.map((msg) => {
                if (msg.id === `ai-${tempId}`) {
                  return {
                    ...msg,
                    content: msg.content + textChunk,
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

      if (isNewChat && latestChats.length > 0) {
        const created = latestChats[0];
        if (created) {
          router.push(`/workspace/${workspaceId}/chat/${created.id}`);
        }
      }
    } catch (e) {
      console.error(e);
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
            onNewChat={onNewChat}
            onDeleteChat={async (id) => {
              await deleteChat(workspaceId, id);
              await loadChats();
            }}
          />
        </div>

        <main className={styles.main}>
          <div ref={messagesRef} className={styles.messagesContainer}>
            {currentChat?.messages?.map((msg: any) => (
              <ChatMessage key={msg.id} message={msg} />
            ))}
          </div>

          <ChatInput
            value={input}
            onChange={setInput}
            onSubmit={send}
            disabled={loading}
            isStreaming={loading}
            onCancel={() => {
              abortRef.current?.abort();
              setLoading(false);
            }}
          />
        </main>
      </div>
    </div>
  );
}
