"use client";

import styles from "./PresentationCard.module.css";

export default function PresentationCard({ presentation }: any) {
  const isCompleted = presentation.status === "completed";

  const isProcessing = presentation.status === "processing";

  const downloadPath = presentation.path
    ? `/storage/${presentation.path}`
    : null;

  return (
    <div className={styles.card}>
      <div className={styles.icon}>📊</div>

      <h3>{presentation.title ?? presentation.name ?? "ارائه بدون عنوان"}</h3>

      <p className={styles.slides}>
        {presentation.slides ?? presentation.slides_count ?? 0} اسلاید
      </p>

      <div className={styles.meta}>
        <span
          className={
            isCompleted
              ? styles.completed
              : isProcessing
                ? styles.processing
                : styles.failed
          }
        >
          {isCompleted
            ? "آماده دانلود"
            : isProcessing
              ? "در حال تولید"
              : "خطا در تولید"}
        </span>

        <span>
          {presentation.created ??
            (presentation.created_at
              ? new Date(presentation.created_at).toLocaleDateString("fa-IR")
              : "-")}
        </span>
      </div>

      <div className={styles.actions}>
        {isCompleted && downloadPath ? (
          <a href={downloadPath} download className={styles.download}>
            ⬇️ دانلود پاورپوینت
          </a>
        ) : (
          <button className={styles.disabled} disabled>
            {isProcessing ? "⏳ در حال آماده‌سازی..." : "دانلود موجود نیست"}
          </button>
        )}
      </div>
    </div>
  );
}
