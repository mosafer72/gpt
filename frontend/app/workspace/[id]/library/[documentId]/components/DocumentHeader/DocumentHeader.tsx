"use client";


import { DocumentItem } from "../../../components/types/types";
import styles from "./DocumentHeader.module.css";

interface Props {
  document: DocumentItem;
}

export default function DocumentHeader({ document }: Props) {
  return (
    <section className={styles.wrapper}>
      <div className={styles.icon}>📄</div>

      <div className={styles.content}>
        <h1>{document.name}</h1>

        <div className={styles.meta}>
          <span>{document.mime_type}</span>

          <span>{(document.size / 1024 / 1024).toFixed(2)} MB</span>
        </div>
      </div>

      <div className={styles.status}>
        {document.status === "completed" && (
          <span className={styles.completed}>Completed</span>
        )}

        {document.status === "processing" && (
          <span className={styles.processing}>Processing</span>
        )}

        {document.status === "failed" && (
          <span className={styles.failed}>Failed</span>
        )}

        {document.status === "uploaded" && (
          <span className={styles.uploaded}>Uploaded</span>
        )}
      </div>
    </section>
  );
}
