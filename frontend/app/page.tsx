"use client";

import Footer from "@/components/Common/Footer.tsx/Footer";
import Header from "@/components/Common/header/Header";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { useEffect, useState } from "react";

import styles from "./page.module.css";

import { getDashboard, initiateWorkspacePayment } from "@/lib/api";
import { services } from "@/lib/services";
import { Workspace, WorkspaceType } from "@/types/dashboard";

function getPlanIcon(planName: string) {
  const name = planName?.toLowerCase() || "";
  if (name.includes("advanced") || name.includes("پیشرفته")) return "⚡";
  if (name.includes("pro") || name.includes("حرفه‌ای")) return "💎";
  return "🌱";
}

export default function Home() {
  const router = useRouter();
  const [workspaceTypes, setWorkspaceTypes] = useState<WorkspaceType[]>([]);
  const [activeWorkspaces, setActiveWorkspaces] = useState<Workspace[]>([]);
  const [loadingTypes, setLoadingTypes] = useState(true);
  const [upgradingId, setUpgradingId] = useState<number | null>(null);

  useEffect(() => {
    async function fetchPublicWorkspaces() {
      try {
        setLoadingTypes(true);
        const data = await getDashboard();
        if (data) {
          if (data.workspace_types) {
            setWorkspaceTypes(data.workspace_types);
            console.log(data);
          } else if (Array.isArray(data)) {
            setWorkspaceTypes(data);
          }

          if (data.workspaces) {
            setActiveWorkspaces(data.workspaces);
          }
        }
      } catch (error) {
        console.error(
          "Could not fetch workspace types for public view:",
          error,
        );
      } finally {
        setLoadingTypes(false);
      }
    }

    fetchPublicWorkspaces();
  }, []);

  async function handlePurchase(
    planId: number,
    workspaceTypeId: number,
    existingWorkspaceId?: number,
  ) {
    const token = localStorage.getItem("auth_token");
    if (!token) {
      router.push("/login");
      return;
    }

    try {
      setUpgradingId(planId);

      const targetId = existingWorkspaceId || workspaceTypeId;
      const response = await initiateWorkspacePayment(targetId, planId);

      if (response?.payment_url) {
        window.location.href = response.payment_url;
      } else {
        alert("پلن با موفقیت فعال شد!");
        window.location.reload();
      }
    } catch (error: any) {
      console.error("Payment initiation failed:", error);
      alert(error?.response?.data?.message || "خطا در اتصال به درگاه پرداخت");
    } finally {
      setUpgradingId(null);
    }
  }

  return (
    <div className={styles.container}>
      <Header />

      <main className={styles.hero}>
        <div className={styles.badge}>نسل جدید دستیارهای هوشمند</div>

        <h1 className={styles.title}>
          با یک اکانت، به همه
          <span className={styles.gradient}> مدل‌های هوش مصنوعی </span>
          دسترسی داشته باش
        </h1>

        <p className={styles.sub}>
          یک پنل واحد برای ChatGPT، Grok، Claude و Codex — بدون نیاز به ساخت چند
          حساب کاربری.
        </p>

        <div className={styles.actions}>
          <Link href="/chat" className={styles.cta}>
            🚀 شروع مکالمه
          </Link>

          <Link href="/login" className={styles.secondaryCta}>
            ورود به حساب
          </Link>
        </div>
      </main>

      {/* Workspace Types & Plans Section */}
      <section className={styles.servicesSection}>
        <div className={styles.sectionHeader}>
          <h2>محیط‌های کاری و پلن‌ها</h2>
          <p>
            محیط‌های اختصاصی همراه با ابزارها و فیچرهای پیشرفته برای تحقیق،
            برنامه‌نویسی و یادگیری
          </p>
        </div>

        {loadingTypes ? (
          <div
            style={{ textAlign: "center", color: "#94a3b8", padding: "40px" }}
          >
            در حال بارگذاری محیط‌های کاری...
          </div>
        ) : (
          <div
            style={{
              display: "flex",
              flexDirection: "column",
              gap: "25px",
              width: "100%",
              direction: "rtl",
            }}
          >
            {workspaceTypes.map((wsType) => {
              const matchedWorkspaces = activeWorkspaces.filter(
                (w) => w.workspace_type?.id === wsType.id,
              );

              const hasActiveWorkspace = matchedWorkspaces.length > 0;
              const primaryMatchedWorkspace = matchedWorkspaces[0];

              // پیدا کردن پلن فعال در این محیط کاری بر اساس قیمت یا مشخصات
              const activePlan = wsType.plans?.find((p) =>
                matchedWorkspaces.some((ws) => ws.plan?.id === p.id),
              );

              const wsTypeFeatures = wsType.features || [];

              return (
                <div
                  key={wsType.id}
                  style={{
                    background: "rgba(255, 255, 255, 0.03)",
                    border: hasActiveWorkspace
                      ? "1px solid rgba(59, 130, 246, 0.4)"
                      : "1px solid rgba(255, 255, 255, 0.08)",
                    borderRadius: "20px",
                    padding: "24px",
                  }}
                >
                  <div
                    style={{
                      marginBottom: "20px",
                      borderBottom: "1px solid rgba(255, 255, 255, 0.06)",
                      paddingBottom: "18px",
                    }}
                  >
                    <div
                      style={{
                        display: "flex",
                        justifyContent: "space-between",
                        alignItems: "flex-start",
                        flexWrap: "wrap",
                        gap: "12px",
                      }}
                    >
                      <div
                        style={{
                          display: "flex",
                          alignItems: "center",
                          gap: "16px",
                        }}
                      >
                        <div style={{ fontSize: "32px" }}>
                          {wsType.icon ?? "🚀"}
                        </div>
                        <div>
                          <h3
                            style={{
                              margin: "0 0 4px 0",
                              fontSize: "22px",
                              color: "#fff",
                            }}
                          >
                            {wsType.display_name ?? wsType.name}
                          </h3>
                          <p
                            style={{
                              margin: 0,
                              color: "#94a3b8",
                              fontSize: "14px",
                            }}
                          >
                            {wsType.description}
                          </p>
                        </div>
                      </div>

                      {hasActiveWorkspace && (
                        <span
                          style={{
                            background: "rgba(34, 197, 94, 0.15)",
                            color: "#4ade80",
                            padding: "6px 14px",
                            borderRadius: "12px",
                            fontSize: "13px",
                            fontWeight: 600,
                          }}
                        >
                          محیط کاری فعال شما
                        </span>
                      )}
                    </div>

                    {wsTypeFeatures.length > 0 && (
                      <div
                        style={{
                          display: "flex",
                          flexWrap: "wrap",
                          gap: "10px",
                          marginTop: "16px",
                        }}
                      >
                        {wsTypeFeatures.map((feature, fIdx) => {
                          const featureTitle =
                            typeof feature === "string"
                              ? feature
                              : feature.title;
                          const featureDesc =
                            typeof feature === "object"
                              ? feature.desc
                              : undefined;
                          const featureIcon =
                            typeof feature === "object"
                              ? feature.icon
                              : undefined;

                          return (
                            <div
                              key={fIdx}
                              style={{
                                background: "rgba(59, 130, 246, 0.08)",
                                border: "1px solid rgba(59, 130, 246, 0.2)",
                                color: "#e2e8f0",
                                padding: "6px 12px",
                                borderRadius: "10px",
                                fontSize: "12px",
                                display: "flex",
                                alignItems: "center",
                                gap: "8px",
                              }}
                            >
                              <span
                                style={{ color: "#60a5fa", fontSize: "14px" }}
                              >
                                {featureIcon || "✦"}
                              </span>
                              <div
                                style={{
                                  display: "flex",
                                  flexDirection: "column",
                                }}
                              >
                                <span
                                  style={{ fontWeight: 600, color: "#fff" }}
                                >
                                  {featureTitle}
                                </span>
                                {featureDesc && (
                                  <span
                                    style={{
                                      fontSize: "11px",
                                      color: "#94a3b8",
                                    }}
                                  >
                                    {featureDesc}
                                  </span>
                                )}
                              </div>
                            </div>
                          );
                        })}
                      </div>
                    )}
                  </div>

                  <div
                    style={{
                      display: "grid",
                      gridTemplateColumns:
                        "repeat(auto-fit, minmax(290px, 1fr))",
                      gap: "18px",
                    }}
                  >
                    {wsType.plans?.map((plan) => {
                      const isBusy = upgradingId === plan.id;
                      const planIcon = getPlanIcon(
                        plan.name || plan.display_name,
                      );

                      const isCurrentPlanActive = matchedWorkspaces.some(
                        (ws) => ws.plan?.id === plan.id,
                      );

                      // بررسی اینکه آیا پلن فعلی فعال‌شده، از این پلن گران‌تر/حرفه‌ای‌تر است یا خیر
                      const isLowerThanActivePlan =
                        Boolean(activePlan) &&
                        Number(plan.price) < Number(activePlan?.price);

                      const targetWorkspace =
                        matchedWorkspaces.find(
                          (ws) => ws.plan?.id === plan.id,
                        ) || primaryMatchedWorkspace;

                      const modelsList = plan.models || [];

                      return (
                        <div
                          key={plan.id}
                          style={{
                            background: isCurrentPlanActive
                              ? "rgba(59, 130, 246, 0.08)"
                              : "rgba(255, 255, 255, 0.04)",
                            border: isCurrentPlanActive
                              ? "1px solid rgba(59, 130, 246, 0.5)"
                              : "1px solid rgba(255, 255, 255, 0.08)",
                            borderRadius: "16px",
                            padding: "20px",
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
                              <div style={{ fontSize: "24px" }}>{planIcon}</div>
                              {isCurrentPlanActive && (
                                <span
                                  style={{
                                    background: "#2563eb",
                                    color: "#fff",
                                    fontSize: "11px",
                                    padding: "4px 10px",
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
                                fontSize: "18px",
                                color: "#fff",
                              }}
                            >
                              {plan.display_name ?? plan.name}
                            </h4>

                            <p
                              style={{
                                fontSize: "18px",
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
                                marginBottom: "14px",
                              }}
                            >
                              سقف توکن:{" "}
                              <strong style={{ color: "#fff" }}>
                                {plan.token_limit?.toLocaleString() ??
                                  "نامحدود"}
                              </strong>
                            </p>

                            <div
                              style={{
                                background: "rgba(0, 0, 0, 0.25)",
                                border: "1px solid rgba(255, 255, 255, 0.06)",
                                borderRadius: "12px",
                                padding: "12px",
                                marginBottom: "16px",
                              }}
                            >
                              <span
                                style={{
                                  fontSize: "11px",
                                  color: "#94a3b8",
                                  display: "block",
                                  marginBottom: "8px",
                                  fontWeight: 600,
                                }}
                              >
                                مدل‌های هوش مصنوعی مجاز:
                              </span>
                              {modelsList.length > 0 ? (
                                <div
                                  style={{
                                    display: "flex",
                                    flexWrap: "wrap",
                                    gap: "6px",
                                  }}
                                >
                                  {modelsList.map((model, mIdx) => {
                                    const modelName =
                                      typeof model === "string"
                                        ? model
                                        : model.name;
                                    return (
                                      <span
                                        key={mIdx}
                                        style={{
                                          background:
                                            "rgba(59, 130, 246, 0.15)",
                                          border:
                                            "1px solid rgba(59, 130, 246, 0.3)",
                                          color: "#93c5fd",
                                          padding: "3px 9px",
                                          borderRadius: "8px",
                                          fontSize: "11px",
                                          fontWeight: 500,
                                        }}
                                      >
                                        ✨ {modelName}
                                      </span>
                                    );
                                  })}
                                </div>
                              ) : (
                                <span
                                  style={{ fontSize: "11px", color: "#cbd5e1" }}
                                >
                                  دسترسی استاندارد به مدل‌های پایه
                                </span>
                              )}
                            </div>
                          </div>

                          {isCurrentPlanActive ? (
                            <button
                              onClick={() =>
                                router.push(`/workspace/${targetWorkspace?.id}`)
                              }
                              style={{
                                width: "100%",
                                background: "rgba(34, 197, 94, 0.15)",
                                color: "#4ade80",
                                border: "1px solid rgba(34, 197, 94, 0.3)",
                                padding: "12px",
                                borderRadius: "12px",
                                fontWeight: 700,
                                cursor: "pointer",
                                marginTop: "6px",
                              }}
                            >
                              🚀 ورود به محیط کاری
                            </button>
                          ) : isLowerThanActivePlan ? (
                            <div
                              style={{
                                width: "100%",
                                background: "rgba(255, 255, 255, 0.04)",
                                color: "#94a3b8",
                                border: "1px solid rgba(255, 255, 255, 0.08)",
                                padding: "12px",
                                borderRadius: "12px",
                                fontWeight: 500,
                                textAlign: "center",
                                fontSize: "13px",
                                marginTop: "6px",
                              }}
                            >
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
                              style={{
                                width: "100%",
                                background:
                                  "linear-gradient(135deg, #2563eb, #7c3aed)",
                                color: "#fff",
                                border: "none",
                                padding: "12px",
                                borderRadius: "12px",
                                fontWeight: 700,
                                cursor: "pointer",
                                marginTop: "6px",
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
        )}
      </section>
      <section className={styles.servicesSection}>
        <div className={styles.sectionHeader}>
          <h2>سرویس‌های هوش مصنوعی</h2>
          <p>فعلاً فقط ChatGPT فعال است</p>
        </div>

        <div className={styles.servicesGrid}>
          {services.map((service, index) => {
            const isActive = index === 0 || service.status === "active";

            return (
              <div
                key={service.id}
                className={`${styles.serviceCard} ${
                  isActive ? styles.activeService : ""
                }`}
              >
                <div className={styles.serviceTop}>
                  <div className={styles.serviceIcon}>{service.icon}</div>

                  <span
                    className={`${styles.serviceStatus} ${
                      styles.statusActive
                    }`}
                  >
                    فعال
                  </span>
                </div>

                <h3>{service.name}</h3>

                <p>{service.description}</p>

                <div className={styles.serviceButton}>فعال</div>
              </div>
            );
          })}
        </div>
      </section>
      <Footer />
    </div>
  );
}