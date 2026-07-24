"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";

import styles from "./page.module.css";

import WorkspaceCard from "@/components/Workspace/WorkspaceCard";
import TokenUsageCard from "@/components/Common/TokenUsageCard";

import { getDashboard } from "@/lib/api";
import { getWorkspaces } from "@/lib/api";

import type { Workspace } from "@/types";

export default function DashboardPage() {
  const router = useRouter();

  const [dashboard, setDashboard] = useState<any>(null);

  const [workspaces, setWorkspaces] = useState<Workspace[]>([]);

  const [loading, setLoading] = useState(true);

  async function load() {
    try {
      const [dashboardData, workspaceData] = await Promise.all([
        getDashboard(),

        getWorkspaces(),
      ]);

      setDashboard(dashboardData);

      setWorkspaces(workspaceData);
    } catch (error) {
      console.log(error);
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    load();
  }, []);

  function logout() {
    localStorage.removeItem("auth_token");

    localStorage.removeItem("user");

    router.push("/login");
  }

  if (loading) {
    return <div className={styles.loading}>در حال بارگذاری داشبورد...</div>;
  }

  if (!dashboard) {
    return null;
  }

  const subscription = dashboard.subscription;

  const plan = subscription?.plan;

  const wallet = dashboard.wallet;

  const usage = dashboard.today_usage;

  return (
    <div className={styles.container}>
      {/* Header */}

      <header className={styles.header}>
        <div className={styles.brand}>
          <div className={styles.logo}>AI</div>

          <div>
            <h1>اکانتسرا</h1>

            <p>پنل هوش مصنوعی</p>
          </div>
        </div>

        <div className={styles.userBox}>
          <strong>{dashboard.user?.name ?? "کاربر"}</strong>

          <span>{dashboard.user?.phone}</span>

          <button onClick={logout}>خروج</button>
        </div>
      </header>

      <main className={styles.content}>
        {/* Current Subscription */}

        <section className={styles.heroCard}>
          <div>
            <span>اشتراک فعال</span>

            <h2>{plan?.display_name ?? "بدون اشتراک"}</h2>

            <p>{plan?.workspace_type?.name ?? "Free"}</p>
          </div>

          <div className={styles.badge}>فعال</div>
        </section>

        {/* Token */}

        <section>
          <h2 className={styles.title}>مصرف توکن</h2>

          <TokenUsageCard />
        </section>

        {/* Usage */}

        <section className={styles.statsGrid}>
          <div className={styles.statCard}>
            <h3>درخواست امروز</h3>

            <strong>{usage?.requests ?? 0}</strong>
          </div>

          <div className={styles.statCard}>
            <h3>توکن امروز</h3>

            <strong>{usage?.total_tokens?.toLocaleString() ?? 0}</strong>
          </div>

          <div className={styles.statCard}>
            <h3>باقی مانده</h3>

            <strong>{wallet?.remaining_tokens?.toLocaleString() ?? 0}</strong>
          </div>
        </section>

        {/* Workspaces */}

        <section>
          <div className={styles.sectionTitle}>
            <h2>Workspace ها</h2>

            <p>محیط تخصصی خود را انتخاب کنید</p>
          </div>

          <div className={styles.grid}>
            {workspaces.map((workspace) => (
              <WorkspaceCard key={workspace.id} workspace={workspace} />
            ))}
          </div>
        </section>

        {/* Models */}

        <section>
          <h2 className={styles.title}>مدل‌های فعال</h2>

          <div className={styles.models}>
            {plan?.models?.map((model: any) => (
              <div key={model.id} className={styles.model}>
                🤖 {model.name}
                <small>{model.provider}</small>
              </div>
            ))}
          </div>
        </section>

        <div className={styles.actions}>
          <button onClick={() => router.push("/chat")}>💬 شروع گفتگو</button>

          <button onClick={() => router.push("/workspace")}>
            🚀 Workspace ها
          </button>
        </div>
      </main>
    </div>
  );
}
