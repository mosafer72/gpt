"use client";

import { useRouter, useParams } from "next/navigation";

import styles from "./page.module.css";

const tools = [
  {
    icon: "📄",
    title: "Documents",
    desc: "آپلود و تحلیل جزوه و فایل‌های PDF",
    path: "documents",
  },

  {
    icon: "📚",
    title: "Paper Library",
    desc: "مدیریت و جستجوی مقالات علمی",
    path: "Library",
  },

  {
    icon: "📝",
    title: "Summarizer",
    desc: "خلاصه‌سازی متن و استخراج نکات مهم",
    path: "summarizer",
  },

  {
    icon: "🧮",
    title: "AI Solver",
    desc: "حل تمرین و توضیح مرحله به مرحله",
    path: "solver",
  },

  {
    icon: "🔍",
    title: "Research",
    desc: "تحقیق، مقاله و جمع‌آوری منابع",
    path: "research",
  },

  {
    icon: "🧠",
    title: "Memory",
    desc: "حافظه هوشمند دانشجویی",
    path: "memory",
  },
];

export default function ToolsPage() {
  const router = useRouter();

  const params = useParams();

  const id = params.id;

  return (
    <div className={styles.container}>
      <header className={styles.header}>
        <div className={styles.logo}>🎓</div>

        <div>
          <h1>Student AI Tools</h1>

          <p>ابزارهای هوشمند آموزشی</p>
        </div>
      </header>

      <section className={styles.grid}>
        {tools.map((tool) => (
          <div key={tool.path} className={styles.card}>
            <div className={styles.icon}>{tool.icon}</div>

            <h2>{tool.title}</h2>

            <p>{tool.desc}</p>

            <button
              onClick={() => router.push(`/workspace/${id}/${tool.path}`)}
            >
              ورود
            </button>
          </div>
        ))}
      </section>
    </div>
  );
}
