"use client";

import DocumentCard, { DocumentItem } from "./DocumentCard";
import styles from "./DocumentGrid.module.css";

// استفاده مجدد یا هماهنگ‌سازی تایپ DocumentItem با کامپوننت DocumentCard
export type { DocumentItem };

type Props = {
  documents: DocumentItem[];
  onOpen?: (id: string | number) => void;
  onRename?: (id: string | number) => void;
  onDelete?: (id: string | number) => void;
  onChat?: (document: DocumentItem) => void;
};

export default function DocumentGrid({
  documents,
  onOpen,
  onRename,
  onDelete,
  onChat,
}: Props) {
  return (
    <section className={styles.grid}>
      {documents.map((doc) => (
        <DocumentCard
          key={doc.id}
          document={doc}
          onOpen={onOpen}
          onDelete={onDelete}
          onChat={onChat}
        />
      ))}
    </section>
  );
}