"use client";

import { DocumentItem } from "../../../components/types/types";
import styles from "./DocumentInfo.module.css";


interface Props {
  document: DocumentItem;
}

export default function DocumentInfo({ document }: Props) {
  const createdAt = new Date(document.created_at).toLocaleDateString("fa-IR");

  const updatedAt = new Date(document.updated_at).toLocaleDateString("fa-IR");

  const info = [
    {
      icon: "📄",
      title: "نام فایل",
      value: document.name,
    },

    {
      icon: "📌",
      title: "نوع فایل",
      value: document.mime_type,
    },

    {
      icon: "💾",
      title: "حجم",
      value: `${(document.size / 1024 / 1024).toFixed(2)} MB`,
    },

    {
      icon: "📅",
      title: "تاریخ ایجاد",
      value: createdAt,
    },

    {
      icon: "🔄",
      title: "آخرین بروزرسانی",
      value: updatedAt,
    },

    {
      icon: "🆔",
      title: "شناسه",
      value: `#${document.id}`,
    },
  ];

  return (
    <section className={styles.wrapper}>
      <h2>اطلاعات فایل</h2>

      <div className={styles.grid}>
        {info.map((item) => (
          <div key={item.title} className={styles.item}>
            <div className={styles.icon}>{item.icon}</div>

            <div className={styles.content}>
              <span>{item.title}</span>

              <strong>{item.value}</strong>
            </div>
          </div>
        ))}
      </div>

      {document.error && (
        <div className={styles.error}>
          <strong>خطای پردازش:</strong>

          <p>{document.error}</p>
        </div>
      )}
    </section>
  );
}
