"use client";

import styles from "./AllInsights.module.css";

interface Props {
  sections: any[];
}

export default function AllInsights({ sections = [] }: Props) {
  if (!sections.length) {
    return (
      <section className={styles.card}>
        <h2>🧠 تحلیل هوشمند</h2>

        <p className={styles.empty}>هنوز تحلیلی تولید نشده است</p>
      </section>
    );
  }

  const total = sections.length;

  const highPriority = sections.filter(
    (item) => item.answer_probability >= 0.8,
  ).length;

  return (
    <section className={styles.card}>
      <header className={styles.header}>
        <div>
          <h2>🧠 All Insights</h2>

          <p>تحلیل هوشمند ساختار مقاله توسط AI</p>
        </div>

        <div className={styles.badge}>
          {total}
          Insight
        </div>
      </header>

      <div className={styles.metrics}>
        <div className={styles.metric}>
          <span>📌</span>

          <div>
            <strong>{highPriority}</strong>

            <p>بخش مهم</p>
          </div>
        </div>

        <div className={styles.metric}>
          <span>🤖</span>

          <div>
            <strong>AI</strong>

            <p>تحلیل شده</p>
          </div>
        </div>

        <div className={styles.metric}>
          <span>📚</span>

          <div>
            <strong>{total}</strong>

            <p>Sections</p>
          </div>
        </div>
      </div>

      <div className={styles.list}>
        {sections.map((section, index) => (
          <article
            key={section.id ?? `${section.section}-${index}`}
            className={styles.item}
          >
            <div className={styles.top}>
              <h3>{section.section_title}</h3>

              <span
                className={
                  section.answer_probability >= 0.8
                    ? styles.high
                    : styles.normal
                }
              >
                {Math.round((section.answer_probability ?? 0) * 100)}%
              </span>
            </div>

            <p className={styles.summary}>{section.summary}</p>

            <div className={styles.footer}>
              <div className={styles.keywords}>
                {(section.keywords ?? []).map((keyword: string) => (
                  <span key={keyword}>#{keyword}</span>
                ))}
              </div>

              <div className={styles.rank}>
                Rank
                {section.planner_rank}
              </div>
            </div>
          </article>
        ))}
      </div>
    </section>
  );
}
