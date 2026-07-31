"use client";

import { useEffect, useRef, useState, FormEvent } from "react";
import { useRouter, usePathname } from "next/navigation";
import {
  sendMessageStream,
  getChats,
  getChat,
  deleteChat,
  getWorkspaceModels,
  getDashboard,
} from "@/lib/api";
import { Chat, AIModel, Workspace, DashboardResponse } from "@/types";

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
  models: initialModels = [],
  initialChat,
  onNewChat,
  selectedModel,
}: Props) {
  const router = useRouter();
  const pathname = usePathname();

  const [chats, setChats] = useState<Chat[]>([]);
  const [currentChat, setCurrentChat] = useState<Chat | null>(
    initialChat ?? null,
  );

  const [userName, setUserName] = useState<string>("کاربر");
  const [isFullyLoaded, setIsFullyLoaded] = useState<boolean>(false);
  const [isSwitchingChat, setIsSwitchingChat] = useState<boolean>(false);
  const hasStreamedWelcome = useRef<boolean>(false);

  const streamWelcomeMessage = (fullText: string) => {
    if (hasStreamedWelcome.current) return;
    hasStreamedWelcome.current = true;

    const welcomeMsgId = Date.now();

    setCurrentChat({
      id: 0,
      user_id: null,
      title: "چت جدید",
      summary: null,
      messages: [
        {
          id: welcomeMsgId,
          chat_id: 0,
          user_id: null,
          role: "assistant",
          content: "",
          created_at: new Date().toISOString(),
          updated_at: new Date().toISOString(),
        },
      ],
      created_at: new Date().toISOString(),
      updated_at: new Date().toISOString(),
    });

    const tokens = fullText.match(/([^\s]+|\s+)/g) || [fullText];
    let currentIndex = 0;

    const interval = setInterval(() => {
      if (currentIndex < tokens.length) {
        const nextToken = tokens[currentIndex];
        setCurrentChat((prev) => {
          if (!prev) return prev;
          return {
            ...prev,
            messages: (prev.messages || []).map((msg) =>
              msg.id === welcomeMsgId
                ? { ...msg, content: msg.content + nextToken }
                : msg,
            ),
          };
        });
        currentIndex++;
        if (messagesRef.current) {
          messagesRef.current.scrollTop = messagesRef.current.scrollHeight;
        }
      } else {
        clearInterval(interval);
      }
    }, 40);
  };

  useEffect(() => {
    if (initialChat) {
      const chatObj = (initialChat as any)?.chat || initialChat;
      const sortedChat = {
        ...chatObj,
        messages: [...(chatObj?.messages ?? [])].sort(
          (a, b) =>
            new Date(a.created_at).getTime() -
            new Date(b.created_at).getTime(),
        ),
      };
      setCurrentChat(sortedChat);
      setIsFullyLoaded(true);
    } else {
      const timer = setTimeout(() => {
        setIsFullyLoaded(true);
      }, 300);
      return () => clearTimeout(timer);
    }
  }, [initialChat]);

  useEffect(() => {
    async function loadChatFromPath() {
      const segments = pathname.split("/");
      const chatIndex = segments.indexOf("chat");
      if (chatIndex !== -1 && segments[chatIndex + 1]) {
        const chatIdFromUrl = Number(segments[chatIndex + 1]);
        if (
          chatIdFromUrl &&
          (!currentChat || currentChat.id !== chatIdFromUrl)
        ) {
          setIsSwitchingChat(true);
          const data = await getChat(workspaceId, chatIdFromUrl);
          const chatData = data;
          if (chatData) {
            const sortedChat = {
              ...chatData,
              messages: [...(chatData.messages ?? [])].sort(
                (a, b) =>
                  new Date(a.created_at).getTime() -
                  new Date(b.created_at).getTime(),
              ),
            };
            setCurrentChat(sortedChat);
            setIsFullyLoaded(true);
          }
        }
      }
    }

    loadChatFromPath();
  }, [pathname, workspaceId]);

  const [models, setModels] = useState<AIModel[]>(initialModels);
  const [activeModel, setActiveModel] = useState<AIModel | null>(
    selectedModel ?? null,
  );
  const [featureOptions, setFeatureOptions] = useState<any[]>([]);

  const [input, setInput] = useState("");
  const [loading, setLoading] = useState(false);

  const [imageFile, setImageFile] = useState<File | null>(null);
  const [attachedFile, setAttachedFile] = useState<File | null>(null);

  const messagesRef = useRef<HTMLDivElement | null>(null);
  const abortRef = useRef<AbortController | null>(null);

  useEffect(() => {
    async function initData() {
      try {
        const dashboardData: DashboardResponse = await getDashboard();

        if (dashboardData?.user?.name) {
          setUserName(dashboardData.user.name);
        }

        if (dashboardData?.workspaces) {
          const options: any[] = [];

          dashboardData.workspaces.forEach((ws) => {
            const planName =
              ws.plan?.display_name ||
              ws.plan?.name ||
              ws.workspace_type?.display_name ||
              ws.workspace_type?.name ||
              "پلن فعال";
            const workspaceFeatures = ws.workspace_type?.features || [];
            const planCapabilities = ws.plan?.capabilities || {};

            workspaceFeatures.forEach((feature: any) => {
              const key = (feature.key || "").toLowerCase();
              const isAllowed =
                planCapabilities[key as keyof typeof planCapabilities] ===
                  true ||
                planCapabilities[key as keyof typeof planCapabilities] ===
                  undefined ||
                key === "chat";

              if (isAllowed) {
                const optionValue = `${ws.id}-${ws.name}-${key}`;
                const optionLabel = `${ws.name} - ${feature.title || feature.key} (${ws.type})`;

                if (!options.some((f) => f.uniqueKey === optionValue)) {
                  options.push({
                    workspaceId: ws.id,
                    workspaceName: ws.name,
                    planName: planName,
                    featureTitle: feature.title || feature.key,
                    path: feature.path || key,
                    uniqueKey: optionValue,
                    label: optionLabel,
                  });
                }
              }
            });
          });

          setFeatureOptions(options);
        }

        if (initialModels && initialModels.length > 0) {
          setModels(initialModels);
          const defaultModel =
            initialModels.find((m: any) => m.pivot?.is_default === 1) ??
            initialModels[0];
          setActiveModel(defaultModel);
          return;
        }

        const response: any = await getWorkspaceModels(workspaceId);
        const fetchedModels: AIModel[] = response?.models || response?.data || response || [];
        setModels(fetchedModels);
        if (fetchedModels && fetchedModels.length > 0) {
          const defaultModel =
            fetchedModels.find((m: any) => m.pivot?.is_default === 1) ??
            fetchedModels[0];
          setActiveModel(defaultModel);
        }
      } catch (e) {
        console.error("Error initializing workspace data:", e);
      }
    }

    initData();
  }, [workspaceId, initialModels]);

  useEffect(() => {
    if (
      isFullyLoaded &&
      (!initialChat || currentChat?.id === 0) &&
      (!currentChat || currentChat.messages?.length === 0)
    ) {
      streamWelcomeMessage(`سلام ${userName} عزیز، چطور می‌تونم کمکت کنم؟`);
    }
  }, [isFullyLoaded, userName]);

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
    if (currentChat?.id === chat.id) return;
    router.push(`/workspace/${workspaceId}/chat/${chat.id}`);
  }

  const handleNewChat = () => {
    if (abortRef.current) {
      abortRef.current.abort();
      setLoading(false);
    }

    setInput("");
    setImageFile(null);
    setAttachedFile(null);

    router.push(`/workspace/${workspaceId}`);

    hasStreamedWelcome.current = false;
    streamWelcomeMessage(`سلام ${userName} عزیز، چطور می‌تونم کمکت کنم؟`);

    if (onNewChat) {
      onNewChat();
    }
  };

  function scrollBottom() {
    if (messagesRef.current) {
      messagesRef.current.scrollTop = messagesRef.current.scrollHeight;
    }
  }

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

    const tempId = Date.now();
    const isNewChat = !currentChat || currentChat.id === 0;

    setCurrentChat((prev) => {
      const base: Chat = prev ?? {
        id: 0,
        user_id: null,
        title: "چت جدید",
        summary: null,
        messages: [],
        created_at: new Date().toISOString(),
        updated_at: new Date().toISOString(),
      };

      return {
        ...base,
        messages: [
          ...(base.messages || []),
          {
            id: tempId,
            chat_id: base.id,
            user_id: null,
            role: "user",
            content: text,
            created_at: new Date().toISOString(),
            updated_at: new Date().toISOString(),
          },
          {
            id: tempId + 1,
            chat_id: base.id,
            user_id: null,
            role: "assistant",
            content: "",
            created_at: new Date().toISOString(),
            updated_at: new Date().toISOString(),
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
              messages: (prev.messages || []).map((msg) => {
                if (msg.id === tempId + 1) {
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

      if (isNewChat && latestChats.length > 0) {
        const newlyCreatedChat = latestChats[0];
        if (newlyCreatedChat) {
          setCurrentChat((prev) =>
            prev ? { ...prev, id: newlyCreatedChat.id } : newlyCreatedChat,
          );
          window.history.replaceState(
            null,
            "",
            `/workspace/${workspaceId}/chat/${newlyCreatedChat.id}`,
          );
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

  const messagesList =
    currentChat?.messages || (currentChat as any)?.chat?.messages || [];

  return (
    <div className={styles.container}>
      <header className={styles.header}>
        <div
          className={styles.headerLeft}
          style={{ display: "flex", gap: "15px", alignItems: "center" }}
        >
          <span className={styles.logo}>✨ اکانتسرا</span>

          <div style={{ display: "flex", alignItems: "center", gap: "6px" }}>
            <span style={{ fontSize: "13px", color: "#94a3b8" }}>مدل:</span>
            <select
              value={activeModel?.id ?? ""}
              onChange={(e) => {
                const selected = models.find(
                  (m) => m.id === Number(e.target.value),
                );
                if (selected) setActiveModel(selected);
              }}
              style={{
                background: "rgba(255, 255, 255, 0.08)",
                border: "1px solid rgba(255, 255, 255, 0.15)",
                color: "#fff",
                padding: "6px 12px",
                borderRadius: "8px",
                fontSize: "13px",
                cursor: "pointer",
                outline: "none",
              }}
            >
              {models.length === 0 ? (
                <option value="" disabled>
                  بدون مدل فعال
                </option>
              ) : (
                models.map((model) => (
                  <option
                    key={model.id}
                    value={model.id}
                    style={{ background: "#1e293b", color: "#fff" }}
                  >
                    {model.name}
                  </option>
                ))
              )}
            </select>
          </div>

          {featureOptions.length > 0 && (
            <div style={{ display: "flex", alignItems: "center", gap: "6px" }}>
              <span style={{ fontSize: "13px", color: "#94a3b8" }}>
                پلن و فیچر:
              </span>
              <select
                value={
                  featureOptions.find(
                    (opt) =>
                      opt.workspaceId === workspaceId && opt.path === "chat",
                  )?.uniqueKey || ""
                }
                onChange={(e) => {
                  const selectedUniqueKey = e.target.value;
                  const matchedOption = featureOptions.find(
                    (opt) => opt.uniqueKey === selectedUniqueKey,
                  );
                  if (matchedOption) {
                    router.push(
                      `/workspace/${matchedOption.workspaceId}/${matchedOption.path}`,
                    );
                  }
                }}
                style={{
                  background: "rgba(59, 130, 246, 0.1)",
                  border: "1px solid rgba(59, 130, 246, 0.4)",
                  color: "#fff",
                  padding: "6px 12px",
                  borderRadius: "8px",
                  fontSize: "13px",
                  cursor: "pointer",
                  outline: "none",
                  fontWeight: 600,
                }}
              >
                <option
                  value=""
                  disabled
                  style={{ background: "#1e293b", color: "#94a3b8" }}
                >
                  انتخاب پلن و فیچر...
                </option>
                {featureOptions.map((opt) => (
                  <option
                    key={opt.uniqueKey}
                    value={opt.uniqueKey}
                    style={{ background: "#1e293b", color: "#fff" }}
                  >
                    {opt.label}
                  </option>
                ))}
              </select>
            </div>
          )}
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

        <main className={styles.main} style={{ position: "relative" }}>
          {isSwitchingChat && (
            <div className={styles.chatLoadingOverlay}>
              <div className={styles.spinner}></div>
              <span className={styles.loadingText}>
                در حال بارگذاری گفتگو...
              </span>
            </div>
          )}

          <div ref={messagesRef} className={styles.messagesContainer}>
            {messagesList.length > 0 ? (
              messagesList.map((msg: any) => {
                const isAiEmpty =
                  msg.role === "assistant" &&
                  (!msg.content || msg.content.trim() === "");

                if (isAiEmpty) {
                  return (
                    <div key={msg.id} className={styles.typingIndicatorWrapper}>
                      <div className={styles.typingBubble}>
                        <span className={styles.typingDot}></span>
                        <span className={styles.typingDot}></span>
                        <span className={styles.typingDot}></span>
                        <span className={styles.typingText}>
                          در حال تفکر...
                        </span>
                      </div>
                    </div>
                  );
                }

                return <ChatMessage key={msg.id} message={msg} />;
              })
            ) : (
              <div
                style={{
                  display: "flex",
                  justifyContent: "center",
                  alignItems: "center",
                  height: "100%",
                  color: "#64748b",
                }}
              >
                پیامی برای نمایش وجود ندارد. گفتگوی جدیدی را شروع کنید!
              </div>
            )}
          </div>

          <ChatInput
            value={input}
            onChange={setInput}
            onSubmit={send}
            disabled={loading || !activeModel || isSwitchingChat}
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