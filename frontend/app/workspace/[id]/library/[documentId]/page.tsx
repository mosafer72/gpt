"use client";

import { useEffect, useState } from "react";

import { useParams, useRouter } from "next/navigation";

import styles from "./page.module.css";

import { getDocumentOverview } from "../components/lib/api";

// Components

import DocumentStats from "./components/DocumentStats/DocumentStats";

import DocumentActions from "./components/DocumentActions/DocumentActions";

import DocumentSections from "./components/DocumentSections/DocumentSections";

import ProcessingStatus from "./components/ProcessingStatus/ProcessingStatus";

import DocumentInfo from "./components/DocumentInfo/DocumentInfo";

import DocumentPreview from "./components/DocumentPreview/DocumentPreview";

import ErrorPanel from "./components/ErrorPanel/ErrorPanel";

import AllInsights from "./components/AIInsights/AIInsights";
import DocumentHeader from "./components/DocumentHeader/DocumentHeader";
import DocumentStructure from "./components/DocumentStructure/DocumentStructure";

export default function DocumentPage() {
  const params = useParams();

  const router = useRouter();

  const workspaceId = params.id as string;

  const documentId = params.documentId as string;

  const [data, setData] = useState<any>(null);

  const [loading, setLoading] = useState(true);

  useEffect(() => {
    load();
  }, [workspaceId, documentId]);

  async function load() {
    try {
      setLoading(true);

      const result = await getDocumentOverview(workspaceId, documentId);
      console.log();
      setData(result.data);
    } catch (error) {
      console.error(error);
    } finally {
      setLoading(false);
    }
  }

  if (loading) {
    return <div className={styles.loading}>در حال تحلیل مقاله...</div>;
  }

  if (!data) {
    return <div className={styles.empty}>مقاله پیدا نشد</div>;
  }

  const document = data.document;

  return (
    <main className={styles.container}>
      <DocumentHeader document={document} onBack={() => router.back()} />
      <section className={styles.grid}>
        <div className={styles.content}>
          {/* PDF Preview */}

          <DocumentPreview document={document} />

          {/* AI Analysis */}

          <AllInsights sections={data.sections?.[0] ?? []} />

          {/* Paper Structure */}

          <DocumentSections sections={data.sections} />

          {/* Errors */}

          <ErrorPanel document={document} />
        </div>

        <DocumentInfo document={document} />

        <DocumentStructure structure={data.structure?.[0]?.sections ?? []} />
        <aside className={styles.sidebar}>
          <DocumentStats
            document={document}
            structure={data.structure?.[0]}
            sections={data.sections?.[0] ?? []}
          />

          <ProcessingStatus document={document} />

          <DocumentActions document={document} />

          <DocumentInfo document={document} />
        </aside>
      </section>
    </main>
  );
}
