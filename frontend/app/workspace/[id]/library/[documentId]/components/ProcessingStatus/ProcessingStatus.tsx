"use client";

import { DocumentItem } from "../../../components/DocumentGrid";
import styles from "./ProcessingStatus.module.css";


interface Props {
  document: DocumentItem;
}

export default function ProcessingStatus({ document }: Props) {
  const steps = [
    {
      title: "آپلود فایل",
      done: true,
    },

    {
      title: "استخراج متن",
      done: ["processing", "completed"].includes(document.status),
    },

    {
      title: "تقسیم‌بندی متن",
      done: document.status === "completed",
    },

    {
      title: "ساخت Embedding",
      done: document.status === "completed",
    },
  ];

  return (
    <section className={styles.wrapper}>
      <h2>⚙️ پردازش سند</h2>

      <div className={styles.timeline}>
        {steps.map((step, index) => (
          <div key={step.title} className={styles.item}>
            <div className={step.done ? styles.done : styles.circle}>
              {step.done ? "✓" : index + 1}
            </div>

            <div>
              <strong>{step.title}</strong>

              <span>{step.done ? "انجام شد" : "در انتظار"}</span>
            </div>
          </div>
        ))}
      </div>
    </section>
  );
}
