"use client";

import { useEffect, useState } from "react";

import { useParams,useRouter } from "next/navigation";


import styles from "./page.module.css";

import type { DocumentItem } from "../components/types/types";

import { getDocument } from "../components/lib/api";

import DocumentPreview from "./components/DocumentPreview/DocumentPreview";

import DocumentAIInsights from "./components/DocumentAIInsights/DocumentAIInsights";

import DocumentActions from "./components/DocumentActions/DocumentActions";

import DocumentInfo from "./components/DocumentInfo/DocumentInfo";

import DocumentStats from "./components/DocumentStats/DocumentStats";

import ProcessingStatus from "./components/ProcessingStatus/ProcessingStatus";

import ErrorPanel from "./components/ErrorPanel/ErrorPanel";

export default function DocumentPage() {
  const params = useParams();
  const router=useRouter()

  const workspaceId = params.id as string;

  const documentId = params.documentId as string;

  const [document, setDocument] = useState<DocumentItem | null>(null);

  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadDocument();
  }, [workspaceId, documentId]);

  async function loadDocument() {
    try {
      setLoading(true);

      const response = await getDocument(workspaceId, documentId);

      setDocument(response.document);
    } catch (error) {
      console.error(error);
    } finally {
      setLoading(false);
    }
  }

  if (loading) {
    return <div className={styles.loading}>در حال بارگذاری مقاله...</div>;
  }

  if (!document) {
    return <div className={styles.empty}>مقاله پیدا نشد</div>;
  }

  return (
    <main className={styles.container}>
      <header className={styles.header}>
        <div>
          <h1>📄 {document.name}</h1>
          <p>فضای تحقیق و تحلیل مقاله</p>
        </div>
        <div>
          <button onClick={() => router.back()} className={styles.backButton}>
            ←
          </button>
        </div>
      </header>

      <section className={styles.layout}>
        <div className={styles.main}>
          <DocumentPreview document={document} />

          <DocumentAIInsights document={document} />

          <ErrorPanel document={document} />
        </div>

        <aside className={styles.sidebar}>
          <DocumentStats document={document} />

          <ProcessingStatus document={document} />

          <DocumentActions document={document} />

          <DocumentInfo document={document} />
        </aside>
      </section>
    </main>
  );
}
