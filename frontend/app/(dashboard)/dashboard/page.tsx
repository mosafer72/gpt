"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";

import styles from "./page.module.css";
import { getDashboard, initiateWorkspacePayment } from "@/lib/api";
import Header from "@/components/Common/header/Header";
import Footer from "@/components/Common/Footer.tsx/Footer";

function getPlanIcon(planName: string) {
  const name = planName?.toLowerCase() || "";
  if (name.includes("advanced") || name.includes("پیشرفته")) return "⚡";
  if (name.includes("pro") || name.includes("حرفه‌ای")) return "💎";
  return "🌱";
}

export default function DashboardPage() {
  const router = useRouter();

  const [dashboard, setDashboard] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [upgradingId, setUpgradingId] = useState<number | null>(null);

  async function loadData() {
    try {
      setLoading(true);
      const dashboardData = await getDashboard();
      setDashboard(dashboardData);
    } catch (error) {
      console.error("Error loading dashboard data:", error);
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    loadData();
  }, []);

  async function handlePurchase(
    planId: number,
    workspaceTypeId: number,
    existingWorkspaceId?: number,
  ) {
    try {
      setUpgradingId(planId);

      const targetId = existingWorkspaceId || workspaceTypeId;
      const response = await initiateWorkspacePayment(targetId, planId);

      if (response?.payment_url) {
        window.location.href = response.payment_url;
      } else {
        alert("پلن با موفقیت فعال شد!");
        loadData();
      }
    } catch (error: any) {
      console.error("Payment initiation failed:", error);
      alert(error?.response?.data?.message || "خطا در اتصال به درگاه پرداخت");
    } finally {
      setUpgradingId(null);
    }
  }

  if (loading) {
    return <div className={styles.loading}>در حال بارگذاری داشبورد...</div>;
  }

  if (!dashboard) {
    return null;
  }

  const user = dashboard.user;
  const workspaceTypes = dashboard.workspace_types ?? [];
  const activeWorkspaces = dashboard.workspaces ?? [];

  return (
    <div className={styles.container}>
      <div className={styles.backgroundGlow} />
      {/* Header */}
      <Header />

      <main className={styles.content}>
        {/* ---------------- بخش مشخصات کاربر ---------------- */}
        <section className={styles.userProfileSection}>
          <div className={styles.userProfileHeader}>
            <div>
              <h2>مشخصات حساب کاربری</h2>
              <p>اطلاعات هویتی و ثبتی شما در سیستم</p>
            </div>
            <span className={styles.userProfileBadge}>
              حساب کاربری فعال
            </span>
          </div>

          <div className={styles.userProfileGrid}>
            {/* نام و نام خانوادگی */}
            <div className={styles.userProfileItem}>
              <div className={styles.userProfileItemIcon}>👤</div>
              <div>
                <span>نام و نام خانوادگی</span>
                <strong>
                  {user?.name || user?.full_name || "ثبت نشده"}
                </strong>
              </div>
            </div>

            {/* شماره موبایل */}
            <div className={styles.userProfileItem}>
              <div className={styles.userProfileItemIcon}>📱</div>
              <div>
                <span>شماره موبایل</span>
                <strong className={styles.phoneText}>
                  {user?.phone || user?.mobile || "ثبت نشده"}
                </strong>
              </div>
            </div>

            {/* تاریخ عضویت */}
            <div className={styles.userProfileItem}>
              <div className={styles.userProfileItemIcon}>📅</div>
              <div>
                <span>تاریخ عضویت</span>
                <strong>
                  {user?.created_at
                    ? new Date(user.created_at).toLocaleDateString("fa-IR")
                    : "ثبت نشده"}
                </strong>
              </div>
            </div>
          </div>
        </section>

        {/* ---------------- بخش پلن‌های فعال کاربر (کارت‌های مربعی) ---------------- */}
        {activeWorkspaces.length > 0 && (
          <section className={styles.activeWorkspacesSection}>
            <div className={styles.activeWorkspacesHeader}>
              <h2>پلن‌های فعال کاربر</h2>
              <p>وضعیت مصرف و جزئیات پلن‌ها و محیط‌های کاری فعال شما</p>
            </div>

            <div className={styles.activeWorkspacesGrid}>
              {activeWorkspaces.map((ws: any, index: number) => {
                const activePlan = ws.plan;
                const planName =
                  activePlan?.display_name || activePlan?.name || "پلن فعال";
                const planIcon = getPlanIcon(planName);

                const wallet = ws.wallet;
                const usage = ws.today_usage;

                const totalTokens =
                  wallet?.total_tokens > 0 ? wallet.total_tokens : 1;
                const usedTokens = wallet?.used_tokens || 0;
                const remainingTokens = wallet?.remaining_tokens || 0;
                const usagePercent = Math.min(
                  Math.round((usedTokens / totalTokens) * 100),
                  100,
                );

                const wsTypeObj = ws.workspace_type;
                const typeDisplayName = wsTypeObj?.display_name || "محیط کاری";
                const wsIcon = wsTypeObj?.icon || planIcon || "🚀";

                return (
                  <div
                    key={ws.id || index}
                    className={styles.activeWorkspaceCard}
                  >
                    <div>
                      {/* هدر کارت */}
                      <div className={styles.activeWorkspaceCardTop}>
                        <div className={styles.activeWorkspaceCardTitleGroup}>
                          <div className={styles.activeWorkspaceCardIcon}>{wsIcon}</div>
                          <div>
                            <h3>{ws.name}</h3>
                            <span>{typeDisplayName}</span>
                          </div>
                        </div>
                        <span className={styles.activeWorkspaceStatusBadge}>
                          فعال
                        </span>
                      </div>

                      {/* آمار مصرف روزانه */}
                      <div className={styles.activeWorkspaceStatsGrid}>
                        <div className={styles.activeWorkspaceStatBox}>
                          <span>باقیمانده</span>
                          <strong className={styles.remainingVal}>
                            {remainingTokens.toLocaleString()}
                          </strong>
                        </div>
                        <div className={styles.activeWorkspaceStatBox}>
                          <span>درخواست</span>
                          <strong className={styles.requestVal}>
                            {usage?.requests ?? 0}
                          </strong>
                        </div>
                        <div className={styles.activeWorkspaceStatBox}>
                          <span>مصرف امروز</span>
                          <strong className={styles.usageVal}>
                            {usage?.total_tokens?.toLocaleString() ?? 0}
                          </strong>
                        </div>
                      </div>

                      {/* باکس داخلی با پروگرس‌بار */}
                      <div className={styles.activeWorkspaceProgressContainer}>
                        <div className={styles.activeWorkspaceProgressInfo}>
                          <span>
                            مصرف کل:{" "}
                            <strong>
                              {usedTokens.toLocaleString()}
                            </strong>
                          </span>
                          <span>({usagePercent}%)</span>
                        </div>

                        {/* Progress Bar */}
                        <div className={styles.activeWorkspaceProgressBarTrack}>
                          <div
                            className={styles.activeWorkspaceProgressBarFill}
                            style={{ width: `${usagePercent}%` }}
                          />
                        </div>
                      </div>
                    </div>

                    <button
                      onClick={() => router.push(`/workspace/${ws.id}`)}
                      className={styles.activeWorkspaceEnterButton}
                    >
                      🚀 ورود به محیط کاری
                    </button>
                  </div>
                );
              })}
            </div>
          </section>
        )}

        {/* Workspace Types & Plans Section */}
        <section className={styles.workspaceTypesSection}>
          <div className={styles.sectionTitle}>
            <h2>محیط‌های کاری و پلن‌ها</h2>
            <p>
              پلن فعال خود را مدیریت کنید یا برای ارتقاء و تغییر محیط کاری اقدام
              نمایید.
            </p>
          </div>

          <div
            style={{ display: "flex", flexDirection: "column", gap: "25px" }}
          >
            {workspaceTypes.map((wsType: any) => {
              const matchedWorkspaces = activeWorkspaces.filter(
                (w: any) => w.workspace_type?.id === wsType.id,
              );

              const hasActiveWorkspace = matchedWorkspaces.length > 0;
              const primaryMatchedWorkspace = matchedWorkspaces[0];

              const activePlan = wsType.plans?.find((p: any) =>
                matchedWorkspaces.some((ws: any) => ws.plan?.id === p.id),
              );

              return (
                <div
                  key={wsType.id}
                  className={`${styles.workspaceTypeCard} ${
                    hasActiveWorkspace
                      ? styles.workspaceTypeCardActive
                      : styles.workspaceTypeCardInactive
                  }`}
                >
                  <div className={styles.workspaceTypeHeader}>
                    <div className={styles.workspaceTypeHeaderContent}>
                      <div className={styles.workspaceTypeTitleGroup}>
                        <div className={styles.workspaceTypeIcon}>
                          {wsType.icon ?? "🚀"}
                        </div>
                        <div>
                          <h3>
                            {wsType.display_name ?? wsType.name}
                          </h3>
                          <p>
                            {wsType.description}
                          </p>
                        </div>
                      </div>

                      {hasActiveWorkspace && (
                        <span className={styles.workspaceTypeActiveBadge}>
                          محیط کاری فعال شما
                        </span>
                      )}
                    </div>
                  </div>

                  <div className={styles.workspaceTypePlansGrid}>
                    {wsType.plans?.map((plan: any) => {
                      const isBusy = upgradingId === plan.id;
                      const planIcon = getPlanIcon(
                        plan.name || plan.display_name,
                      );

                      const isCurrentPlanActive = matchedWorkspaces.some(
                        (ws: any) => ws.plan?.id === plan.id,
                      );

                      const isLowerThanActivePlan =
                        Boolean(activePlan) &&
                        Number(plan.price) < Number(activePlan?.price);

                      const targetWorkspace =
                        matchedWorkspaces.find(
                          (ws: any) => ws.plan?.id === plan.id,
                        ) || primaryMatchedWorkspace;

                      return (
                        <div
                          key={plan.id}
                          className={`${styles.planItemCard} ${
                            isCurrentPlanActive
                              ? styles.planItemCardActive
                              : styles.planItemCardInactive
                          }`}
                        >
                          <div>
                            <div className={styles.planItemCardTop}>
                              <div className={styles.planItemIcon}>{planIcon}</div>
                              {isCurrentPlanActive && (
                                <span className={styles.planItemCurrentBadge}>
                                  پلن فعال
                                </span>
                              )}
                            </div>

                            <h4>
                              {plan.display_name ?? plan.name}
                            </h4>

                            <p className={styles.planItemPrice}>
                              {plan.price > 0
                                ? `${Number(plan.price).toLocaleString()} تومان`
                                : "رایگان"}
                            </p>

                            <p className={styles.planItemTokenLimit}>
                              سقف توکن:{" "}
                              <strong>
                                {plan.token_limit?.toLocaleString() ??
                                  "نامحدود"}
                              </strong>
                            </p>
                          </div>

                          {isCurrentPlanActive ? (
                            <button
                              onClick={() =>
                                router.push(`/workspace/${targetWorkspace?.id}`)
                              }
                              className={styles.planItemEnterButton}
                            >
                              🚀 ورود به محیط کاری
                            </button>
                          ) : isLowerThanActivePlan ? (
                            <div className={styles.planItemHigherNotice}>
                              پلن بالاتری فعال است
                            </div>
                          ) : (
                            <button
                              disabled={isBusy}
                              onClick={() =>
                                handlePurchase(
                                  plan.id,
                                  wsType.id,
                                  targetWorkspace?.id,
                                )
                              }
                              className={styles.planItemPurchaseButton}
                            >
                              {isBusy
                                ? "در حال انتقال..."
                                : plan.price > 0
                                  ? "خرید و ارتقای پلن"
                                  : "فعال‌سازی رایگان"}
                            </button>
                          )}
                        </div>
                      );
                    })}
                  </div>
                </div>
              );
            })}
          </div>
        </section>
      </main>
      <Footer />
    </div>
  );
}