"use client";

import styles from "./DocumentHeader.module.css";

interface Props {
  document: any;

  onBack: () => void;
}

export default function DocumentHeader({ document, onBack }: Props) {
  function formatSize(size: number) {
    if (!size) return "0 KB";

    return (size / 1024).toFixed(1) + " KB";
  }

  function status() {
    switch (document.status) {
      case "completed":
        return {
          text: "تحلیل کامل شد",
          class: "success",
        };

      case "processing":
        return {
          text: "در حال پردازش",
          class: "warning",
        };

      case "failed":
        return {
          text: "خطا",
          class: "danger",
        };

      default:
        return {
          text: document.status,
          class: "default",
        };
    }
  }

  const currentStatus = status();

  return (
    <header className={styles.header}>
      <div className={styles.right}>
        <button className={styles.back} onClick={onBack}>
          ←
        </button>

        <div className={styles.icon}>📄</div>

        <div>
          <h1 className={`${styles.name}`}>{document.name}</h1>

          <div className={styles.meta}>
            <span>PDF</span>

            <span>{formatSize(document.size)}</span>

            <span>
              {new Date(document.created_at).toLocaleDateString("fa-IR")}
            </span>
          </div>
        </div>
      </div>

      <div
        className={`${styles.status}
                ${styles[currentStatus.class]}`}
      >
        <span></span>

        {currentStatus.text}
      </div>
    </header>
  );
}
