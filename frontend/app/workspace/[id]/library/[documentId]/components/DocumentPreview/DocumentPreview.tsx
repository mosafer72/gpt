"use client";

import { DocumentItem } from "../../../components/types/types";
import styles from "./DocumentPreview.module.css";


interface Props {
  document: DocumentItem;
}

export default function DocumentPreview({ document }: Props) {
  const fileUrl = `${process.env.NEXT_PUBLIC_File_URL}/${document.path}`;

  if (document.status === "processing" || document.status === "uploaded") {
    return (
      <section className={styles.wrapper}>
        <div className={styles.loading}>
          <div className={styles.spinner}></div>

          <h3>در حال پردازش مقاله</h3>

          <p>استخراج متن، ساختاردهی و آماده سازی هوش مصنوعی</p>
        </div>
      </section>
    );
  }

  if (document.status === "failed") {
    return (
      <section className={styles.wrapper}>
        <div className={styles.error}>
          <div className={styles.errorIcon}>⚠️</div>

          <h3>پردازش فایل ناموفق بود</h3>

          <p>{document.error}</p>
        </div>
      </section>
    );
  }

  return (
    <section className={styles.wrapper}>
      <div className={styles.header}>
        <h2>پیش نمایش مقاله</h2>

        <a href={fileUrl} target="_blank" className={styles.open}>
          باز کردن کامل
        </a>
      </div>

      <div className={styles.viewer}>
        <iframe src={fileUrl} title={document.name} />
      </div>
    </section>
  );
}
