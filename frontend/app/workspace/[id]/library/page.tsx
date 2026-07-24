"use client";

import { useEffect, useMemo, useState } from "react";
import { useParams } from "next/navigation";

import styles from "./page.module.css";

import DocumentCard from "./components/DocumentCard";
import SearchBar from "./components/SearchBar";
import FilterBar from "./components/FilterBar";
import { DocumentItem } from "@/types";
import { getDocuments } from "./components/lib/api";

export default function LibraryPage() {
  const params = useParams();

  const workspaceId = params.id as string;

  const [documents, setDocuments] = useState<DocumentItem[]>([]);

  const [loading, setLoading] = useState(true);

  const [search, setSearch] = useState("");

  const [status, setStatus] = useState("all");

  useEffect(() => {
    loadDocuments();
  }, [workspaceId]);

  async function loadDocuments() {
    try {
      setLoading(true);

      const docs = await getDocuments(workspaceId);

      console.log("DOCUMENTS RESPONSE:", docs);
      console.log("IS ARRAY:", Array.isArray(docs));

      setDocuments(Array.isArray(docs) ? docs : []);
    } catch (error) {
      console.error(error);
    } finally {
      setLoading(false);
    }
  }

  const filteredDocuments = useMemo(() => {
    return documents.filter((doc) => {
      const matchesSearch = doc.name
        .toLowerCase()
        .includes(search.toLowerCase());

      const matchesStatus = status === "all" ? true : doc.status === status;

      return matchesSearch && matchesStatus;
    });
  }, [documents, search, status]);

  return (
    <div className={styles.container}>
      <header className={styles.header}>
        <div>
          <h1>📚 Paper Library</h1>

          <p>مدیریت مقالات، جزوات و فایل‌های آموزشی</p>
        </div>

        <button className={styles.upload}>Upload Paper</button>
      </header>

      <section className={styles.toolbar}>
        <SearchBar value={search} onChange={setSearch} />

        <FilterBar value={status} onChange={setStatus} />
      </section>

      <section className={styles.info}>
        <span>Total Documents</span>

        <strong>{filteredDocuments.length}</strong>
      </section>

      <section className={styles.grid}>
        {loading ? (
          Array.from({ length: 6 }).map((_, index) => (
            <div key={index} className={styles.skeleton} />
          ))
        ) : filteredDocuments.length === 0 ? (
          <div className={styles.empty}>
            <h3>No Documents</h3>

            <p>اولین مقاله خود را آپلود کنید.</p>
          </div>
        ) : (
          filteredDocuments.map((document) => (
            <DocumentCard
              key={document.id}
              document={document}
              onRefresh={loadDocuments}
            />
          ))
        )}
      </section>
    </div>
  );
}
