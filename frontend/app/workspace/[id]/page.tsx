"use client";

import { useEffect, useState } from "react";
import { useParams, useRouter } from "next/navigation";
import styles from "./page.module.css";
import {
  getWorkspace,
  getWorkspacePlans,
  initiateWorkspacePayment,
} from "@/lib/api";

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
    path: "library",
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

// تعیین آیکون جذاب بر اساس نام یا نوع پلن
function getPlanIcon(planName: string) {
  const name = planName.toLowerCase();
  if (name.includes("advanced") || name.includes("پیشرفته")) return "⚡";
  if (name.includes("pro") || name.includes("حرفه‌ای")) return "💎";
  return "🌱"; // برای پلن‌های پایه (Basic)
}

export default function WorkspacePage() {
  const params = useParams();
  const router = useRouter();
  const id = Number(params?.id);

  const [workspace, setWorkspace] = useState<any>(null);
  const [plans, setPlans] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [upgradingId, setUpgradingId] = useState<number | null>(null);

  async function loadData() {
    try {
      setLoading(true);
      const [wsData, plansData] = await Promise.all([
        getWorkspace(id),
        getWorkspacePlans(id),
      ]);
      setWorkspace(wsData.workspace);
      setPlans(plansData.data ?? plansData);
    } catch (error) {
      console.log("Error loading workspace data:", error);
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    if (id) {
      loadData();
    }
  }, [id]);

  async function handleUpgrade(planId: number) {
    try {
      setUpgradingId(planId);
      const response = await initiateWorkspacePayment(id, planId);

      if (response.payment_url) {
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
    return <div className={styles.loading}>در حال بارگذاری Workspace...</div>;
  }

  if (!workspace) {
    return <div className={styles.loading}>Workspace پیدا نشد</div>;
  }

  const type = workspace.workspace_type ?? {};
  const currentPlan = workspace.plan ?? {};
  const wallet = workspace.wallet ?? {
    total_tokens: 0,
    used_tokens: 0,
    remaining_tokens: 0,
  };

  return (
    <div className={styles.container}>
      <header className={styles.header}>
        <div className={styles.icon}>{type.icon ?? "🚀"}</div>
        <div>
          <h1>{workspace.name}</h1>
          <p>
            {type.display_name}
            {" - "}
            {type.description}
          </p>
        </div>
      </header>

      {/* بخش نمایش پلن فعال */}
      <section className={styles.planCard}>
        <div>
          <span>پلن فعال فضای کاری</span>
          <h2>{currentPlan.display_name ?? currentPlan.name ?? "بدون پلن"}</h2>
          <p>
            پایان اشتراک:{" "}
            {workspace.expires_at
              ? new Date(workspace.expires_at).toLocaleDateString("fa-IR")
              : "دائمی / نامحدود"}
          </p>
        </div>
        <div className={styles.badge}>فعال</div>
      </section>

      <section className={styles.stats}>
        <Stat
          title="کل توکن"
          value={currentPlan.token_limit?.toLocaleString()}
        />
        <Stat title="مصرف شده" value={wallet.used_tokens?.toLocaleString()} />
        <Stat
          title="باقی مانده"
          value={wallet.remaining_tokens?.toLocaleString()}
        />
      </section>

      <section className={styles.card}>
        <h2>امکانات Workspace</h2>
        <div className={styles.features}>
          {type.features?.map((item: string, index: number) => (
            <div key={index} className={styles.feature}>
              ✓ {item}
            </div>
          ))}
        </div>
      </section>

      <div
        style={{
          maxWidth: "1200px",
          margin: "40px auto 20px",
          position: "relative",
          zIndex: 2,
        }}
      >
        <h2>ابزارهای هوشمند دانشجویی</h2>
      </div>

      <section className={styles.grid} style={{ marginBottom: "40px" }}>
        {tools.map((tool) => (
          <Tool
            key={tool.path}
            icon={tool.icon}
            title={tool.title}
            desc={tool.desc}
            click={() => router.push(`/workspace/${id}/${tool.path}`)}
          />
        ))}
      </section>

      {/* بخش مدیریت و خرید پلن‌ها همراه با آیکون‌های اختصاصی */}
      <section className={styles.card} style={{ marginTop: "25px" }}>
        <h2 style={{ marginBottom: "10px" }}>ارتقا و خرید پلن‌های فضای کاری</h2>
        <p style={{ color: "#94a3b8", marginBottom: "25px" }}>
          پلن مناسب خود را انتخاب کنید تا توکن‌ها و امکانات پیشرفته فعال شوند.
        </p>

        <div className={styles.gridPlans}>
          {plans.map((plan) => {
            const isCurrent = currentPlan.id === plan.id;
            const isBusy = upgradingId === plan.id;
            const planIcon = getPlanIcon(plan.name);

            return (
              <div
                key={plan.id}
                className={`${styles.planItemCard} ${isCurrent ? styles.activePlanBorder : ""}`}
              >
                <div>
                  {/* هدر کارت پلن شامل آیکون و بج وضعیت */}
                  <div
                    style={{
                      display: "flex",
                      justifyContent: "space-between",
                      alignItems: "center",
                      marginBottom: "16px",
                    }}
                  >
                    <div className={styles.planIconBox}>{planIcon}</div>
                    {isCurrent && (
                      <span className={styles.currentBadge}>پلن فعلی</span>
                    )}
                  </div>

                  <h3 style={{ margin: "0 0 8px 0", fontSize: "20px" }}>
                    {plan.display_name ?? plan.name}
                  </h3>

                  <p className={styles.planPrice}>
                    {plan.price > 0
                      ? `${Number(plan.price).toLocaleString()} تومان`
                      : "رایگان"}
                  </p>

                  <p
                    style={{
                      fontSize: "14px",
                      color: "#94a3b8",
                      marginBottom: "15px",
                    }}
                  >
                    سقف توکن:{" "}
                    <strong style={{ color: "#fff" }}>
                      {plan.token_limit?.toLocaleString()}
                    </strong>
                  </p>

                  <div className={styles.planFeaturesList}>
                    {plan.features && plan.features.length > 0 ? (
                      plan.features.map((feat: string, idx: number) => (
                        <div key={idx} style={{ marginBottom: "6px" }}>
                          ✓ {feat}
                        </div>
                      ))
                    ) : (
                      <div style={{ color: "#64748b", fontSize: "13px" }}>
                        دسترسی به قابلیت‌های این سطح
                      </div>
                    )}
                  </div>
                </div>

                <button
                  disabled={isCurrent || isBusy}
                  onClick={() => handleUpgrade(plan.id)}
                  className={styles.planButton}
                  style={{
                    background: isCurrent
                      ? "rgba(255, 255, 255, 0.1)"
                      : "linear-gradient(135deg, #2563eb, #7c3aed)",
                    color: isCurrent ? "#94a3b8" : "#fff",
                    cursor: isCurrent ? "not-allowed" : "pointer",
                  }}
                >
                  {isCurrent
                    ? "پلن فعلی شما"
                    : isBusy
                      ? "در حال انتقال..."
                      : "انتخاب و خرید"}
                </button>
              </div>
            );
          })}
        </div>
      </section>
    </div>
  );
}

function Stat({ title, value }: any) {
  return (
    <div>
      <span>{title}</span>
      <strong>{value ?? 0}</strong>
    </div>
  );
}

function Tool({ icon, title, desc, click }: any) {
  return (
    <div>
      <div style={{ fontSize: "32px", marginBottom: "12px" }}>{icon}</div>
      <h3>{title}</h3>
      <p>{desc}</p>
      <button onClick={click}>ورود</button>
    </div>
  );
}
