"use client";

import {
  useEffect,
  useState,
  useRef,
  useCallback,
  useMemo,
  ChangeEvent,
  FormEvent,
} from "react";
import { useParams, useRouter } from "next/navigation";
import Link from "next/link";
import ReactMarkdown from "react-markdown";
import remarkGfm from "remark-gfm";
import axios from "axios";
import { DocumentItem } from "./components/DocumentCard";

import styles from "./page.module.css";
import SearchBar from "./components/SearchBar";
import FilterBar from "./components/FilterBar";
import {
  getDocuments,
  checkWorkspaceAccess,
  createChat,
  sendMessageStream,
  deleteDocument,
  getDashboard,
} from "@/lib/api";
import DocumentCard from "./components/DocumentCard";

interface Message {
  id: string | number;
  role: "user" | "assistant";
  content: string;
}

export default function LibraryPage() {
  const router = useRouter();
  const params = useParams();
  const workspaceId = Number(params.id);

  const [documents, setDocuments] = useState<DocumentItem[]>([]);
  const [loading, setLoading] = useState(true);
  const [checkingAccess, setCheckingAccess] = useState(true);
  const [hasAccess, setHasAccess] = useState<boolean | null>(null);

  const [search, setSearch] = useState("");
  const [status, setStatus] = useState("all");
  const [errorMessage, setErrorMessage] = useState<string | null>(null);

  // Upload States
  const [uploading, setUploading] = useState(false);
  const [uploadProgress, setUploadProgress] = useState(0);

  // Workspace Switcher Options State
  const [featureOptions, setFeatureOptions] = useState<any[]>([]);

  // Chat Modal States
  const [isChatOpen, setIsChatOpen] = useState(false);
  const [activeDocument, setActiveDocument] = useState<DocumentItem | null>(null);
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
      setTimeout(() => setErrorMessage(null), 5000);
    },
    [],
  );

  useEffect(() => {
    async function verifyAccess() {
      if (!workspaceId) {
        setHasAccess(false);
        setCheckingAccess(false);
        setLoading(false);
        return;
      }

      try {
        // ۱. دریافت داشبورد برای ساخت دراپ‌دان هوشمند فیچرها و پلن‌ها
        const dashboardData = await getDashboard();
        if (dashboardData?.workspaces) {
          const options: any[] = [];

          dashboardData.workspaces.forEach((ws: any) => {
            const planName = ws.plan?.display_name || ws.plan?.name || ws.workspace_type?.display_name || ws.workspace_type?.name || "پلن فعال";
            const workspaceFeatures = ws.workspace_type?.features || [];
            const planCapabilities = ws.plan?.capabilities || {};

            workspaceFeatures.forEach((feature: any) => {
              const key = (feature.key || "").toLowerCase();
              const isAllowed = 
                planCapabilities[key] === true || 
                planCapabilities[key] === undefined || 
                key === "library";

              if (isAllowed) {
                const optionValue = `${ws.id}-${ws.name}-${key}`;
                const optionLabel = `${ws.display_name || ws.name} - ${feature.title || feature.key} (${ws.type})`;

                if (!options.some((f) => f.uniqueKey === optionValue)) {
                  options.push({
                    workspaceId: ws.id,
                    workspaceName: ws.name,
                    planName: planName,
                    featureTitle: feature.title || feature.key,
                    path: feature.path || key,
                    uniqueKey: optionValue,
                    label: optionLabel
                  });
                }
              }
            });
          });

          setFeatureOptions(options);
        }

        // ۲. بررسی دسترسی به کتابخانه
        const response = await checkWorkspaceAccess(workspaceId, "library");
        if (response.has_access) {
          setHasAccess(true);
        } else {
          setHasAccess(false);
        }
      } catch (error) {
        console.error("Access check failed:", error);
        setHasAccess(false);
      } finally {
        setCheckingAccess(false);
      }
    }

    verifyAccess();
  }, [workspaceId]);

  const loadDocuments = useCallback(
    async (showGlobalLoading = false) => {
      if (showGlobalLoading) setLoading(true);
      try {
        const response: any = await getDocuments(workspaceId);
        const docsArray = Array.isArray(response)
          ? response
          : response?.documents || response?.data || [];
        setDocuments(Array.isArray(docsArray) ? docsArray : []);
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
      (doc: any) => doc.status !== "completed" && doc.status !== "failed",
    );
    if (!hasPendingDoc) return;

    const interval = setInterval(() => {
      loadDocuments(false);
    }, 3000);

    return () => clearInterval(interval);
  }, [documents, loadDocuments]);

  async function handleOpenChatModal(doc: DocumentItem) {
    setActiveDocument(doc);
    setIsChatOpen(true);
    setMessages([]);
    setActiveChatId(null);

    try {
      const response = await createChat(workspaceId, {
        document_ids: [Number(doc.id)],
        model_id: 16,
      });
      if (response.chat?.id) {
        setActiveChatId(response.chat.id);
      }
    } catch (error) {
      handleBackendError(error, "خطا در ایجاد نشست گفتگو با مقاله");
    }
  }

  function handleCloseChatModal() {
    setIsChatOpen(false);
    setActiveDocument(null);
    setMessages([]);
    setActiveChatId(null);
  }

  async function handleUpload(event: ChangeEvent<HTMLInputElement>) {
    const file = event.target.files?.[0];
    if (!file) return;

    const formData = new FormData();
    formData.append("file", file);

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

  async function handleDeleteDocument(docId: number | string) {
    if (!confirm("آیا از حذف این سند مطمئن هستید؟")) return;

    try {
      await deleteDocument(workspaceId, docId);
      await loadDocuments(false);
    } catch (error) {
      handleBackendError(error, "خطا در حذف سند");
    }
  }

  async function handleSendMessage(e: FormEvent) {
    e.preventDefault();
    if (!input.trim() || chatLoading || !activeDocument) return;

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

    try {
      await sendMessageStream(
        userText,
        workspaceId,
        activeChatId ?? undefined,
        null,
        16,
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
        [Number(activeDocument.id)],
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

  const filteredDocuments = useMemo(() => {
    return documents.filter((doc) => {
      const matchesSearch = doc.name
        .toLowerCase()
        .includes(search.toLowerCase());
      const matchesStatus = status === "all" ? true : (doc as any).status === status;
      return matchesSearch && matchesStatus;
    });
  }, [documents, search, status]);

  if (checkingAccess || (hasAccess && loading && documents.length === 0)) {
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
            شما به این فضای کاری یا قابلیت کتابخانه دسترسی ندارید.
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

      {/* پاپ‌آپ چت با مقاله با استایل دارک و حرفه‌ای */}
      {isChatOpen && activeDocument && (
        <div className={styles.modalOverlay}>
          <div className={styles.chatModalContainer}>
            <div className={styles.chatModalHeader}>
              <div className={styles.chatModalTitleInfo}>
                <div className={styles.modalDocIconBox}>📄</div>
                <div>
                  <h3>گفتگو با مقاله</h3>
                  <p title={activeDocument.name}>{activeDocument.name}</p>
                </div>
              </div>
              <button
                className={styles.closeModalBtn}
                onClick={handleCloseChatModal}
                title="بستن"
              >
                ✕
              </button>
            </div>

            <div className={styles.modalMessagesArea}>
              {messages.length === 0 ? (
                <div className={styles.emptyMessages}>
                  <span className={styles.emptyMsgIcon}>💡</span>
                  <p>سوال خود را درباره این مقاله بپرسید...</p>
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
                                در حال پردازش و تحلیل...
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

            <form onSubmit={handleSendMessage} className={styles.modalInputForm}>
              <input
                type="text"
                placeholder="سوال خود را درباره این مقاله بنویسید..."
                value={input}
                onChange={(e) => setInput(e.target.value)}
                disabled={chatLoading}
              />
              <button type="submit" disabled={chatLoading || !input.trim()}>
                {chatLoading ? "..." : "ارسال"}
              </button>
            </form>
          </div>
        </div>
      )}

      <header className={styles.header}>
        <div style={{ display: "flex", alignItems: "center", gap: "20px" }}>
          <div>
            <h1>📚 Paper Library</h1>
            <p>مدیریت مقالات، جزوات و فایل‌های آموزشی</p>
          </div>

          {/* دراپ‌دان هوشمند پلن‌ها و فیبرها (در کنار هدر مشابه صفحه چت) */}
          {featureOptions.length > 0 && (
            <div style={{ display: "flex", alignItems: "center", gap: "6px" }}>
              <span style={{ fontSize: "13px", color: "#94a3b8" }}>پلن و فیچر:</span>
              <select
                value={
                  featureOptions.find(
                    (opt) => opt.workspaceId === workspaceId && opt.path === "library"
                  )?.uniqueKey || ""
                }
                onChange={(e) => {
                  const selectedUniqueKey = e.target.value;
                  const matchedOption = featureOptions.find((opt) => opt.uniqueKey === selectedUniqueKey);
                  if (matchedOption) {
                    router.push(`/workspace/${matchedOption.workspaceId}/${matchedOption.path}`);
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
                <option value="" disabled style={{ background: "#1e293b", color: "#94a3b8" }}>
                  انتخاب پلن و فیچر...
                </option>
                {featureOptions.map((opt) => (
                  <option key={opt.uniqueKey} value={opt.uniqueKey} style={{ background: "#1e293b", color: "#fff" }}>
                    {opt.label}
                  </option>
                ))}
              </select>
            </div>
          )}
        </div>

        <label className={styles.uploadBtn}>
          <span className={styles.plusIcon}>+</span>
          <span>Upload Paper</span>
          <input
            type="file"
            hidden
            accept=".pdf,.doc,.docx,.txt"
            onChange={handleUpload}
            disabled={uploading}
          />
        </label>
      </header>

      <section className={styles.toolbar}>
        <SearchBar value={search} onChange={setSearch} />
        <FilterBar
          status={status}
          sort="newest"
          onStatus={setStatus}
          onSort={() => {}}
        />
      </section>

      <section className={styles.info}>
        <span>Total Documents</span>
        <strong>{filteredDocuments.length}</strong>
      </section>

      <section className={styles.grid}>
        {loading ? (
          Array.from({ length: 6 }).map((_, index) => (
            <div key={index} className={styles.skeleton} />
          ))
        ) : filteredDocuments.length === 0 ? (
          <div className={styles.empty}>
            <h3>No Documents</h3>
            <p>اولین مقاله خود را آپلود کنید.</p>
          </div>
        ) : (
          filteredDocuments.map((document) => (
            <DocumentCard
              key={document.id}
              document={document}
              onDelete={() => handleDeleteDocument(document.id)}
              onChat={() => handleOpenChatModal(document)}
            />
          ))
        )}
      </section>
    </div>
  );
}