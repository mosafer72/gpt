"use client";

import styles from "./DocumentCard.module.css";
import { useRouter, usePathname } from "next/navigation";
import { formatPersianDate } from "./lib/api";

export interface DocumentItem {
  id: number | string;
  name: string;
  title?: string;
  authors?: string[];
  year?: string;
  pages?: number;
  chunks?: number;
  status: "uploaded" | "processing" | "completed" | "failed";
  size?: number | string;
  updated_at?: string;
  created_at?: string;
}

interface Props {
  document: DocumentItem;
  onOpen?: (id: string | number) => void;
  onDelete?: (id: string | number) => void;
  onChat?: (document: DocumentItem) => void;
}

function formatFileSize(bytes?: number | string): string {
  if (!bytes) return "-";
  const numBytes = typeof bytes === "string" ? Number(bytes) : bytes;
  if (isNaN(numBytes)) return String(bytes);

  if (numBytes < 1024 * 1024) {
    return `${(numBytes / 1024).toFixed(1)} KB`;
  }
  return `${(numBytes / (1024 * 1024)).toFixed(1)} MB`;
}

export default function DocumentCard({ document, onOpen, onDelete, onChat }: Props) {
  const router = useRouter();
  const pathname = usePathname();

  const statusLabel: Record<string, string> = {
    completed: "آماده",
    processing: "درحال پردازش",
    uploaded: "آپلود شده",
    failed: "خطا",
  };

  const isCompleted = document.status === "completed";
  const currentStatusText = statusLabel[document.status] || "نامشخص";

  return (
    <article className={styles.card}>
      <div className={styles.header}>
        <div className={styles.icon}>📄</div>

        <div className={`${styles.status} ${styles[document.status] || styles.uploaded}`}>
          {currentStatusText}
        </div>
      </div>

      <h3 className={styles.title} title={document.title || document.name}>
        {document.title || document.name}
      </h3>

      {document.authors && document.authors.length > 0 && (
        <p className={styles.authors}>{document.authors.join(", ")}</p>
      )}

      <div className={styles.info}>
        <span>📅 {document.year || "-"}</span>
        <span>📑 {document.pages ? `${document.pages} صفحه` : "-"}</span>
      </div>

      <div className={styles.info}>
        <span>🧩 {document.chunks ? `${document.chunks} Chunk` : "سایز فایل:"}</span>
        <span>💾 {formatFileSize(document.size)}</span>
      </div>

      {(document.updated_at || document.created_at) && (
        <div className={styles.updated}>
          آخرین بروزرسانی:{" "}
          {formatPersianDate(document.updated_at || document.created_at!)}
        </div>
      )}

      <div className={styles.actions}>
        <button
          type="button"
          className={styles.primary}
          onClick={() => {
            if (onOpen) {
              onOpen(document.id);
            } else {
              router.push(`${pathname}/${document.id}`);
            }
          }}
        >
          مشاهده
        </button>

        <button
          type="button"
          className={styles.chatBtn}
          disabled={!isCompleted}
          onClick={() => onChat?.(document)}
          title={isCompleted ? "چت با این مقاله" : `وضعیت: ${currentStatusText}`}
        >
          {isCompleted ? "💬 چت" : `⏳ ${currentStatusText}`}
        </button>

        <button
          type="button"
          className={styles.danger}
          onClick={() => onDelete?.(document.id)}
        >
          حذف
        </button>
      </div>
    </article>
  );
}