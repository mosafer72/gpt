"use client";

import { useState } from "react";
import styles from "./DocumentSections.module.css";

interface Translation {
  status?: string;
  title_fa?: string;
  summary_fa?: string;
  translated_content?: string;
}

interface SectionItem {
  id?: string;
  section: string;
  section_title?: string;
  title?: string;
  answer_probability?: number;
  summary?: string;
  summary_fa?: string;
  translated_content?: string;
  translation?: Translation;
  keywords?: string[];
  metadata?: {
    word_count?: number;
    order?: number;
  };
  planner_rank?: number;
}

interface Props {
  sections: SectionItem[];
}

export default function DocumentSections({ sections }: Props) {
  const [openIndex, setOpenIndex] = useState<number | null>(null);
  console.log(sections);
  if (!sections || sections.length === 0) {
    return (
      <section className={styles.card}>
        <div className={styles.header}>
          <h2>📄 بخش‌های سند</h2>
        </div>
        <div className={styles.empty}>
          <span>⚠️</span>
          <p>هیچ بخشی برای این سند پردازش یا یافت نشده است.</p>
        </div>
      </section>
    );
  }

  const sortedSections = [...sections].sort(
    (a, b) =>
      (a.metadata?.order ?? a.planner_rank ?? 999) -
      (b.metadata?.order ?? b.planner_rank ?? 999),
  );

  return (
    <section className={styles.card}>
      <div className={styles.header}>
        <div>
          <h2>📚 بخش‌های تحلیل شده</h2>
          <p>ساختار، ترجمه تخصصی و تحلیل هوش مصنوعی</p>
        </div>
        <div className={styles.badge}>AI Document v2</div>
      </div>

      <div className={styles.list}>
        {sortedSections.map((section, index) => {
          const isOpen = openIndex === index;

          // استخراج امن اطلاعات ترجمه (چه در سطح اول باشد و چه داخل آبجکت translation)
          const summaryFa =
            section.summary_fa ?? section.translation?.summary_fa;
          const translatedContent =
            section.translated_content ??
            section.translation?.translated_content;
          const probability = section.answer_probability;

          return (
            <article
              key={section.id ?? `${section.section}-${index}`}
              className={styles.item}
            >
              <div className={styles.top}>
                <div>
                  <h3>
                    {section.title ?? section.section_title ?? section.section}
                  </h3>
                  <span className={styles.sectionTag}>
                    بخش: {section.section}
                  </span>
                </div>

                {probability !== undefined && (
                  <div className={styles.scoreWrapper}>
                    <span className={styles.scoreLabel}>اطمینان:</span>
                    <div className={styles.score}>
                      {Math.round(probability * 100)}%
                    </div>
                  </div>
                )}
              </div>

              {probability !== undefined && (
                <div className={styles.line}>
                  <div
                    className={styles.lineFill}
                    style={{
                      width: `${probability * 100}%`,
                    }}
                  />
                </div>
              )}

              {/* نمایش خلاصه فارسی یا خلاصه اصلی */}
              {summaryFa ? (
                <p className={styles.summary}>{summaryFa}</p>
              ) : (
                section.summary && (
                  <p className={styles.summary}>{section.summary}</p>
                )
              )}

              {/* بخش ترجمه کامل */}
              {translatedContent && (
                <div className={styles.translationContainer}>
                  <button
                    className={`${styles.button} ${isOpen ? styles.activeButton : ""}`}
                    onClick={() => setOpenIndex(isOpen ? null : index)}
                    aria-expanded={isOpen}
                  >
                    <span>
                      {isOpen ? "بستن ترجمه تخصصی" : "مشاهده ترجمه کامل متن"}
                    </span>
                    <span
                      className={`${styles.arrow} ${isOpen ? styles.arrowUp : ""}`}
                    >
                      ▼
                    </span>
                  </button>

                  {isOpen && (
                    <div className={styles.translationBox}>
                      <p>{translatedContent}</p>
                    </div>
                  )}
                </div>
              )}

              <div className={styles.bottom}>
                <div className={styles.keywords}>
                  {(section.keywords ?? []).map((keyword: string) => (
                    <span key={keyword} className={styles.keywordTag}>
                      #{keyword}
                    </span>
                  ))}
                </div>

                <div className={styles.words}>
                  <span>📝</span>
                  <span>{section.metadata?.word_count ?? 0} کلمه</span>
                </div>
              </div>
            </article>
          );
        })}
      </div>
    </section>
  );
}
