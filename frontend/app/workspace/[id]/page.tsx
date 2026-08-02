"use client";

import { useEffect, useState } from "react";
import { useParams, useRouter } from "next/navigation";
import styles from "./page.module.css";
import {
  getWorkspace,
  getWorkspacePlans,
  initiateWorkspacePayment,
} from "@/lib/api";
import Header from "@/components/Common/header/Header";
import Footer from "@/components/Common/Footer.tsx/Footer";

// نقشه پیش‌فرض برای ابزارهای استاندارد (Fallback)
const allToolsMap: Record<string, { icon: string; title: string; desc: string; path: string }> = {
  document: {
    icon: "📄",
    title: "Documents",
    desc: "آپلود و تحلیل جزوه و فایل‌های PDF",
    path: "documents",
  },
  documents: {
    icon: "📄",
    title: "Documents",
    desc: "آپلود و تحلیل جزوه و فایل‌های PDF",
    path: "documents",
  },
  library: {
    icon: "📚",
    title: "Paper Library",
    desc: "مدیریت و جستجوی مقالات علمی",
    path: "library",
  },
  summarizer: {
    icon: "📝",
    title: "Summarizer",
    desc: "خلاصه‌سازی متن و استخراج نکات مهم",
    path: "summarizer",
  },
  solver: {
    icon: "🧮",
    title: "AI Solver",
    desc: "حل تمرین و توضیح مرحله به مرحله",
    path: "solver",
  },
  research: {
    icon: "🔍",
    title: "Research",
    desc: "تحقیق، مقاله و جمع‌آوری منابع",
    path: "research",
  },
  memory: {
    icon: "🧠",
    title: "Memory",
    desc: "حافظه هوشمند دانشجویی",
    path: "memory",
  },
  chat: {
    icon: "💬",
    title: "Chat",
    desc: "گفتگو با هوش مصنوعی",
    path: "chat",
  },
};

function getPlanIcon(planName: string) {
  const name = (planName || "").toLowerCase();
  if (name.includes("advanced") || name.includes("پیشرفته")) return "⚡";
  if (name.includes("pro") || name.includes("حرفه‌ای")) return "💎";
  return "🌱";
}

