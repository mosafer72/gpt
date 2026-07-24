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
import ReactMarkdown from "react-markdown";
import remarkGfm from "remark-gfm";
import axios from "axios";
import styles from "./page.module.css";
import {
  getDocuments,
  createChat,
  sendMessageStream,
  deleteDocument,
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

  // Ref اختصاصی برای جلوگیری از Re-render رگباری React هنگام Stream
  const currentAiMessageRef = useRef<string>("");

  const scrollToBottom = () => {
    messagesEndRef.current?.scrollIntoView({ behavior: "smooth" });
  };

  useEffect(() => {
    scrollToBottom();
  }, [messages, chatLoading]);

  // مدیریت خطاهای بک‌اند
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

  // دریافت لیست اسناد
  const loadDocuments = useCallback(
    async (showGlobalLoading = false) => {
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

  // بارگذاری اولیه
  useEffect(() => {
    if (workspaceId) {
      loadDocuments(true);
    }
  }, [workspaceId, loadDocuments]);

  // Polling بهینه‌شده اسناد
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

  // انتخاب یا عدم انتخاب سند
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
          model_id: 3,
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

  // آپلود فایل
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

  // حذف سند
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

  /*
  |--------------------------------------------------------------------------
  | ارسال پیام چت و پردازش ایمن استریم (پشتیبانی کامل از توکن‌های متصل)
  |--------------------------------------------------------------------------
  */
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

    try {
      await sendMessageStream(
        userText,
        workspaceId,
        activeChatId ?? undefined,
        null,
        3,
        (chunk) => {
          if (!chunk) return;

          // تجزیه خط به خط برای جلوگیری از خرابی JSONهای چندتایی در یک Chunk
          const lines = String(chunk).split("\n");

          for (let line of lines) {
            line = line.trim();
            if (!line) continue;

            // پاکسازی SSE data: prefix
            if (line.startsWith("data:")) {
              line = line.replace(/^data:\s*/, "").trim();
            }

            let extractedText = line;

            // تلاش برای استخراج محتوا از JSON
            try {
              const json = JSON.parse(line);
              if (typeof json.content === "string") {
                extractedText = json.content;
              } else if (typeof json.delta === "string") {
                extractedText = json.delta;
              } else if (typeof json.text === "string") {
                extractedText = json.text;
              }
            } catch {
              // اگر JSON نبود، خود متن خام استفاده می‌شود
            }

            currentAiMessageRef.current += extractedText;
          }

          // زمان‌بندی رندر در فریم بعدی مرورگر (۶۰fps)
          if (!renderScheduled) {
            renderScheduled = true;
            animFrameId = requestAnimationFrame(updateMessagesUI);
          }
        },
        controller.signal,
        null,
        selected.map((doc) => doc.id),
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

  if (loading) {
    return (
      <div className={styles.loadingContainer}>
        <div className={styles.spinner}></div>
        <p>در حال بارگذاری اسناد...</p>
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
        <div className={styles.headerLeft}>
          <button className={styles.backBtn} onClick={() => router.back()}>
            ← بازگشت
          </button>
          <div>
            <h1>📚 دستیار هوشمند اسناد</h1>
            <p>
              اسناد مورد نظر خود را انتخاب کرده و بلافاصله گفتگو را شروع کنید
            </p>
          </div>
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
        {/* پنل راست: لیست اسناد */}
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

        {/* پنل چپ: چت و نمایش مارک‌داون */}
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
                              <span className={styles.typingIndicator}>
                                در حال تایپ...
                              </span>
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