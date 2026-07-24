"use client";

import { useEffect, useState } from "react";
import { useParams } from "next/navigation";

import { getWorkspaceModels } from "@/lib/api";

import styles from "./page.module.css";

export default function ModelsPage() {
  const params = useParams();

  const id = Number(params.id);

  const [loading, setLoading] = useState(true);

  const [data, setData] = useState<any>(null);

  async function load() {
    try {
      const response = await getWorkspaceModels(id);

      setData(response);
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    if (id) {
      load();
    }
  }, [id]);

  if (loading) {
    return (
      <div className={styles.loading}>
        در حال بارگذاری...
      </div>
    );
  }

  if (!data) {
    return null;
  }

  return (
    <div className={styles.container}>
      <div className={styles.header}>
        <div>
          <h1>🤖 مدل‌های فعال</h1>

          <p>
            Workspace:
            {" "}
            {data.workspace.name}
          </p>
        </div>

        <div className={styles.badge}>
          {data.models.length} مدل
        </div>
      </div>

      <div className={styles.grid}>
        {data.models.map((model: any) => (
          <div
            key={model.id}
            className={styles.card}
          >
            <div className={styles.top}>
              <div className={styles.icon}>
                🤖
              </div>

              <div>
                <h3>{model.name}</h3>

                <span>
                  {model.provider}
                </span>
              </div>
            </div>

            <div className={styles.info}>
              <div>
                <label>Model</label>

                <strong>
                  {model.key}
                </strong>
              </div>

              <div>
                <label>Status</label>

                <strong className={styles.active}>
                  فعال
                </strong>
              </div>
            </div>

            <div className={styles.footer}>
              <button>
                استفاده در گفتگو
              </button>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}