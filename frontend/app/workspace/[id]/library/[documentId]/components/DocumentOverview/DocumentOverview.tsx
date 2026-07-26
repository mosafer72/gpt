"use client";

import styles from "./DocumentOverview.module.css";

interface Props {
  document: any;
}

export default function DocumentOverview({ document }: Props) {
  function formatDate(date: string) {
    return new Intl.DateTimeFormat("fa-IR", {
      year: "numeric",
      month: "long",
      day: "numeric",
    }).format(new Date(date));
  }

  const items = [
    {
      icon: "📄",
      title: "نوع فایل",
      value: document.mime_type?.replace("application/", "") || "PDF",
    },

    {
      icon: "📦",
      title: "حجم",
      value: (document.size / 1024 / 1024).toFixed(2) + " MB",
    },

    {
      icon: "📅",
      title: "تاریخ ایجاد",
      value: formatDate(document.created_at),
    },

    {
      icon: "🤖",
      title: "وضعیت تحلیل",
      value:
        document.status === "completed" ? "تحلیل کامل شده" : "در حال پردازش",
    },
  ];

  return (
    <section className={styles.card}>
      <div className={styles.header}>
        <div>
          <h2>نمای کلی مقاله</h2>

          <p>اطلاعات پایه و وضعیت پردازش سند</p>
        </div>

        <div className={styles.badge}>AI Document</div>
      </div>

      <div className={styles.grid}>
        {items.map((item) => (
          <div key={item.title} className={styles.item}>
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
