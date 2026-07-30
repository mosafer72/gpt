"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";

import styles from "./page.module.css";
import { getDashboard, initiateWorkspacePayment } from "@/lib/api";

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
      console.log("Error loading dashboard data:", error);
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

      // آرگومان اول باید یک عدد (شناسه) باشد، نه آبجکت
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

  const user = dashboard.user;
  const wallet = dashboard.wallet;
  const usage = dashboard.today_usage;
  const workspaceTypes =
    dashboard.workspace_types ?? dashboard.available_workspace_types ?? [];

  // اطلاعات ورک‌پیس و پلن‌های فعال کاربر
  const activeWorkspaces =
    dashboard.workspaces ?? (dashboard.workspace ? [dashboard.workspace] : []);
  const activeWorkspaceTypeId = dashboard.workspace?.workspace_type_id;

  // محاسبه کل توکن ولت
  const totalTokens = wallet?.total_tokens || 1;
  const usedTokens = wallet?.used_tokens || 0;
  const remainingTokens = wallet?.remaining_tokens || 0;
  const usagePercent = Math.min(
    Math.round((usedTokens / totalTokens) * 100),
    100,
  );

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
          <strong>{user?.name ?? "کاربر"}</strong>
          <span>{user?.phone}</span>
          <button onClick={logout}>خروج</button>
        </div>
      </header>

      <main className={styles.content}>
        {/* Token / Usage Overview */}
        <section className={styles.statsGrid}>
          <div className={styles.statCard}>
            <span>توکن باقیمانده کیف پول</span>
            <strong>{remainingTokens.toLocaleString()}</strong>
          </div>

          <div className={styles.statCard}>
            <span>درخواست‌های امروز</span>
            <strong>{usage?.requests ?? 0}</strong>
          </div>

          <div className={styles.statCard}>
            <span>توکن مصرفی امروز</span>
            <strong>{usage?.total_tokens?.toLocaleString() ?? 0}</strong>
          </div>
        </section>

        {/* ---------------- بخش پلن‌های فعال کاربر (کارت‌های مربعی) ---------------- */}
        {activeWorkspaces.length > 0 && (
          <section style={{ marginTop: "30px" }}>
            <div style={{ marginBottom: "16px" }}>
              <h2
                style={{ fontSize: "22px", marginBottom: "4px", color: "#fff" }}
              >
                پلن‌های فعال کاربر
              </h2>
              <p style={{ color: "#94a3b8", fontSize: "13px" }}>
                وضعیت مصرف و جزئیات پلن‌ها و محیط‌های کاری فعال شما
              </p>
            </div>

            <div
              style={{
                display: "grid",
                gridTemplateColumns: "repeat(auto-fit, minmax(280px, 350px))",
                gap: "20px",
              }}
            >
              {activeWorkspaces.map((ws: any, index: number) => {
                const activeSubscription = ws.workspace_subscriptions?.[0];
                const activePlan =
                  activeSubscription?.workspace_plan ??
                  activeSubscription?.plan;
                const planName =
                  activePlan?.display_name || activePlan?.name || "پلن فعال";
                const planIcon = getPlanIcon(planName);

                return (
                  <div
                    key={ws.id || index}
                    style={{
                      background: "rgba(59, 130, 246, 0.05)",
                      border: "1px solid rgba(59, 130, 246, 0.3)",
                      borderRadius: "20px",
                      padding: "20px",
                      display: "flex",
                      flexDirection: "column",
                      justifyContent: "space-between",
                      gap: "16px",
                    }}
                  >
                    <div>
                      {/* هدر کارت */}
                      <div
                        style={{
                          display: "flex",
                          justifyContent: "space-between",
                          alignItems: "flex-start",
                          marginBottom: "12px",
                        }}
                      >
                        <div
                          style={{
                            display: "flex",
                            alignItems: "center",
                            gap: "10px",
                          }}
                        >
                          <div style={{ fontSize: "26px" }}>
                            {ws.workspace_type?.icon || planIcon || "🚀"}
                          </div>
                          <div>
                            <h3
                              style={{
                                margin: "0 0 2px 0",
                                fontSize: "16px",
                                color: "#fff",
                              }}
                            >
                              {ws.name}
                            </h3>
                            <span
                              style={{ fontSize: "12px", color: "#94a3b8" }}
                            >
                              {ws.workspace_type?.display_name || "محیط کاری"}
                            </span>
                          </div>
                        </div>
                        <span
                          style={{
                            background: "rgba(34, 197, 94, 0.15)",
                            color: "#4ade80",
                            padding: "3px 10px",
                            borderRadius: "10px",
                            fontSize: "11px",
                            fontWeight: 600,
                          }}
                        >
                          {planName}
                        </span>
                      </div>

                      {/* باکس داخلی با پروگرس‌بار */}
                      <div
                        style={{
                          background: "rgba(0, 0, 0, 0.2)",
                          border: "1px solid rgba(255, 255, 255, 0.08)",
                          borderRadius: "12px",
                          padding: "12px",
                          marginTop: "10px",
                        }}
                      >
                        <div
                          style={{
                            display: "flex",
                            justifyContent: "space-between",
                            fontSize: "12px",
                            marginBottom: "6px",
                            color: "#cbd5e1",
                          }}
                        >
                          <span>
                            مصرف:{" "}
                            <strong style={{ color: "#fff" }}>
                              {usedTokens.toLocaleString()}
                            </strong>
                          </span>
                          <span>
                            باقیمانده:{" "}
                            <strong style={{ color: "#4ade80" }}>
                              {remainingTokens.toLocaleString()}
                            </strong>
                          </span>
                        </div>
                        <div
                          style={{
                            display: "flex",
                            justifyContent: "space-between",
                            fontSize: "12px",
                            marginBottom: "6px",
                            color: "#cbd5e1",
                          }}
                        >
                          <span>
                            کل سهمیه:{" "}
                            <strong style={{ color: "#60a5fa" }}>
                              {totalTokens.toLocaleString()}
                            </strong>
                          </span>
                          <span>({usagePercent}%)</span>
                        </div>

                        {/* Progress Bar */}
                        <div
                          style={{
                            width: "100%",
                            height: "8px",
                            background: "rgba(255, 255, 255, 0.08)",
                            borderRadius: "4px",
                            overflow: "hidden",
                          }}
                        >
                          <div
                            style={{
                              width: `${usagePercent}%`,
                              height: "100%",
                              background:
                                "linear-gradient(90deg, #3b82f6, #8b5cf6)",
                              borderRadius: "4px",
                              transition: "width 0.4s ease",
                            }}
                          />
                        </div>
                      </div>
                    </div>

                    <button
                      onClick={() => router.push(`/workspace/${ws.id}`)}
                      style={{
                        width: "100%",
                        background: "linear-gradient(135deg, #22c55e, #16a34a)",
                        color: "#fff",
                        border: "none",
                        padding: "10px",
                        borderRadius: "12px",
                        fontWeight: 700,
                        cursor: "pointer",
                        fontSize: "13px",
                        boxShadow: "0 4px 12px rgba(34, 197, 94, 0.2)",
                      }}
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
        <section style={{ marginTop: "35px" }}>
          <div className={styles.sectionTitle} style={{ marginBottom: "20px" }}>
            <h2 style={{ fontSize: "26px", marginBottom: "6px" }}>
              محیط‌های کاری و پلن‌ها
            </h2>
            <p style={{ color: "#94a3b8", fontSize: "14px" }}>
              پلن فعال خود را مدیریت کنید یا برای ارتقاء و تغییر محیط کاری اقدام
              نمایید.
            </p>
          </div>

          <div
            style={{ display: "flex", flexDirection: "column", gap: "25px" }}
          >
            {workspaceTypes.map((wsType: any) => {
              const isCurrentWorkspaceType =
                wsType.id === activeWorkspaceTypeId;
              // پیدا کردن ورک‌پیس مرتبط با این نوع در صورت وجود
              const matchedWorkspace = activeWorkspaces.find(
                (w: any) => w.workspace_type_id === wsType.id,
              );

              return (
                <div
                  key={wsType.id}
                  className={styles.card}
                  style={{
                    background: "rgba(255, 255, 255, 0.03)",
                    border: isCurrentWorkspaceType
                      ? "1px solid rgba(59, 130, 246, 0.4)"
                      : "1px solid rgba(255, 255, 255, 0.08)",
                    borderRadius: "20px",
                    padding: "20px",
                  }}
                >
                  {/* هدر ورک‌پیس تایپ همراه با نمایش وضعیت اکتیو */}
                  <div
                    style={{
                      marginBottom: "18px",
                      borderBottom: "1px solid rgba(255, 255, 255, 0.06)",
                      paddingBottom: "16px",
                    }}
                  >
                    <div
                      style={{
                        display: "flex",
                        justifyContent: "space-between",
                        alignItems: "center",
                      }}
                    >
                      <div
                        style={{
                          display: "flex",
                          alignItems: "center",
                          gap: "14px",
                        }}
                      >
                        <div style={{ fontSize: "28px" }}>
                          {wsType.icon ?? "🚀"}
                        </div>
                        <div>
                          <h3
                            style={{
                              margin: "0 0 2px 0",
                              fontSize: "20px",
                              color: "#fff",
                            }}
                          >
                            {wsType.display_name ?? wsType.name}
                          </h3>
                          <p
                            style={{
                              margin: 0,
                              color: "#94a3b8",
                              fontSize: "13px",
                            }}
                          >
                            {wsType.description}
                          </p>
                        </div>
                      </div>

                      {isCurrentWorkspaceType && (
                        <span
                          style={{
                            background: "rgba(34, 197, 94, 0.15)",
                            color: "#4ade80",
                            padding: "5px 14px",
                            borderRadius: "12px",
                            fontSize: "13px",
                            fontWeight: 600,
                          }}
                        >
                          محیط کاری فعال شما
                        </span>
                      )}
                    </div>
                  </div>

                  {/* لیست پلن‌ها */}
                  <div
                    style={{
                      display: "grid",
                      gridTemplateColumns:
                        "repeat(auto-fit, minmax(240px, 1fr))",
                      gap: "15px",
                    }}
                  >
                    {wsType.plans?.map((plan: any) => {
                      const isBusy = upgradingId === plan.id;
                      const planIcon = getPlanIcon(
                        plan.name || plan.display_name,
                      );

                      // بررسی اینکه آیا این پلن خاص در این تایپ فعال است یا خیر
                      const activeSubscription =
                        matchedWorkspace?.workspace_subscriptions?.[0];
                      const isCurrentPlanActive =
                        activeSubscription?.workspace_plan_id === plan.id ||
                        activeSubscription?.plan_id === plan.id;

                      return (
                        <div
                          key={plan.id}
                          style={{
                            background: isCurrentPlanActive
                              ? "rgba(59, 130, 246, 0.08)"
                              : "rgba(255, 255, 255, 0.04)",
                            border: isCurrentPlanActive
                              ? "1px solid rgba(59, 130, 246, 0.4)"
                              : "1px solid rgba(255, 255, 255, 0.08)",
                            borderRadius: "16px",
                            padding: "18px",
                            display: "flex",
                            flexDirection: "column",
                            justifyContent: "space-between",
                          }}
                        >
                          <div>
                            <div
                              style={{
                                display: "flex",
                                justifyContent: "space-between",
                                alignItems: "center",
                                marginBottom: "12px",
                              }}
                            >
                              <div style={{ fontSize: "22px" }}>{planIcon}</div>
                              {isCurrentPlanActive && (
                                <span
                                  style={{
                                    background: "#2563eb",
                                    color: "#fff",
                                    fontSize: "11px",
                                    padding: "3px 10px",
                                    borderRadius: "10px",
                                    fontWeight: 600,
                                  }}
                                >
                                  پلن فعال
                                </span>
                              )}
                            </div>

                            <h4
                              style={{
                                margin: "0 0 6px 0",
                                fontSize: "17px",
                                color: "#fff",
                              }}
                            >
                              {plan.display_name ?? plan.name}
                            </h4>

                            <p
                              style={{
                                fontSize: "17px",
                                fontWeight: 700,
                                color: "#60a5fa",
                                marginBottom: "6px",
                              }}
                            >
                              {plan.price > 0
                                ? `${Number(plan.price).toLocaleString()} تومان`
                                : "رایگان"}
                            </p>

                            <p
                              style={{
                                fontSize: "13px",
                                color: "#94a3b8",
                                marginBottom: "12px",
                              }}
                            >
                              سقف توکن:{" "}
                              <strong style={{ color: "#fff" }}>
                                {plan.token_limit?.toLocaleString() ??
                                  "نامحدود"}
                              </strong>
                            </p>
                          </div>

                          {/* تغییر دکمه و ارسال شناسه صحیح محیط کاری */}
                          {isCurrentPlanActive ? (
                            <button
                              onClick={() =>
                                router.push(
                                  `/workspace/${matchedWorkspace?.id ?? activeWorkspaces[0]?.id}`,
                                )
                              }
                              style={{
                                width: "100%",
                                background: "rgba(34, 197, 94, 0.15)",
                                color: "#4ade80",
                                border: "1px solid rgba(34, 197, 94, 0.3)",
                                padding: "11px",
                                borderRadius: "12px",
                                fontWeight: 700,
                                cursor: "pointer",
                                marginTop: "12px",
                              }}
                            >
                              🚀 ورود به محیط کاری
                            </button>
                          ) : (
                            <button
                              disabled={isBusy}
                              onClick={() => handlePurchase(plan.id, wsType.id)}
                              style={{
                                width: "100%",
                                background:
                                  "linear-gradient(135deg, #2563eb, #7c3aed)",
                                color: "#fff",
                                border: "none",
                                padding: "11px",
                                borderRadius: "12px",
                                fontWeight: 700,
                                cursor: "pointer",
                                marginTop: "12px",
                              }}
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
    </div>
  );
}
