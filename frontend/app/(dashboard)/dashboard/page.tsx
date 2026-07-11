"use client";

import { useEffect, useState } from "react";

import { useRouter } from "next/navigation";

import styles from "./page.module.css";

interface Plan {
  id: number;

  name: string;

  display_name: string;

  model: string;

  price: number;

  price_in_tomans: number;

  duration_days: number;

  features: string[];
}

interface SubscriptionInfo {
  has_subscription: boolean;

  subscription?: {
    id: number;

    plan: Plan;

    started_at: string;

    expires_at: string;

    days_remaining: number;

    is_valid: boolean;
  };
}

export default function DashboardPage() {
  const router = useRouter();

  const [user, setUser] = useState<any>(null);

  const [plans, setPlans] = useState<Plan[]>([]);

  const [mySubscription, setMySubscription] = useState<SubscriptionInfo | null>(
    null,
  );

  const [loading, setLoading] = useState(true);

  const [selectedPlan, setSelectedPlan] = useState<Plan | null>(null);

  const [upgradeCost, setUpgradeCost] = useState<number | null>(null);

  const [processing, setProcessing] = useState(false);

  const [error, setError] = useState<string | null>(null);

  const getToken = () => localStorage.getItem("auth_token");

  const redirectToLogin = () => {
    localStorage.removeItem("auth_token");

    localStorage.removeItem("user");

    document.cookie =
      "auth_token=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT";

    router.push("/login");
  };

  const fetchWithAuth = async (url: string, options: RequestInit = {}) => {
    const token = getToken();

    if (!token) {
      redirectToLogin();

      throw new Error("No token");
    }

    const response = await fetch(url, {
      ...options,

      headers: {
        Authorization: `Bearer ${token}`,

        "Content-Type": "application/json",

        Accept: "application/json",

        ...options.headers,
      },
    });

    if (response.status === 401) {
      redirectToLogin();

      throw new Error("Unauthorized");
    }

    return response;
  };

  useEffect(() => {
    const token = getToken();

    if (!token) {
      redirectToLogin();

      return;
    }

    const userData = localStorage.getItem("user");

    if (userData) {
      try {
        setUser(JSON.parse(userData));
      } catch {}
    }

    fetchDashboardData();
  }, []);

  const fetchDashboardData = async () => {
    try {
      setError(null);

      const plansRes = await fetchWithAuth(
        `${process.env.NEXT_PUBLIC_API_URL}/plans`,
      );

      const plansData = await plansRes.json();

      setPlans(plansData);

      const subRes = await fetchWithAuth(
        `${process.env.NEXT_PUBLIC_API_URL}/my-subscription`,
      );

      const subData = await subRes.json();

      setMySubscription(subData);
    } catch (err: any) {
      if (err.message !== "Unauthorized" && err.message !== "No token") {
        setError("خطا در دریافت اطلاعات. لطفاً دوباره تلاش کنید.");
      }
    } finally {
      setLoading(false);
    }
  };

  const handleCalculateUpgrade = async (plan: Plan) => {
    try {
      setSelectedPlan(plan);

      setUpgradeCost(Math.floor(plan.price / 10));
    } catch {
      setError("خطا در محاسبه هزینه");
    }
  };
  const handlePayment = async () => {
    if (!selectedPlan) return;

    setProcessing(true);

    try {
      const res = await fetchWithAuth(
        `${process.env.NEXT_PUBLIC_API_URL}/initiate-payment`,
        {
          method: "POST",

          body: JSON.stringify({ plan_id: selectedPlan.id }),
        },
      );

      const data = await res.json();

      if (res.ok && data.payment_url) {
        window.location.href = data.payment_url;
      } else {
        setError(data.error || "خطا در شروع پرداخت");
      }
    } finally {
      setProcessing(false);
    }
  };

  const handleLogout = () => {
    localStorage.removeItem("auth_token");

    localStorage.removeItem("user");

    router.push("/login");
  };

  if (loading) {
    return <div className={styles.loading}>در حال بارگذاری...</div>;
  }

  const currentPlan = mySubscription?.subscription?.plan;

  const daysRemaining = mySubscription?.subscription?.days_remaining || 0;

  return (
    <div className={styles.container}>
      <div className={styles.backgroundGlow} />

      <header className={styles.header}>
        <div className={styles.brand}>
          <div className={styles.logoIcon}>AI</div>

          <div>
            <h1>اکانتسرا</h1>

            <p>پنل مدیریت اشتراک هوش مصنوعی</p>
          </div>
        </div>

        <div className={styles.userInfo}>
          <span>{user?.name || "کاربر"}</span>

          <small>{user?.phone}</small>
        </div>
      </header>

      <main className={styles.content}>
        <section className={styles.subscriptionCard}>
          <div className={styles.subscriptionTop}>
            <div>
              <span className={styles.subscriptionLabel}>بسته فعال</span>

              <h2>{currentPlan ? currentPlan.display_name : "بدون اشتراک"}</h2>
            </div>

            <div className={styles.subscriptionBadge}>
              {currentPlan ? "فعال" : "غیرفعال"}
            </div>
          </div>

          {currentPlan ? (
            <>
              <div className={styles.subscriptionMeta}>
                <div>
                  <span>مدل</span>

                  <strong>{currentPlan.model}</strong>
                </div>

                <div>
                  <span>روز باقی‌مانده</span>

                  <strong>{daysRemaining} روز</strong>
                </div>
              </div>

              <div className={styles.progress}>
                <div
                  className={styles.progressFill}
                  style={{
                    width: `${Math.min((daysRemaining / currentPlan.duration_days) * 100, 100)}%`,
                  }}
                />
              </div>
            </>
          ) : (
            <p className={styles.noSubscription}>
              هنوز اشتراکی فعال نکرده‌اید.
            </p>
          )}
        </section>

        <section className={styles.plansSection}>
          <div className={styles.sectionHeader}>
            <h2>پلن‌های موجود</h2>

            <p>پلن مناسب خود را انتخاب کنید</p>
          </div>

          <div className={styles.plansGrid}>
            {plans.map((plan) => {
              const isCurrent = currentPlan?.id === plan.id;

              const isSelected = selectedPlan?.id === plan.id;

              return (
                <div
                  key={plan.id}
                  className={`${styles.planCard} ${isCurrent ? styles.activePlan : ""} ${isSelected ? styles.selectedPlan : ""}`}
                >
                  {isCurrent && (
                    <div className={styles.currentRibbon}>پلن فعال</div>
                  )}

                  <div className={styles.planIcon}>
                    {plan.name === "free" && "🎁"}

                    {plan.name === "basic" && "🌱"}

                    {plan.name === "standard" && "🚀"}

                    {plan.name === "premium" && "💎"}

                    {plan.name === "enterprise" && "🏢"}
                  </div>

                  <h3>{plan.display_name}</h3>

                  <p className={styles.planModel}>{plan.model}</p>

                  <div className={styles.planPrice}>
                    {plan.price === 0
                      ? "رایگان"
                      : `${Math.floor(plan.price / 10).toLocaleString()} تومان`}
                  </div>

                  <ul className={styles.features}>
                    {plan.features.map((feature, idx) => (
                      <li key={idx}>✓ {feature}</li>
                    ))}
                  </ul>

                  {isCurrent ? (
                    <button className={styles.activeButton}>فعال</button>
                  ) : (
                    <button
                      className={styles.selectButton}
                      onClick={() => handleCalculateUpgrade(plan)}
                      disabled={processing}
                    >
                      انتخاب پلن
                    </button>
                  )}
                </div>
              );
            })}
          </div>
        </section>

        {selectedPlan && upgradeCost !== null && (
          <section className={styles.checkoutCard}>
            <div className={styles.checkoutRow}>
              <span>پلن انتخابی</span>

              <strong>{selectedPlan.display_name}</strong>
            </div>

            <div className={styles.checkoutRow}>
              <span>مبلغ قابل پرداخت</span>

              <strong className={styles.checkoutPrice}>
                {upgradeCost === 0
                  ? "رایگان"
                  : `${upgradeCost.toLocaleString()} تومان`}
              </strong>
            </div>

            <button
              className={styles.payButton}
              onClick={handlePayment}
              disabled={processing}
            >
              {processing ? "در حال اتصال به درگاه..." : "پرداخت و ارتقا"}
            </button>
          </section>
        )}

        <div className={styles.actions}>
          <button
            onClick={() => router.push("/chat")}
            className={styles.chatButton}
          >
            💬 ورود به چت
          </button>

          <button onClick={handleLogout} className={styles.logoutButton}>
            🚪 خروج
          </button>
        </div>
      </main>
    </div>
  );
}
