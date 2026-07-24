"use client";

import { useState } from "react";

import styles from "./DocumentAIInsights.module.css";
import { DocumentItem } from "../../../components/types/types";


interface Props {
  document: DocumentItem;
}

export default function DocumentAIInsights({ document }: Props) {
  const [loading, setLoading] = useState(false);

  const insights = [
    {
      icon: "📝",
      title: "خلاصه هوشمند",
      desc: "تولید خلاصه علمی و استخراج مفهوم اصلی مقاله",
    },

    {
      icon: "🏷️",
      title: "عنوان پیشنهادی",
      desc: "پیشنهاد عنوان بهتر بر اساس محتوای مقاله",
    },

    {
      icon: "🔑",
      title: "کلیدواژه‌ها",
      desc: "استخراج Keywords و موضوعات اصلی",
    },

    {
      icon: "❓",
      title: "سوالات تحقیق",
      desc: "ساخت سوالات پژوهشی مرتبط",
    },

    {
      icon: "💡",
      title: "نکات مهم",
      desc: "استخراج نکات کلیدی مقاله",
    },

    {
      icon: "📚",
      title: "ساختار مقاله",
      desc: "تحلیل بخش‌ها و ساختار علمی",
    },
  ];

  function runAI() {
    setLoading(true);

    setTimeout(() => {
      setLoading(false);
    }, 1000);
  }

  return (
    <section className={styles.wrapper}>
      <div className={styles.header}>
        <div>
          <h2>🤖 دستیار هوشمند مقاله</h2>

          <p>تحلیل مقاله با هوش مصنوعی</p>
        </div>

        <button onClick={runAI} disabled={loading}>
          {loading ? "در حال تحلیل..." : "شروع تحلیل"}
        </button>
      </div>

      <div className={styles.grid}>
        {insights.map((item) => (
          <div key={item.title} className={styles.card}>
            <div className={styles.icon}>{item.icon}</div>

            <div>
              <h3>{item.title}</h3>

              <p>{item.desc}</p>
            </div>
          </div>
        ))}
      </div>

      <div className={styles.future}>
        <span>🚀</span>

        <div>
          <strong>آماده اتصال به AI Pipeline</strong>

          <p>
            بعد از پردازش Document، خروجی‌های هوشمند اینجا نمایش داده می‌شوند.
          </p>
        </div>
      </div>
    </section>
  );
}
