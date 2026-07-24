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
  status: "processing" | "completed" | "failed";
  size?: string;
  updated_at?: string;
}

interface Props {
  document: DocumentItem;
  onOpen?: (id: string | number) => void;
  onDelete?: (id: string | number) => void;
}

export default function DocumentCard({ document, onOpen, onDelete }: Props) {
  const router = useRouter();
  const pathname = usePathname();

  const statusLabel = {
    completed: "آماده",
    processing: "درحال پردازش",
    failed: "خطا",
  };

  return (
    <article className={styles.card}>
      <div className={styles.header}>
        <div className={styles.icon}>📄</div>

        <div className={`${styles.status} ${styles[document.status]}`}>
          {statusLabel[document.status]}
        </div>
      </div>

      <h3 className={styles.title}>{document.title || document.name}</h3>

      {document.authors && document.authors.length > 0 && (
        <p className={styles.authors}>{document.authors.join(", ")}</p>
      )}

      <div className={styles.info}>
        <span>📅 {document.year || "-"}</span>
        <span>📑 {document.pages || 0} صفحه</span>
      </div>

      <div className={styles.info}>
        <span>🧩 {document.chunks || 0} Chunk</span>
        <span>💾 {document.size || "-"}</span>
      </div>

      {document.updated_at && (
        <div className={styles.updated}>
          آخرین بروزرسانی
          <br />
          {formatPersianDate(document.updated_at)}
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
          className={styles.danger}
          onClick={() => onDelete?.(document.id)}
        >
          حذف
        </button>
      </div>
    </article>
  );
}