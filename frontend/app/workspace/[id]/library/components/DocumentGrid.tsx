"use client";

import DocumentCard from "./DocumentCard";
import styles from "./DocumentGrid.module.css";

export type DocumentItem = {
  id: number;
  title: string;
  authors: string[];
  year?: string;
  pages?: number;
  size?: string;
  status: "processing" | "completed" | "failed";
  chunks?: number;
  uploadedAt: string;
};

type Props = {
  documents: DocumentItem[];
  onOpen?: (id: number) => void;
  onRename?: (id: number) => void;
  onDelete?: (id: number) => void;
};

export default function DocumentGrid({
  documents,
  onOpen,
  onRename,
  onDelete,
}: Props) {
  return (
    <section className={styles.grid}>
      {documents.map((doc) => (
        <DocumentCard
          key={doc.id}
          {...doc}
          onOpen={() => onOpen?.(doc.id)}
          onRename={() => onRename?.(doc.id)}
          onDelete={() => onDelete?.(doc.id)}
        />
      ))}
    </section>
  );
}