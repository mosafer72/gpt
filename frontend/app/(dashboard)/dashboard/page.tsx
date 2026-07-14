"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";

import styles from "./page.module.css";
import TokenUsageCard from "@/components/Common/TokenUsageCard";

import { getDashboardData, initiatePayment } from "@/lib/api";

import {Plan,Subscription} from "@/types";

export default function DashboardPage() {
  const router = useRouter();

  const [plans, setPlans] = useState<Plan[]>([]);

  const [subscription, setSubscription] = useState<Subscription | null>(null);

  const [selectedPlan, setSelectedPlan] = useState<Plan | null>(null);

  const [user, setUser] = useState<any>(null);

  const [loading, setLoading] = useState(true);

  const [processing, setProcessing] = useState(false);

  const [error, setError] = useState("");

  async function load() {
    try {
      const userData = localStorage.getItem("user");

      if (userData) {
        setUser(JSON.parse(userData));
      }

      const data = await getDashboardData();

      setPlans(data.plans);

      setSubscription(data.subscription);
    } catch (error) {
      console.log(error);

      setError("خطا در دریافت اطلاعات");
    } finally {
      setLoading(false);
    }
  }
  useEffect(() => {
    load();
  }, []);

  function selectPlan(plan: Plan) {
    setSelectedPlan(plan);

    setTimeout(() => {
      document.getElementById("checkout")?.scrollIntoView({
        behavior: "smooth",
      });
    }, 100);
  }

  async function payment() {
    if (!selectedPlan) return;

    try {
      setProcessing(true);

      const data = await initiatePayment(selectedPlan.id);

      if (data.payment_url) {
        window.location.href = data.payment_url;
      }
    } catch (error) {
      console.log(error);

      setError("خطا در پرداخت");
    } finally {
      setProcessing(false);
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

  const currentPlan = subscription?.subscription?.plan;

  return (
    <div className={styles.container}>
      <div className={styles.backgroundGlow} />

      <header className={styles.header}>
        <div className={styles.brand}>
          <div className={styles.logoIcon}>AI</div>

          <div>
            <h1>اکانتسرا</h1>

            <p>پنل مدیریت هوش مصنوعی</p>
          </div>
        </div>

        <div className={styles.userBox}>
          <strong>{user?.name || "کاربر"}</strong>

          <span>{user?.phone}</span>
        </div>
      </header>

      <main className={styles.content}>
        <section className={styles.heroCard}>
          <div>
            <span>اشتراک فعال</span>

            <h2>{currentPlan ? currentPlan.display_name : "بدون اشتراک"}</h2>
          </div>

          <div className={styles.badge}>{currentPlan ? "فعال" : "رایگان"}</div>
        </section>

        <TokenUsageCard />

        <section>
          <div className={styles.sectionTitle}>
            <h2>انتخاب پلن</h2>

            <p>مدل مناسب خودت را انتخاب کن</p>
          </div>

          <div className={styles.grid}>
            {plans.map((plan) => {
              const isCurrent = currentPlan?.id === plan.id;

              const isSelected = selectedPlan?.id === plan.id;

              return (
                <div
                  key={plan.id}
                  className={`
                ${styles.planCard}
                ${isCurrent ? styles.activePlan : ""}
                ${isSelected ? styles.selected : ""}
                `}
                >
                  {isCurrent && (
                    <div className={styles.currentRibbon}>اشتراک فعال شما</div>
                  )}

                  <h3>{plan.display_name}</h3>

                  <div className={styles.price}>
                    {Number(plan.price) === 0
                      ? "رایگان"
                      : `${(Number(plan.price) / 10).toLocaleString()} تومان`}
                  </div>

                  <div className={styles.token}>
                    🪙
                    {plan.token_limit.toLocaleString()}
                    توکن
                  </div>

                  <div className={styles.modelsBox}>
                    <div className={styles.modelsTitle}>🤖 مدل‌ها</div>

                    {plan.models?.map((model) => (
                      <div key={model.id} className={styles.modelBadge}>
                        {model.name}

                        <small>{model.provider}</small>
                      </div>
                    ))}
                  </div>

                  <div className={styles.featuresBox}>
                    {plan.features?.map((feature, index) => (
                      <div key={index} className={styles.featureBadge}>
                        ✓ {feature}
                      </div>
                    ))}
                  </div>

                  {isCurrent ? (
                    <button disabled className={styles.activeButton}>
                      ✓ پلن فعلی شما
                    </button>
                  ) : (
                    <button
                      className={styles.selectButton}
                      disabled={processing}
                      onClick={() => selectPlan(plan)}
                    >
                      انتخاب پلن
                    </button>
                  )}
                </div>
              );
            })}
          </div>
        </section>

        {selectedPlan && (
          <section id="checkout" className={styles.checkout}>
            <h2>تکمیل خرید</h2>

            <strong>{selectedPlan.display_name}</strong>

            <div>
              {(Number(selectedPlan.price) / 10).toLocaleString()}
              تومان
            </div>

            <button onClick={payment} disabled={processing}>
              {processing ? "در حال اتصال..." : "پرداخت و فعال سازی"}
            </button>
          </section>
        )}

        <div className={styles.actions}>
          <button onClick={() => router.push("/chat")}>💬 ورود به چت</button>

          <button onClick={logout}>🚪 خروج</button>
        </div>
      </main>
    </div>
  );
}
