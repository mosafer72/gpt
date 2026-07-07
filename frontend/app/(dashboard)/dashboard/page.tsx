'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import styles from './page.module.css';

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
  const [mySubscription, setMySubscription] = useState<SubscriptionInfo | null>(null);
  const [loading, setLoading] = useState(true);
  const [selectedPlan, setSelectedPlan] = useState<Plan | null>(null);
  const [upgradeCost, setUpgradeCost] = useState<number | null>(null);
  const [processing, setProcessing] = useState(false);
  const [error, setError] = useState<string | null>(null);

  // توابع کمکی برای مدیریت توکن
  const getToken = () => localStorage.getItem('auth_token');
  const redirectToLogin = () => {
    localStorage.removeItem('auth_token');
    localStorage.removeItem('user');
    document.cookie = 'auth_token=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT';
    router.push('/login');
  };

  const fetchWithAuth = async (url: string, options: RequestInit = {}) => {
    const token = getToken();
    if (!token) {
      redirectToLogin();
      throw new Error('No token');
    }
    const response = await fetch(url, {
      ...options,
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        ...options.headers,
      },
    });
    if (response.status === 401) {
      redirectToLogin();
      throw new Error('Unauthorized');
    }
    return response;
  };

  useEffect(() => {
    const token = getToken();
    if (!token) {
      redirectToLogin();
      return;
    }
    const userData = localStorage.getItem('user');
    if (userData) {
      try {
        setUser(JSON.parse(userData));
      } catch {
        // ignore
      }
    }
    fetchDashboardData();
  }, []);

  const fetchDashboardData = async () => {
    try {
      setError(null);
      // دریافت بسته‌ها
      const plansRes = await fetchWithAuth(`${process.env.NEXT_PUBLIC_API_URL}/plans`);
      const plansData = await plansRes.json();
      setPlans(plansData);

      // دریافت اشتراک فعلی
      const subRes = await fetchWithAuth(`${process.env.NEXT_PUBLIC_API_URL}/my-subscription`);
      const subData = await subRes.json();
      setMySubscription(subData);
    } catch (err: any) {
      if (err.message !== 'Unauthorized' && err.message !== 'No token') {
        setError('خطا در دریافت اطلاعات. لطفاً دوباره تلاش کنید.');
        console.error(err);
      }
    } finally {
      setLoading(false);
    }
  };

  const handleCalculateUpgrade = async (plan: Plan) => {
    try {
      setError(null);
      const res = await fetchWithAuth(`${process.env.NEXT_PUBLIC_API_URL}/calculate-upgrade`, {
        method: 'POST',
        body: JSON.stringify({ plan_id: plan.id }),
      });
      const data = await res.json();
      if (res.ok) {
        setSelectedPlan(plan);
        setUpgradeCost(data.amount_toman);
      } else {
        setError(data.error || 'خطا در محاسبه هزینه');
      }
    } catch (err: any) {
      if (err.message !== 'Unauthorized' && err.message !== 'No token') {
        setError('خطا در ارتباط با سرور');
      }
    }
  };

  const handlePayment = async () => {
    if (!selectedPlan) return;
    setProcessing(true);
    setError(null);
    try {
      const res = await fetchWithAuth(`${process.env.NEXT_PUBLIC_API_URL}/initiate-payment`, {
        method: 'POST',
        body: JSON.stringify({ plan_id: selectedPlan.id }),
      });
      const data = await res.json();
      if (res.ok && data.payment_url) {
        window.location.href = data.payment_url;
      } else {
        setError(data.error || 'خطا در شروع پرداخت');
      }
    } catch (err: any) {
      if (err.message !== 'Unauthorized' && err.message !== 'No token') {
        setError('خطا در ارتباط با سرور');
      }
    } finally {
      setProcessing(false);
    }
  };

  const handleLogout = async () => {
    const token = getToken();
    if (token) {
      try {
        await fetch(`${process.env.NEXT_PUBLIC_API_URL}/auth/logout`, {
          method: 'POST',
          headers: { 'Authorization': `Bearer ${token}` },
        });
      } catch (e) { /* ignore */ }
    }
    localStorage.removeItem('auth_token');
    localStorage.removeItem('user');
    document.cookie = 'auth_token=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT';
    router.push('/login');
  };

  if (loading) {
    return (
      <div className={styles.container}>
        <div className={styles.card}>
          <div className={styles.logo}>✨ نابغه</div>
          <p className={styles.loadingText}>در حال بارگذاری...</p>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className={styles.container}>
        <div className={styles.card}>
          <div className={styles.logo}>✨ نابغه</div>
          <div className={styles.errorBox}>
            <p className={styles.errorText}>{error}</p>
            <button onClick={() => window.location.reload()} className={styles.retryBtn}>
              تلاش مجدد
            </button>
          </div>
        </div>
      </div>
    );
  }

  const currentPlan = mySubscription?.subscription?.plan;
  const daysRemaining = mySubscription?.subscription?.days_remaining || 0;

  return (
    <div className={styles.container}>
      <div className={styles.card}>
        <div className={styles.logo}>✨ نابغه</div>
        <h1 className={styles.title}>
          خوش آمدید {user?.name || 'کاربر عزیز'} 👋
        </h1>
        <p className={styles.subtitle}>
          شماره موبایل: <strong>{user?.phone}</strong>
        </p>

        {/* بسته فعال */}
        <div className={styles.subscriptionBox}>
          <div className={styles.subscriptionHeader}>
            <span className={styles.subscriptionIcon}>📦</span>
            <span>بسته فعال</span>
          </div>
          {currentPlan ? (
            <>
              <div className={styles.planName}>{currentPlan.display_name}</div>
              <div className={styles.planDetails}>
                <span>مدل: {currentPlan.model}</span>
                <span>⏳ {daysRemaining} روز باقی‌مانده</span>
              </div>
            </>
          ) : (
            <div className={styles.noPlan}>هیچ بسته فعالی ندارید</div>
          )}
        </div>

        {/* لیست بسته‌ها */}
        <div className={styles.plansGrid}>
          <h3 className={styles.sectionTitle}>بسته‌های موجود</h3>
          {plans.map((plan) => {
            const isCurrent = currentPlan?.id === plan.id;
            const isUpgrading = selectedPlan?.id === plan.id;

            return (
              <div
                key={plan.id}
                className={`${styles.planCard} ${isCurrent ? styles.activePlan : ''} ${isUpgrading ? styles.selectedPlan : ''}`}
              >
                <div className={styles.planHeader}>
                  <span className={styles.planEmoji}>
                    {plan.name === 'free' && '🎁'}
                    {plan.name === 'basic' && '🌱'}
                    {plan.name === 'standard' && '🚀'}
                    {plan.name === 'premium' && '💎'}
                    {plan.name === 'enterprise' && '🏢'}
                  </span>
                  <div>
                    <div className={styles.planDisplayName}>{plan.display_name}</div>
                    <div className={styles.planModel}>{plan.model}</div>
                  </div>
                  <div className={styles.planPrice}>
                    {plan.price === 0 ? 'رایگان' : Math.floor(plan.price / 10).toLocaleString() + ' تومان'}
                  </div>
                </div>

                <ul className={styles.planFeatures}>
                  {plan.features.map((feature: string, idx: number) => (
                    <li key={idx}>✓ {feature}</li>
                  ))}
                </ul>

                {isCurrent ? (
                  <div className={styles.currentBadge}>✓ فعال</div>
                ) : (
                  <button
                    className={styles.upgradeBtn}
                    onClick={() => handleCalculateUpgrade(plan)}
                    disabled={processing}
                  >
                    {isUpgrading ? 'محاسبه...' : 'انتخاب'}
                  </button>
                )}
              </div>
            );
          })}
        </div>

        {/* نمایش هزینه ارتقا */}
        {selectedPlan && upgradeCost !== null && (
          <div className={styles.upgradeSummary}>
            <div className={styles.summaryRow}>
              <span>بسته انتخابی:</span>
              <strong>{selectedPlan.display_name}</strong>
            </div>
            <div className={styles.summaryRow}>
              <span>هزینه قابل پرداخت:</span>
              <strong className={styles.priceHighlight}>
                {upgradeCost === 0 ? 'رایگان' : upgradeCost.toLocaleString() + ' تومان'}
              </strong>
            </div>
            {mySubscription?.has_subscription && (
              <div className={styles.summaryRow}>
                <span>تخفیف بر اساس روزهای باقی‌مانده:</span>
                <span className={styles.discountBadge}>✔ اعمال شد</span>
              </div>
            )}
            <button
              className={styles.payBtn}
              onClick={handlePayment}
              disabled={processing || upgradeCost === 0}
            >
              {processing ? 'در حال اتصال به درگاه...' : upgradeCost === 0 ? 'ارتقا رایگان' : 'پرداخت و ارتقا'}
            </button>
            {upgradeCost === 0 && (
              <p className={styles.freeNote}>🎉 این ارتقا رایگان است!</p>
            )}
          </div>
        )}

        <div className={styles.divider} />

        <div className={styles.actionButtons}>
          <button onClick={() => router.push('/chat')} className={styles.primaryBtn}>
            💬 شروع مکالمه
          </button>
          <button onClick={handleLogout} className={styles.logoutBtn}>
            🚪 خروج
          </button>
        </div>
      </div>
    </div>
  );
}