export default function WorkspacePage() {
  const params = useParams();
  const router = useRouter();
  const id = Number(params?.id);

  const [workspaceData, setWorkspaceData] = useState<any>(null);
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
      setWorkspaceData(wsData);
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

  if (!workspaceData) {
    return <div className={styles.loading}>Workspace پیدا نشد</div>;
  }

  // استخراج ساختارها بر اساس خروجی‌های مختلف احتمالی بک‌اند
  const workspace = workspaceData.workspace ?? workspaceData;
  const type = workspace.workspace_type ?? {};
  
  // استخراج ایمن پلن فعال و اطلاعات اشتراک از ساختارهای مختلف
  const currentPlan = 
    workspaceData.plan ?? 
    workspace.plan ?? 
    workspaceData.subscription?.workspace_plan ?? 
    workspace.subscription?.workspace_plan ?? 
    workspace.workspace_subscriptions?.[0]?.workspace_plan ?? 
    {};

  const activeSubscription = 
    workspaceData.subscription ?? 
    workspace.subscription ?? 
    workspace.workspace_subscriptions?.[0] ?? 
    {};

  const wallet = workspaceData.wallet ?? workspace.wallet ?? {
    total_tokens: 0,
    used_tokens: 0,
    remaining_tokens: 0,
  };

  // پردازش و نرمال‌سازی ابزارها
  const workspaceFeatures = Array.isArray(type.features) ? type.features : [];
  const activeTools = workspaceFeatures
    .map((feat: any) => {
      if (typeof feat === "object" && feat !== null) {
        return {
          icon: feat.icon || "🧩",
          title: feat.title || feat.key,
          desc: feat.desc || "ابزار هوشمند فضای کاری",
          path: feat.path || feat.key,
        };
      }
      if (typeof feat === "string") {
        const lowerKey = feat.toLowerCase();
        if (allToolsMap[lowerKey]) {
          return allToolsMap[lowerKey];
        }
        return {
          icon: "🧩",
          title: feat,
          desc: "ابزار هوشمند فضای کاری",
          path: lowerKey.replace(/\s+/g, "_"),
        };
      }
      return null;
    })
    .filter(Boolean);

  return (
    <div className={styles.container}>
      <Header/>

      {/* بخش نمایش پلن فعال */}
      <section className={styles.planCard}>
        <div>
          <span>پلن فعال فضای کاری</span>
          <h2>
            {currentPlan.display_name ?? currentPlan.name ?? "پلن رایگان / بدون پلن فعال"}
          </h2>
          <p>
            پایان اشتراک:{" "}
            {activeSubscription.expires_at
              ? new Date(activeSubscription.expires_at).toLocaleDateString("fa-IR")
              : workspace.expires_at
              ? new Date(workspace.expires_at).toLocaleDateString("fa-IR")
              : "دائمی / نامحدود"}
          </p>
        </div>
        <div className={styles.badge}>فعال</div>
      </section>

      <section className={styles.stats}>
        <Stat title="کل توکن" value={currentPlan.token_limit?.toLocaleString()} />
        <Stat title="مصرف شده" value={wallet.used_tokens?.toLocaleString()} />
        <Stat title="باقی مانده" value={wallet.remaining_tokens?.toLocaleString()} />
      </section>

      {/* نمایش ابزارهای هوشمند فیلترشده */}
      <div style={{ maxWidth: "1200px", margin: "40px auto 20px", position: "relative", zIndex: 2 }}>
        <h2>ابزارهای هوشمند این فضا</h2>
      </div>

      <section className={styles.grid} style={{ marginBottom: "40px" }}>
        {activeTools.length > 0 ? (
          activeTools.map((tool: any) => (
            <Tool
              key={tool.path}
              icon={tool.icon}
              title={tool.title}
              desc={tool.desc}
              click={() => router.push(`/workspace/${id}/${tool.path}`)}
            />
          ))
        ) : (
          <p style={{ color: "#94a3b8", gridColumn: "1 / -1" }}>هیچ ابزاری برای این فضای کاری فعال نشده است.</p>
        )}
      </section>

      {/* بخش ارتقا و خرید پلن‌ها */}
      <section className={styles.card} style={{ marginTop: "25px" }}>
        <h2 style={{ marginBottom: "10px" }}>ارتقا و خرید پلن‌های فضای کاری</h2>
        <p style={{ color: "#94a3b8", marginBottom: "25px" }}>
          پلن مناسب خود را انتخاب کنید تا توکن‌ها و امکانات پیشرفته فعال شوند.
        </p>

        <div className={styles.gridPlans}>
          {plans.map((plan) => {
            const isCurrent = 
              (currentPlan.id === plan.id) || 
              (activeSubscription.workspace_plan_id === plan.id);
            const isBusy = upgradingId === plan.id;
            const planIcon = getPlanIcon(plan.name);

            return (
              <div key={plan.id} className={`${styles.planItemCard} ${isCurrent ? styles.activePlanBorder : ""}`}>
                <div>
                  <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center", marginBottom: "16px" }}>
                    <div className={styles.planIconBox}>{planIcon}</div>
                    {isCurrent && <span className={styles.currentBadge}>پلن فعلی</span>}
                  </div>

                  <h3 style={{ margin: "0 0 8px 0", fontSize: "20px" }}>
                    {plan.display_name ?? plan.name}
                  </h3>
                  <p className={styles.planPrice}>
                    {plan.price > 0 ? `${Number(plan.price).toLocaleString()} تومان` : "رایگان"}
                  </p>
                  <p style={{ fontSize: "14px", color: "#94a3b8", marginBottom: "15px" }}>
                    سقف توکن: <strong style={{ color: "#fff" }}>{plan.token_limit?.toLocaleString()}</strong>
                  </p>
                </div>

                <button
                  disabled={isCurrent || isBusy}
                  onClick={() => handleUpgrade(plan.id)}
                  className={styles.planButton}
                  style={{
                    background: isCurrent ? "rgba(255, 255, 255, 0.1)" : "linear-gradient(135deg, #2563eb, #7c3aed)",
                    color: isCurrent ? "#94a3b8" : "#fff",
                    cursor: isCurrent ? "not-allowed" : "pointer",
                  }}
                >
                  {isCurrent ? "پلن فعلی شما" : isBusy ? "در حال انتقال..." : "انتخاب و خرید"}
                </button>
              </div>
            );
          })}
        </div>
      </section>
      <Footer/>
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
    <div className={styles.toolCard}>
      <div style={{ fontSize: "32px", marginBottom: "12px" }}>{icon}</div>
      <h3>{title}</h3>
      <p>{desc}</p>
      <button onClick={click}>ورود</button>
    </div>
  );
}