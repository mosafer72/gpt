"use client";

import styles from "./DocumentAIInsights.module.css";

interface Props {
  sections: any[];
}

export default function DocumentAIInsights({ sections }: Props) {
  if (!sections || sections.length === 0) {
    return (
      <section className={styles.card}>
        <h2>تحلیل هوشمند</h2>

        <p className={styles.empty}>هنوز تحلیل AI موجود نیست</p>
      </section>
    );
  }

  const sorted = [...sections].sort(
    (a, b) => (b.answer_probability ?? 0) - (a.answer_probability ?? 0),
  );

  return (
    <section className={styles.card}>
      <div className={styles.header}>
        <div>
          <h2>🧠 تحلیل هوشمند مقاله</h2>

          <p>بخش‌هایی که بیشترین ارزش پاسخ‌دهی دارند</p>
        </div>

        <div className={styles.count}>
          {sections.length}
          بخش
        </div>
      </div>

      <div className={styles.list}>
        {sorted.map((section, index) => (
          <article key={section.id ?? index} className={styles.item}>
            <div className={styles.top}>
              <div>
                <h3>{section.section_title}</h3>

                <span>رتبه AI: #{section.planner_rank}</span>
              </div>

              <div className={styles.score}>
                {Math.round((section.answer_probability ?? 0) * 100)}%
              </div>
            </div>

            <div className={styles.progress}>
              <div
                style={{
                  width: `${(section.answer_probability ?? 0) * 100}%`,
                }}
              />
            </div>

            <p className={styles.summary}>{section.summary}</p>

            <div className={styles.keywords}>
              {(section.keywords ?? []).map((keyword: string) => (
                <span key={keyword}>{keyword}</span>
              ))}
            </div>

            <div className={styles.meta}>
              <span>
                📄
                {section.metadata?.word_count || 0}
                کلمه
              </span>

              <span>⭐ اهمیت بالا</span>
            </div>
          </article>
        ))}
      </div>
    </section>
  );
}
