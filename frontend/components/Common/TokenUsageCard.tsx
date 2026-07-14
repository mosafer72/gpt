"use client";

import { useEffect, useState } from "react";

import styles from "./TokenUsageCard.module.css";

const API = process.env.NEXT_PUBLIC_API_URL || "http://localhost:8000/api";

interface Usage {
  total_tokens: number;

  used_tokens: number;

  remaining_tokens: number;
}

export default function TokenUsageCard() {
  const [usage, setUsage] = useState<Usage | null>(null);

  const [loading, setLoading] = useState(true);

  useEffect(() => {
    async function load() {
      try {
        const token = localStorage.getItem("auth_token");

        const res = await fetch(`${API}/token-wallet`, {
          headers: {
            Authorization: `Bearer ${token}`,

            Accept: "application/json",
          },
        });

        if (!res.ok) {
          throw new Error("Token wallet failed");
        }

        const data = await res.json();

        console.log("TOKEN WALLET:", data);

        setUsage({
          total_tokens: Number(data.total_tokens ?? 0),

          used_tokens: Number(data.used_tokens ?? 0),

          remaining_tokens: Number(data.remaining_tokens ?? 0),
        });
      } catch (error) {
        console.error(error);

        setUsage({
          total_tokens: 0,

          used_tokens: 0,

          remaining_tokens: 0,
        });
      } finally {
        setLoading(false);
      }
    }

    load();
  }, []);

  if (loading) {
    return <section className={styles.card}>در حال دریافت اعتبار...</section>;
  }

  if (!usage) {
    return null;
  }

  const percent =
    usage.total_tokens > 0
      ? Math.min(
          100,

          Math.round((usage.used_tokens / usage.total_tokens) * 100),
        )
      : 0;

  return (
    <section className={styles.card}>
      <div className={styles.header}>
        <div>
          <h3>اعتبار توکن</h3>

          <span>مصرف اشتراک فعلی شما</span>
        </div>

        <div className={styles.percent}>{percent}%</div>
      </div>

      <div className={styles.progress}>
        <div
          className={styles.progressBar}
          style={{
            width: `${percent}%`,
          }}
        />
      </div>

      <div className={styles.stats}>
        <div>
          <small>کل اعتبار</small>

          <strong>{usage.total_tokens.toLocaleString()}</strong>
        </div>

        <div>
          <small>مصرف شده</small>

          <strong>{usage.used_tokens.toLocaleString()}</strong>
        </div>

        <div>
          <small>باقی مانده</small>

          <strong>{usage.remaining_tokens.toLocaleString()}</strong>
        </div>
      </div>
    </section>
  );
}
