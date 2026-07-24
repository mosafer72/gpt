"use client";

import styles from "./PaperAttachment.module.css";

export default function PaperAttachment({
  paperId,
}: {
  paperId: string;
}) {

  return (
    <div className={styles.paper}>
      <a className={styles.icon}
        href={`/api/papers/${paperId}/download`}
        target="_blank"
      >
                📄
      </a>
    </div>
  );
}