"use client";

import styles from "./DocumentStructure.module.css";

interface Props {
  structure: any[];
}

export default function DocumentStructure({ structure = [] }: Props) {
  // چون مستقیم آرایه sections را می‌گیریم
  const sections = structure;

  if (sections.length === 0) {
    return (
      <section className={styles.card}>
        <h2>📚 ساختار مقاله</h2>

        <div className={styles.empty}>ساختاری برای مقاله ثبت نشده است</div>
      </section>
    );
  }

  return (
    <section className={styles.card}>
      <div className={styles.header}>
        <div>
          <h2>📚 ساختار مقاله</h2>
          <p>تقسیم‌بندی هوشمند بخش‌های مقاله</p>
        </div>

        <div className={styles.total}>{sections.length} بخش</div>
      </div>

      <div className={styles.timeline}>
        {[...sections]
          .sort((a, b) => a.order - b.order)
          .map((section, index) => (
            <div
              key={`${section.name}-${section.order}-${index}`}
              className={styles.section}
            >
              <div className={styles.number}>
                {String(section.order).padStart(2, "0")}
              </div>

              <div className={styles.content}>
                <div className={styles.titleRow}>
                  <h3>{section.title}</h3>

                  {section.importance === "high" && (
                    <span className={styles.important}>⭐ مهم</span>
                  )}
                </div>

                <p>{section.summary}</p>

                <div className={styles.meta}>
                  <span>📝 {section.word_count} کلمه</span>

                  <span>#{section.name}</span>
                </div>
              </div>
            </div>
          ))}
      </div>
    </section>
  );
}
