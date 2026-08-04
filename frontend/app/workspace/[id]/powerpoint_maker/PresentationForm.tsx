"use client";

import { useState } from "react";
import styles from "./PresentationForm.module.css";

export default function PresentationForm({
  onCreate,
}: {
  onCreate: (data: any) => Promise<void>;
}) {
  const [loading, setLoading] = useState(false);
  const [message, setMessage] = useState("");

  async function submit(e: React.FormEvent) {
    e.preventDefault();

    if (!message.trim()) return;

    setLoading(true);

    try {
      await onCreate({
        message: message.trim(),
      });

      setMessage("");
    } finally {
      setLoading(false);
    }
  }

  return (
    <main className={styles.page}>
      <form className={styles.form} onSubmit={submit}>
        <div>
          <h1 className={styles.title}>ساخت ارائه با هوش مصنوعی</h1>

          <p className={styles.subtitle}>
            توضیح دهید چه ارائه‌ای می‌خواهید بسازید.
          </p>
        </div>

        <section className={styles.card}>
          <h2 className={styles.sectionTitle}>درخواست شما</h2>

          <textarea
            className={styles.textarea}
            rows={8}
            value={message}
            placeholder="مثال:
یک ارائه ۱۰ اسلایدی درباره معماری شبکه‌های عصبی بساز."
            onChange={(e) => setMessage(e.target.value)}
            required
          />
        </section>

        <button disabled={loading} className={styles.button}>
          {loading ? "در حال ارسال..." : "🚀 ساخت پاورپوینت"}
        </button>
      </form>
    </main>
  );
}
