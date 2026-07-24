"use client";

import { DocumentItem } from "../../../components/types/types";
import styles from "./ErrorPanel.module.css";


interface Props {
  document: DocumentItem;
}

export default function ErrorPanel({ document }: Props) {
  if (document.status !== "failed" || !document.error) {
    return null;
  }

  return (
    <section className={styles.wrapper}>
      <div className={styles.header}>
        <span>❌</span>

        <h2>خطای پردازش</h2>
      </div>

      <div className={styles.message}>{document.error}</div>

      <button>🔄 تلاش مجدد</button>
    </section>
  );
}
