"use client";

import {
  useEffect,
  useState,
  useRef,
  useCallback,
  ChangeEvent,
  FormEvent,
} from "react";
import { useRouter, useParams } from "next/navigation";
import Link from "next/link";
import ReactMarkdown from "react-markdown";
import remarkGfm from "remark-gfm";
import axios from "axios";
import styles from "./page.module.css";
import {
  getDocuments,
  createChat,
  sendMessageStream,
  deleteDocument,
  checkWorkspaceAccess,
  getDashboard,
  DocumentItem,
} from "@/lib/api";

interface Message {
  id: string | number;
  role: "user" | "assistant";
  content: string;
}

export default function DocumentsPage() {
  const router = useRouter();
  const params = useParams();
  const workspaceId = Number(params.id);

  // Access States
  const [checkingAccess, setCheckingAccess] = useState(true);
  const [hasAccess, setHasAccess] = useState<boolean | null>(null);

  // Workspace & Plan Features Switcher State
  const [featureOptions, setFeatureOptions] = useState<any[]>([]);

  // Models State
  const [models, setModels] = useState<any[]>([]);
  const [selectedModelId, setSelectedModelId] = useState<number | null>(null);

  // Document States
  const [documents, setDocuments] = useState<DocumentItem[]>([]);
  const [selected, setSelected] = useState<DocumentItem[]>([]);
  const [loading, setLoading] = useState(true);

  // Upload States
  const [uploading, setUploading] = useState(false);
  const [uploadProgress, setUploadProgress] = useState(0);

  // Error & Action States
  const [errorMessage, setErrorMessage] = useState<string | null>(null);
  const [deletingId, setDeletingId] = useState<number | string | null>(null);

  // Chat States
  const [activeChatId, setActiveChatId] = useState<number | null>(null);
  const [messages, setMessages] = useState<Message[]>([]);
  const [input, setInput] = useState("");
  const [chatLoading, setChatLoading] = useState(false);

  const messagesEndRef = useRef<HTMLDivElement>(null);
  const abortControllerRef = useRef<AbortController | null>(null);

  const currentAiMessageRef = useRef<string>("");

  const scrollToBottom = () => {
    messagesEndRef.current?.scrollIntoView({ behavior: "smooth" });
  };

  useEffect(() => {
    scrollToBottom();
  }, [messages, chatLoading]);

  const handleBackendError = useCallback(
    (error: any, defaultMessage: string) => {
      console.error(error);
      let msg = defaultMessage;

      if (error.response?.data?.message) {
        msg = error.response.data.message;
      } else if (error.response?.data?.error) {
        msg = error.response.data.error;
      } else if (error.message) {
        msg = error.message;
      }

      setErrorMessage(msg);
      setTimeout(() => {
        setErrorMessage(null);
      }, 5000);
    },
    [],
  );

  useEffect(() => {
    let isMounted = true;

    async function verifyAccessAndExtractFeatures() {
      if (!workspaceId) {
        if (isMounted) {
          setHasAccess(false);
          setCheckingAccess(false);
          setLoading(false);
        }
        return;
      }

      if (isMounted) {
        setCheckingAccess(true);
        setLoading(true);
        setSelected([]);
        setMessages([]);
        setActiveChatId(null);
      }

      try {
        const dashboardData = await getDashboard();

        if (isMounted && dashboardData?.workspaces) {
          const options: any[] = [];

          dashboardData.workspaces.forEach((ws: any) => {
            if (Number(ws.id) === workspaceId) {
              let activeModels: any[] = [];

              if (
                ws.models &&
                Array.isArray(ws.models) &&
                ws.models.length > 0
              ) {
                activeModels = ws.models;
              } else if (
                ws.plan?.models &&
                Array.isArray(ws.plan.models) &&
                ws.plan.models.length > 0
              ) {
                activeModels = ws.plan.models;
              } else if (dashboardData.workspace_types) {
                const matchedType = dashboardData.workspace_types.find(
                  (wt: any) => wt.key === ws.type,
                );
                const matchedPlan = matchedType?.plans?.find(
                  (p: any) => p.id === ws.plan?.id,
                );
                if (matchedPlan?.models) {
                  activeModels = matchedPlan.models;
                }
              }

              setModels(activeModels);
              if (activeModels.length > 0) {
                setSelectedModelId(activeModels[0].id);
              }
            }

            const workspaceFeatures = ws.workspace_type?.features || [];
            const planCapabilities = ws.plan?.capabilities || {};

            workspaceFeatures.forEach((feature: any) => {
              const key = (feature.key || "").toLowerCase();

              const isAllowed =
                planCapabilities[key] === true ||
                planCapabilities[key] === undefined ||
                key === "chat";

              if (isAllowed) {
                const optionValue = `${ws.id}-${ws.name}-${key}`;
                const optionLabel = `${ws.display_name || ws.name} - ${feature.title || feature.key} (${ws.type})`;

                if (!options.some((f) => f.uniqueKey === optionValue)) {
                  options.push({
                    workspaceId: ws.id,
                    workspaceName: ws.name,
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

        const response = await checkWorkspaceAccess(workspaceId, "documents");
        if (isMounted) {
          if (response.has_access) {
            setHasAccess(true);
          } else {
            setHasAccess(false);
          }
        }
      } catch (error) {
        console.error("Access check failed:", error);
        if (isMounted) {
          setHasAccess(false);
        }
      } finally {
        if (isMounted) {
          setCheckingAccess(false);
        }
      }
    }

    verifyAccessAndExtractFeatures();

    return () => {
      isMounted = false;
    };
  }, [workspaceId]);

  const loadDocuments = useCallback(
    async (showGlobalLoading = false) => {
      if (!workspaceId) return;
      if (showGlobalLoading) setLoading(true);
      try {
        const response = await getDocuments(workspaceId);
        setDocuments(response.data ?? (response as any).documents ?? []);
      } catch (error: any) {
        handleBackendError(error, "خطا در دریافت لیست اسناد");
      } finally {
        if (showGlobalLoading) setLoading(false);
      }
    },
    [workspaceId, handleBackendError],
  );

  useEffect(() => {
    if (hasAccess && workspaceId) {
      loadDocuments(true);
    }
  }, [hasAccess, workspaceId, loadDocuments]);

  useEffect(() => {
    const hasPendingDoc = documents.some(
      (doc) => doc.status !== "completed" && doc.status !== "failed",
    );

    if (!hasPendingDoc) return;

    const interval = setInterval(() => {
      loadDocuments(false);
    }, 3000);

    return () => clearInterval(interval);
  }, [documents, loadDocuments]);
  async function toggleDocument(document: DocumentItem) {
    let updatedSelected: DocumentItem[] = [];
    const exists = selected.some((item) => item.id === document.id);

    if (exists) {
      updatedSelected = selected.filter((item) => item.id !== document.id);
    } else {
      updatedSelected = [...selected, document];
    }

    setSelected(updatedSelected);

    if (updatedSelected.length > 0) {
      try {
        const response = await createChat(workspaceId, {
          document_ids: updatedSelected.map((x) => x.id),
          model_id: selectedModelId ?? (models.length > 0 ? models[0].id : 16),
        });
        if (response.chat?.id) {
          setActiveChatId(response.chat.id);
        }
      } catch (error) {
        handleBackendError(error, "خطا در ایجاد نشست گفتگو");
      }
    } else {
      setActiveChatId(null);
      setMessages([]);
    }
  }
  async function handleUpload(event: ChangeEvent<HTMLInputElement>) {
    const file = event.target.files?.[0];
    if (!file) return;

    const formData = new FormData();
    formData.append("file", file);
    formData.append("workspace_id", String(workspaceId));

    try {
      setUploading(true);
      setUploadProgress(0);

      const token = localStorage.getItem("auth_token");
      await axios.post(
        `${process.env.NEXT_PUBLIC_API_URL || ""}/workspaces/${workspaceId}/documents`,
        formData,
        {
          headers: {
            "Content-Type": "multipart/form-data",
            ...(token ? { Authorization: `Bearer ${token}` } : {}),
          },
          onUploadProgress: (progressEvent) => {
            if (progressEvent.total) {
              const percentCompleted = Math.round(
                (progressEvent.loaded * 100) / progressEvent.total,
              );
              setUploadProgress(percentCompleted);
            }
          },
        },
      );

      await loadDocuments(false);
    } catch (error) {
      handleBackendError(error, "خطا در آپلود سند");
    } finally {
      setUploading(false);
      setUploadProgress(0);
      event.target.value = "";
    }
  }

  async function handleDeleteDocument(
    e: React.MouseEvent,
    docId: number | string,
  ) {
    e.stopPropagation();

    if (!confirm("آیا از حذف این سند مطمئن هستید؟")) return;

    try {
      setDeletingId(docId);
      await deleteDocument(workspaceId, docId);
      setSelected((prev) => prev.filter((doc) => doc.id !== docId));
      await loadDocuments(false);
    } catch (error) {
      handleBackendError(error, "خطا در حذف سند");
    } finally {
      setDeletingId(null);
    }
  }

  async function handleSendMessage(e: FormEvent) {
    e.preventDefault();
    if (!input.trim() || chatLoading || selected.length === 0) return;

    const userText = input.trim();
    setInput("");

    const tempUserMsgId = Date.now();
    const tempAiMsgId = tempUserMsgId + 1;

    currentAiMessageRef.current = "";

    setMessages((prev) => [
      ...prev,
      { id: tempUserMsgId, role: "user", content: userText },
      { id: tempAiMsgId, role: "assistant", content: "" },
    ]);

    setChatLoading(true);
    const controller = new AbortController();
    abortControllerRef.current = controller;

    let animFrameId: number | null = null;
    let renderScheduled = false;

    const updateMessagesUI = () => {
      setMessages((prev) =>
        prev.map((msg) =>
          msg.id === tempAiMsgId
            ? { ...msg, content: currentAiMessageRef.current }
            : msg,
        ),
      );
      renderScheduled = false;
    };

    const targetModelId =
      selectedModelId ?? (models.length > 0 ? models[0].id : 16);

    try {
      await sendMessageStream(
        userText,
        workspaceId,
        activeChatId ?? undefined,
        null,
        targetModelId,
        (chunk) => {
          if (!chunk) return;

          currentAiMessageRef.current += chunk;

          if (!renderScheduled) {
            renderScheduled = true;
            animFrameId = requestAnimationFrame(updateMessagesUI);
          }
        },
        controller.signal,
        null,
        selected.map((doc) => Number(doc.id)),
      );
    } catch (error) {
      handleBackendError(error, "خطا در دریافت پاسخ چت");
    } finally {
      if (animFrameId !== null) {
        cancelAnimationFrame(animFrameId);
      }
      updateMessagesUI();
      setChatLoading(false);
      abortControllerRef.current = null;
    }
  }

  if (checkingAccess || (hasAccess && loading)) {
    return (
      <div className={styles.loadingContainer}>
        <div className={styles.spinner}></div>
        <p>در حال بررسی دسترسی و بارگذاری...</p>
      </div>
    );
  }

  if (!hasAccess) {
    return (
      <div className={styles.notFoundContainer}>
        <div className={styles.notFoundContent}>
          <span className={styles.errorCode}>404</span>
          <h1 className={styles.errorTitle}>فضای کاری یافت نشد</h1>
          <p className={styles.errorDesc}>
            شما به این فضای کاری یا قابلیت اسناد دسترسی ندارید.
          </p>
          <Link href="/workspaces" className={styles.backButton}>
            بازگشت به لیست فضاهای کاری
          </Link>
        </div>
      </div>
    );
  }

  return (
    <div className={styles.container}>
      {errorMessage && (
        <div className={styles.errorToast}>
          <span>⚠️ {errorMessage}</span>
          <button onClick={() => setErrorMessage(null)}>×</button>
        </div>
      )}

      {uploading && (
        <div className={styles.uploadOverlay}>
          <div className={styles.uploadModal}>
            <div className={styles.uploadPulseIcon}>📄</div>
            <h3>در حال آپلود سند...</h3>
            <p>لطفاً تا اتمام بارگذاری شکیبا باشید</p>

            <div className={styles.progressBarBg}>
              <div
                className={styles.progressBarFill}
                style={{ width: `${uploadProgress}%` }}
              ></div>
            </div>
            <span className={styles.progressPercent}>{uploadProgress}%</span>
          </div>
        </div>
      )}

      <header className={styles.header}>
        <div
          className={styles.headerLeft}
          style={{ display: "flex", flexDirection: "column", gap: "10px" }}
        >
          <div
            style={{
              display: "flex",
              gap: "15px",
              alignItems: "center",
              flexWrap: "wrap",
            }}
          >
            <button className={styles.backBtn} onClick={() => router.back()}>
              ← بازگشت
            </button>
            <h1>📚 دستیار هوشمند اسناد</h1>

            {featureOptions.length > 0 && (
              <div
                style={{ display: "flex", alignItems: "center", gap: "6px" }}
              >
                <span style={{ fontSize: "13px", color: "#94a3b8" }}>
                  پلن و فیچر:
                </span>
                <select
                  value={
                    featureOptions.find(
                      (opt) =>
                        opt.workspaceId === workspaceId &&
                        opt.path === "documents",
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

            {/* {models.length > 0 && (
              <div style={{ display: "flex", alignItems: "center", gap: "6px" }}>
                <span style={{ fontSize: "13px", color: "#94a3b8" }}>مدل:</span>
                <select
                  value={selectedModelId ?? models[0].id}
                  onChange={(e) => setSelectedModelId(Number(e.target.value))}
                  style={{
                    background: "rgba(16, 185, 129, 0.1)",
                    border: "1px solid rgba(16, 185, 129, 0.4)",
                    color: "#fff",
                    padding: "6px 12px",
                    borderRadius: "8px",
                    fontSize: "13px",
                    cursor: "pointer",
                    outline: "none",
                    fontWeight: 600,
                  }}
                >
                  {models.map((model) => (
                    <option key={model.id} value={model.id} style={{ background: "#1e293b", color: "#fff" }}>
                      {model.name || model.display_name || `مدل ${model.id}`}
                    </option>
                  ))}
                </select>
              </div>
            )} */}
          </div>
          <p>اسناد مورد نظر خود را انتخاب کرده و بلافاصله گفتگو را شروع کنید</p>
        </div>

        <div className={styles.actions}>
          <label className={styles.uploadBtn}>
            <span className={styles.plusIcon}>+</span>
            <span>آپلود سند جدید</span>
            <input
              type="file"
              hidden
              accept=".pdf,.doc,.docx,.txt"
              onChange={handleUpload}
              disabled={uploading}
            />
          </label>
        </div>
      </header>

      <div className={styles.layout}>
        <aside className={styles.sidebar}>
          <div className={styles.sidebarHeader}>
            <h2>اسناد شما</h2>
            <span className={styles.badge}>{documents.length} فایل</span>
          </div>

          <div className={styles.documentList}>
            {documents.length === 0 ? (
              <div className={styles.emptySidebar}>هیچ سندی یافت نشد</div>
            ) : (
              documents.map((doc) => {
                const isSelected = selected.some((x) => x.id === doc.id);
                const isDeleting = deletingId === doc.id;
                const isProcessing =
                  doc.status !== "completed" && doc.status !== "failed";

                return (
                  <div
                    key={doc.id}
                    onClick={() => !isProcessing && toggleDocument(doc)}
                    className={`${styles.docCard} ${isSelected ? styles.docCardActive : ""} ${
                      isProcessing ? styles.docCardDisabled : ""
                    }`}
                  >
                    <div className={styles.checkbox}>{isSelected && "✓"}</div>
                    <div className={styles.docInfo}>
                      <h3>{doc.name}</h3>
                      <div className={styles.docMeta}>
                        <span>
                          {doc.size ? Math.round(doc.size / 1024) : 0} KB
                        </span>
                        <span className={styles.dot}>•</span>
                        <span
                          className={
                            doc.status === "completed"
                              ? styles.statusReady
                              : doc.status === "failed"
                                ? styles.statusFailed
                                : styles.statusPending
                          }
                        >
                          {doc.status === "completed"
                            ? "آماده"
                            : doc.status === "failed"
                              ? "خطا در پردازش"
                              : "در حال پردازش..."}
                        </span>
                      </div>
                    </div>

                    <button
                      className={styles.deleteBtn}
                      title="حذف سند"
                      disabled={isDeleting}
                      onClick={(e) => handleDeleteDocument(e, doc.id)}
                    >
                      {isDeleting ? "..." : "🗑️"}
                    </button>
                  </div>
                );
              })
            )}
          </div>
        </aside>

        <main className={styles.chatMain}>
          {selected.length === 0 ? (
            <div className={styles.selectPrompt}>
              <div className={styles.promptIcon}>📄</div>
              <h2>یک یا چند سند را انتخاب کنید</h2>
              <p>
                برای شروع گفتگو با هوش مصنوعی، حداقل یک سند را از پنل سمت راست
                انتخاب کنید.
              </p>
            </div>
          ) : (
            <div className={styles.chatContainer}>
              <div className={styles.chatHeaderBar}>
                <span className={styles.chatHeaderTitle}>اسناد فعال:</span>
                <div className={styles.chipContainer}>
                  {selected.map((doc) => (
                    <div key={doc.id} className={styles.docChip}>
                      <span>📄 {doc.name}</span>
                      <button onClick={() => toggleDocument(doc)}>×</button>
                    </div>
                  ))}
                </div>
              </div>

              <div className={styles.messagesArea}>
                {messages.length === 0 ? (
                  <div className={styles.emptyMessages}>
                    <span>💡</span>
                    <p>سوال خود را درباره اسناد انتخاب‌شده بپرسید...</p>
                  </div>
                ) : (
                  messages.map((msg) => (
                    <div
                      key={msg.id}
                      className={`${styles.messageRow} ${
                        msg.role === "user" ? styles.userRow : styles.aiRow
                      }`}
                    >
                      <div className={styles.avatar}>
                        {msg.role === "user" ? "👤" : "🤖"}
                      </div>
                      <div className={styles.messageBubble}>
                        <div className={styles.senderName}>
                          {msg.role === "user" ? "شما" : "دستیار هوشمند"}
                        </div>

                        <div className={styles.markdownContent}>
                          {msg.content ? (
                            <ReactMarkdown remarkPlugins={[remarkGfm]}>
                              {msg.content}
                            </ReactMarkdown>
                          ) : (
                            chatLoading &&
                            msg.role === "assistant" && (
                              <div className={styles.thinkingContainer}>
                                <div className={styles.thinkingDots}>
                                  <span></span>
                                  <span></span>
                                  <span></span>
                                </div>
                                <span className={styles.thinkingText}>
                                  دستیار در حال پردازش و تفکر...
                                </span>
                              </div>
                            )
                          )}
                        </div>
                      </div>
                    </div>
                  ))
                )}
                <div ref={messagesEndRef} />
              </div>

              <form onSubmit={handleSendMessage} className={styles.inputForm}>
                <input
                  type="text"
                  placeholder="سوال خود را بنویسید..."
                  value={input}
                  onChange={(e) => setInput(e.target.value)}
                  disabled={chatLoading}
                />
                <button type="submit" disabled={chatLoading || !input.trim()}>
                  {chatLoading ? "..." : "ارسال"}
                </button>
              </form>
            </div>
          )}
        </main>
      </div>
    </div>
  );
}
