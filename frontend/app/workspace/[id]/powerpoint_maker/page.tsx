"use client";

import { useCallback, useEffect, useState } from "react";
import { useParams } from "next/navigation";

import styles from "./page.module.css";

import PresentationCard from "./PresentationCard";
import PresentationModal from "./PresentationModal";

import getToken from "@/lib/api";

const API_BASE = process.env.NEXT_PUBLIC_API_URL;

if (!API_BASE) {
  throw new Error("NEXT_PUBLIC_API_URL is not defined");
}

export default function PresentationPage() {
  const params = useParams();

  const workspaceId = Number(params.id);

  const [showModal, setShowModal] = useState(false);

  const [presentations, setPresentations] = useState<any[]>([]);

  const [loading, setLoading] = useState(true);

  const [error, setError] = useState<string | null>(null);

  const fetchPresentations = useCallback(async () => {
    if (!workspaceId) return;

    try {
      setError(null);

      const token = getToken();

      const res = await fetch(
        `${API_BASE}/workspaces/${workspaceId}/presentations`,
        {
          headers: {
            ...(token && {
              Authorization: `Bearer ${token}`,
            }),
          },
        },
      );

      if (!res.ok) {
        throw new Error("خطا در دریافت ارائه‌ها");
      }

      const data = await res.json();

      const list = data.data ?? data.presentations ?? data ?? [];

      setPresentations(Array.isArray(list) ? list : []);
    } catch (error: any) {
      console.error(error);

      setError(error.message ?? "خطا در دریافت اطلاعات");
    } finally {
      setLoading(false);
    }
  }, [workspaceId]);

  useEffect(() => {
    setLoading(true);

    fetchPresentations();
  }, [fetchPresentations]);

  async function createPresentation(data: any) {
    try {
      const token = getToken();

      const res = await fetch(
        `${API_BASE}/workspaces/${workspaceId}/presentations`,
        {
          method: "POST",

          headers: {
            ...(token && {
              Authorization: `Bearer ${token}`,
            }),

            "Content-Type": "application/json",
          },

          body: JSON.stringify(data),
        },
      );

      if (!res.ok) {
        throw new Error("ساخت ارائه ناموفق بود");
      }

      setShowModal(false);

      await fetchPresentations();
    } catch (error) {
      console.error(error);

      alert("خطا در ساخت ارائه");
    }
  }

  const completed = presentations.filter(
    (x) => x.status === "completed",
  ).length;

  const processing = presentations.filter(
    (x) => x.status === "processing",
  ).length;

  useEffect(() => {
    if (processing === 0) return;

    const timer = setInterval(() => {
      fetchPresentations();
    }, 10000);

    return () => clearInterval(timer);
  }, [processing, fetchPresentations]);

  return (
    <main className={styles.container}>
      <header className={styles.header}>
        <div>
          <h1>🎨 AI Presentation Studio</h1>

          <p>ساخت و مدیریت ارائه‌های حرفه‌ای با هوش مصنوعی</p>
        </div>

        <button className={styles.createBtn} onClick={() => setShowModal(true)}>
          ✨ ایجاد ارائه جدید
        </button>
      </header>

      <section className={styles.stats}>
        <div>
          <span>کل ارائه‌ها</span>

          <strong>{presentations.length}</strong>
        </div>

        <div>
          <span>آماده دانلود</span>

          <strong>{completed}</strong>
        </div>

        <div>
          <span>در حال پردازش</span>

          <strong>{processing}</strong>
        </div>
      </section>

      {error && <div className={styles.error}>{error}</div>}

      <section className={styles.grid}>
        {loading ? (
          <div className={styles.loading}>در حال دریافت ارائه‌ها...</div>
        ) : presentations.length === 0 ? (
          <div className={styles.empty}>
            <h3>هنوز ارائه‌ای ساخته نشده</h3>

            <p>اولین ارائه خود را با هوش مصنوعی بسازید.</p>
          </div>
        ) : (
          presentations.map((item) => (
            <PresentationCard
              key={item.id}
              presentation={{
                id: item.id,

                title: item.name?.replace(".pptx", "") ?? "ارائه بدون عنوان",

                slides: item.metadata?.slideCount ?? "-",

                status: item.status,

                statusText:
                  item.status === "processing"
                    ? "در حال تولید با هوش مصنوعی"
                    : item.status === "completed"
                      ? "آماده دانلود"
                      : "خطا در تولید",

                created: item.created_at
                  ? new Date(item.created_at).toLocaleDateString("fa-IR")
                  : "-",

                path: item.path,
              }}
            />
          ))
        )}
      </section>

      {showModal && (
        <PresentationModal
          onClose={() => setShowModal(false)}
          onCreate={createPresentation}
        />
      )}
    </main>
  );
}
