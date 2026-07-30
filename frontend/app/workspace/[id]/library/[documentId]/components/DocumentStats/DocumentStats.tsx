"use client";

import { DocumentItem } from "../../../components/types/types";
import styles from "./DocumentStats.module.css";

interface Props {
  document: DocumentItem;
  structure?: any;
  sections?: any;
}

export default function DocumentStats({ document, structure, sections }: Props) {
  // اگر size رشته باشد، تبدیل آن به عدد یا مدیریت مقدار خالی
  const sizeValue = typeof document.size === 'string' ? Number(document.size) : (document.size || 0);
  const sizeMB = (sizeValue / 1024 / 1024).toFixed(2);

  const statusMap: Record<string, { label: string; icon: string; className: string }> = {
    uploaded: {
      label: "آپلود شده",
      icon: "📤",
      className: "uploaded",
    },

    processing: {
      label: "در حال پردازش",
      icon: "⚙️",
      className: "processing",
    },

    completed: {
      label: "تکمیل شده",
      icon: "✅",
      className: "completed",
    },

    failed: {
      label: "ناموفق",
      icon: "❌",
      className: "failed",
    },
  };

  const current = statusMap[document.status] ?? statusMap.uploaded;

  const stats = [
    {
      icon: "📄",
      title: "صفحات",
      value: document.metadata?.pages ?? "-",
    },

    {
      icon: "🧩",
      title: "تکه‌ها",
      value: document.metadata?.chunks ?? "-",
    },

    {
      icon: "🔤",
      title: "کلمات",
      value: document.metadata?.words ?? "-",
    },

    {
      icon: "💾",
      title: "حجم",
      value: `${sizeMB} MB`,
    },
  ];

  return (
    <section className={styles.wrapper}>
      <div className={styles.title}>
        <h2>📊 وضعیت سند</h2>

        <span className={`${styles.status} ${styles[current.className]}`}>
          {current.icon}

          {current.label}
        </span>
      </div>

      <div className={styles.grid}>
        {stats.map((item) => (
          <div key={item.title} className={styles.card}>
            <div className={styles.icon}>{item.icon}</div>

            <div>
              <span>{item.title}</span>

              <strong>{item.value}</strong>
            </div>
          </div>
        ))}
      </div>
    </section>
  );
}