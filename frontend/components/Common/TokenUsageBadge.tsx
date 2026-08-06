"use client";

import { useEffect, useState } from "react";
import styles from "./TokenUsageBadge.module.css";
import { getTodayUsage } from "@/lib/api";

interface TokenUsageBadgeProps {
  workspaceId: number;
}

export default function TokenUsageBadge({ workspaceId }: TokenUsageBadgeProps) {
  const [usage, setUsage] = useState({
    total_tokens: 0,

    requests: 0,
  });

  useEffect(() => {
    loadUsage();

    const timer = setInterval(() => {
      loadUsage();
    }, 60000);

    return () => clearInterval(timer);
  }, [workspaceId]);

  const loadUsage = async () => {
    try {
      const data = await getTodayUsage(workspaceId);

      setUsage({
        total_tokens: data.total_tokens ?? 0,

        requests: data.requests ?? 0,
      });
    } catch (error) {
      console.error("Usage load error:", error);
    }
  };

  return (
    <div className={styles.wrapper}>
      <div className={styles.icon}>🧠</div>

      <div className={styles.content}>
        <span>توکن امروز</span>

        <strong>{usage.total_tokens.toLocaleString()}</strong>
      </div>

      <div className={styles.requests}>{usage.requests} درخواست</div>
    </div>
  );
}
