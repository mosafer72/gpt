"use client";

import styles from "./DocumentPanel.module.css";

import { DocumentItem } from "@/types/index";

interface Props {
  documents: DocumentItem[];

  selected: number[];

  onChange: (ids: number[]) => void;
}

export default function DocumentPanel({
  documents,

  selected,

  onChange,
}: Props) {
  function toggle(id: number) {
    if (selected.includes(id)) {
      onChange(selected.filter((x) => x !== id));
    } else {
      onChange([...selected, id]);
    }
  }

  return (
    <div className={styles.panel}>
      <div className={styles.header}>
        <h3>📚 Documents</h3>

        <span>{selected.length} انتخاب</span>
      </div>

      <div className={styles.list}>
        {documents.map((doc) => (
          <div
            key={doc.id}
            className={`${styles.item}
${selected.includes(doc.id) ? styles.active : ""}`}
            onClick={() => toggle(doc.id)}
          >
            <div className={styles.icon}>📄</div>

            <div>
              <strong>{doc.name}</strong>

              <p>
                {doc.pages} صفحه •{doc.size}
              </p>
            </div>

            <div className={styles.check}>
              {selected.includes(doc.id) ? "✓" : ""}
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}
