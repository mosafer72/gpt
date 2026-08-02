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
  const [currentChat, setCurrentChat] = useState<Chat | null>(null);

  const [userName, setUserName] = useState<string>("");
  const [isFullyLoaded, setIsFullyLoaded] = useState<boolean>(false);
  const [isDataInitialized, setIsDataInitialized] = useState<boolean>(false);
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
      model_id: null,
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
      const chatObj = (initialChat as any)?.chat ?? initialChat;
      const rawMessages = chatObj?.messages ?? chatObj?.chat?.messages ?? [];

      const sortedMessages = [...rawMessages].sort(
        (a, b) =>
          new Date(a.created_at).getTime() - new Date(b.created_at).getTime(),
      );

      setCurrentChat({
        ...chatObj,
        messages: sortedMessages,
      });
      setIsFullyLoaded(true);
      setIsSwitchingChat(false);
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
          try {
            const data = await getChat(workspaceId, chatIdFromUrl);
            const chatData = (data as any)?.chat ?? data;

            if (chatData) {
              const rawMessages = chatData.messages ?? [];
              const sortedMessages = [...rawMessages].sort(
                (a, b) =>
                  new Date(a.created_at).getTime() -
                  new Date(b.created_at).getTime(),
              );

              setCurrentChat({
                ...chatData,
                messages: sortedMessages,
              });
              setIsFullyLoaded(true);
            }
          } catch (error) {
            console.error("Error loading chat from path:", error);
          } finally {
            setIsSwitchingChat(false);
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
          setIsDataInitialized(true);
          return;
        }

        const response: any = await getWorkspaceModels(workspaceId);
        const fetchedModels: AIModel[] =
          response?.models || response?.data || response || [];
        setModels(fetchedModels);
        if (fetchedModels && fetchedModels.length > 0) {
          const defaultModel =
            fetchedModels.find((m: any) => m.pivot?.is_default === 1) ??
            fetchedModels[0];
          setActiveModel(defaultModel);
        }
      } catch (e) {
        console.error("Error initializing workspace data:", e);
      } finally {
        setIsDataInitialized(true);
      }
    }

    initData();
  }, [workspaceId, initialModels]);

  useEffect(() => {
    if (currentChat && currentChat.id !== 0) {
      const modelId = currentChat.model_id ?? (currentChat as any).model?.id;
      if (modelId && models.length > 0) {
        const found = models.find((m) => m.id === modelId);
        if (found) {
          setActiveModel(found);
        }
      }
    } else if (models.length > 0 && !activeModel) {
      const defaultModel =
        models.find((m: any) => m.pivot?.is_default === 1) ?? models[0];
      setActiveModel(defaultModel);
    }
  }, [currentChat, models]);

  useEffect(() => {
    const segments = pathname.split("/");
    const chatIndex = segments.indexOf("chat");
    const hasChatIdInUrl = chatIndex !== -1 && segments[chatIndex + 1];

    if (
      isFullyLoaded &&
      isDataInitialized &&
      !initialChat &&
      !hasChatIdInUrl &&
      (!currentChat || currentChat.id === 0) &&
      (!currentChat || currentChat.messages?.length === 0)
    ) {
      streamWelcomeMessage(`سلام ${userName || "کاربر"} عزیز، چطور می‌تونم کمکت کنم؟
         لطفا اول مدلی که میخوای چت کنی رو انتخاب کن تا بتونیم بهتر به هدفت برسیم.`);
    }
  }, [isFullyLoaded, isDataInitialized, userName, initialChat, pathname]);

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

    router.push(`/workspace/${workspaceId}/chat`);

    hasStreamedWelcome.current = false;
    streamWelcomeMessage(`سلام ${userName || "کاربر"} عزیز، چطور می‌تونم کمکت کنم؟
       .لطفا اول مدلی که میخوای چت کنی رو انتخاب کن تا بتونیم بهتر به هدفت برسیم`);

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

  // ✅ تابع کمکی برای فشرده‌سازی عکس و تبدیل آن به آبجکت File استاندارد
  const compressImageFile = (file: File): Promise<File> => {
    return new Promise((resolve) => {
      const reader = new FileReader();
      reader.onload = (event) => {
        const img = new Image();
        img.onload = () => {
          const canvas = document.createElement("canvas");
          const MAX_WIDTH = 800;
          const MAX_HEIGHT = 800;
          let width = img.width;
          let height = img.height;

          if (width > height) {
            if (width > MAX_WIDTH) {
              height *= MAX_WIDTH / width;
              width = MAX_WIDTH;
            }
          } else {
            if (height > MAX_HEIGHT) {
              width *= MAX_HEIGHT / height;
              height = MAX_HEIGHT;
            }
          }

          canvas.width = width;
          canvas.height = height;
          const ctx = canvas.getContext("2d");
          ctx?.drawImage(img, 0, 0, width, height);

          canvas.toBlob(
            (blob) => {
              if (blob) {
                const compressedFile = new File([blob], file.name, {
                  type: "image/jpeg",
                  lastModified: Date.now(),
                });
                resolve(compressedFile);
              } else {
                resolve(file); // اگر خطایی رخ داد فایل اصلی برگردانده شود
              }
            },
            "image/jpeg",
            0.75, // کیفیت فشرده‌سازی (75 درصد)
          );
        };
        img.src = event.target?.result as string;
      };
      reader.readAsDataURL(file);
    });
  };

  async function send(e: FormEvent) {
    e.preventDefault();

    if ((!input.trim() && !imageFile && !attachedFile) || !activeModel) return;

    setLoading(true);

    const controller = new AbortController();
    abortRef.current = controller;

    const text = input.trim();
    let fileToSend = imageFile;
    let documentToSend = attachedFile; // ✅ متغیر برای نگهداری فایل غیر تصویری (مثل PDF)

    setInput("");
    setImageFile(null);
    setAttachedFile(null);

    // اعمال فشرده‌سازی روی عکس در صورت وجود
    if (fileToSend) {
      try {
        fileToSend = await compressImageFile(fileToSend);
      } catch (err) {
        console.error("Image compression failed:", err);
      }
    }

    const tempId = Date.now();
    const isNewChat = !currentChat || currentChat.id === 0;

    setCurrentChat((prev) => {
      const base: Chat = prev ?? {
        id: 0,
        user_id: null,
        title: "چت جدید",
        summary: null,
        model_id: null,
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
      // ✅ ارسال فایل پیوست‌شده (documentToSend) به متد sendMessageStream
      await sendMessageStream(
        text,
        workspaceId,
        isNewChat ? undefined : currentChat?.id,
        fileToSend, // فایل عکس (یا null)
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
        documentToSend, // ✅ ارسال فایل PDF یا داکیومنت به عنوان پارامتر جدید به تابع API
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

  const messagesList = currentChat?.messages || [];

  return (
    <div className={styles.container}>
      <header className={styles.header}>
        <div
          className={styles.headerLeft}
          style={{ display: "flex", gap: "15px", alignItems: "center" }}
        >
          <span className={styles.logo}>✨ دانا‌بوم</span>

          <div className={styles.modelSelectorContainer}>
            <span className={styles.modelSelectorLabel}>مدل:</span>
            <select
              value={activeModel?.id ?? ""}
              onChange={(e) => {
                const selected = models.find(
                  (m) => m.id === Number(e.target.value),
                );
                if (selected) setActiveModel(selected);
              }}
              disabled={Boolean(currentChat && currentChat.id !== 0)}
              className={styles.modelSelect}
            >
              {models.length === 0 ? (
                <option value="" disabled className={styles.modelOption}>
                  بدون مدل فعال
                </option>
              ) : (
                models.map((model) => (
                  <option
                    key={model.id}
                    value={model.id}
                    className={styles.modelOption}
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
                میانبر بین فضاها
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
                        <span className={styles.typingText}></span>
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
