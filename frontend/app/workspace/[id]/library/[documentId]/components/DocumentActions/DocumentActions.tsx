"use client";

import { useState } from "react";

import styles from "./DocumentActions.module.css";
import { DocumentItem } from "../../../components/types/types";


interface Props {
  document: DocumentItem;
}

export default function DocumentActions({ document }: Props) {
  const [loading, setLoading] = useState<string | null>(null);

  function action(name: string) {
    setLoading(name);

    setTimeout(() => {
      setLoading(null);
    }, 800);
  }

  const actions = [
    {
      key: "view",
      icon: "👁️",
      title: "مشاهده فایل",
    },

    {
      key: "download",
      icon: "⬇️",
      title: "دانلود",
    },

    {
      key: "chat",
      icon: "💬",
      title: "Chat با مقاله",
    },

    {
      key: "summary",
      icon: "📝",
      title: "خلاصه سازی",
    },

    {
      key: "extract",
      icon: "✨",
      title: "استخراج نکات",
    },

    {
      key: "delete",
      icon: "🗑️",
      title: "حذف",
    },
  ];

  return (
    <section className={styles.wrapper}>
      <h2>ابزارهای مقاله</h2>

      <div className={styles.grid}>
        {actions.map((item) => (
          <button
            key={item.key}
            className={styles.action}
            onClick={() => action(item.key)}
            disabled={loading === item.key}
          >
            <span>{item.icon}</span>

            <div>
              <strong>
                {loading === item.key ? "در حال انجام..." : item.title}
              </strong>
            </div>
          </button>
        ))}
      </div>
    </section>
  );
}
