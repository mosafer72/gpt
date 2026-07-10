// pages/payment/success.tsx
import { GetServerSideProps } from "next";
import Link from "next/link";
import { CheckCircle, Calendar, Clock, Zap, ArrowLeft, Hash } from "lucide-react";
import styles from "./PaymentSuccess.module.css";

interface Plan {
  id: number;
  name: string;
  display_name: string;
  model: string;
  price: number;
  duration_days: number;
  daily_message_limit: number;
  features: string[];
}

interface Subscription {
  id: number;
  user_id: number;
  plan_id: number;
  started_at: string;
  expires_at: string;
  is_active: boolean;
  transaction_id: number;
  plan: Plan;
}

interface SuccessPageProps {
  subscription: Subscription | null;
  refId: string | null;
  error: string | null;
}

export default function PaymentSuccess({ subscription, refId, error }: SuccessPageProps) {
  if (error) {
    return (
      <div className={styles.container}>
        <div className={styles.card}>
          <div className={styles.errorIcon}>⚠️</div>
          <h1 className={styles.errorTitle}>خطا</h1>
          <p className={styles.errorMessage}>{error}</p>
          <Link href="/" className={styles.secondaryButton}>
            بازگشت به سایت
          </Link>
        </div>
      </div>
    );
  }

  if (!subscription) {
    return (
      <div className={styles.container}>
        <div className={styles.card}>
          <div className={styles.loadingSpinner} />
          <p className={styles.loadingText}>در حال بارگذاری اطلاعات پرداخت...</p>
        </div>
      </div>
    );
  }

  const { plan } = subscription;
  const daysRemaining = Math.ceil(
    (new Date(subscription.expires_at).getTime() - new Date().getTime()) / (1000 * 60 * 60 * 24)
  );

  const formatDate = (dateStr: string) => {
    return new Date(dateStr).toLocaleDateString("fa-IR", {
      year: "numeric",
      month: "long",
      day: "numeric",
      hour: "2-digit",
      minute: "2-digit",
    });
  };

  return (
    <div className={styles.container}>
      <div className={styles.card}>
        {/* Header */}
        <div className={styles.header}>
          <div className={styles.iconWrapper}>
            <CheckCircle size={72} className={styles.successIcon} />
          </div>
          <h1 className={styles.title}>🎉 پرداخت موفق</h1>
          <p className={styles.subtitle}>اشتراک شما با موفقیت فعال شد</p>
        </div>

        {/* Tracking Code */}
        <div className={styles.trackingBox}>
          <Hash size={18} className={styles.trackingIcon} />
          <span className={styles.trackingLabel}>کد پیگیری:</span>
          <span className={styles.trackingCode}>{refId || subscription.transaction_id}</span>
        </div>

        {/* Subscription Info */}
        <div className={styles.section}>
          <div className={styles.sectionHeader}>
            <Zap size={20} className={styles.sectionIcon} />
            <h2>اطلاعات اشتراک</h2>
          </div>
          <div className={styles.infoGrid}>
            <div className={styles.infoItem}>
              <span className={styles.infoLabel}>طرح</span>
              <span className={styles.infoValue}>{plan.display_name}</span>
            </div>
            <div className={styles.infoItem}>
              <span className={styles.infoLabel}>مدل</span>
              <span className={styles.infoValue}>{plan.model}</span>
            </div>
            <div className={styles.infoItem}>
              <span className={styles.infoLabel}>مبلغ</span>
              <span className={styles.infoValue}>{plan.price.toLocaleString()} تومان</span>
            </div>
            <div className={styles.infoItem}>
              <span className={styles.infoLabel}>پیام‌های روزانه</span>
              <span className={styles.infoValue}>{plan.daily_message_limit}</span>
            </div>
          </div>
        </div>

        {/* Dates */}
        <div className={styles.section}>
          <div className={styles.sectionHeader}>
            <Calendar size={20} className={styles.sectionIcon} />
            <h2>بازه فعال‌سازی</h2>
          </div>
          <div className={styles.dateGrid}>
            <div className={styles.dateItem}>
              <span className={styles.dateLabel}>شروع</span>
              <span className={styles.dateValue}>{formatDate(subscription.started_at)}</span>
            </div>
            <div className={styles.dateItem}>
              <span className={styles.dateLabel}>انقضا</span>
              <span className={styles.dateValue}>{formatDate(subscription.expires_at)}</span>
            </div>
          </div>
          <div className={styles.daysRemaining}>
            <Clock size={18} />
            <span>
              {daysRemaining > 0
                ? `${daysRemaining} روز تا پایان اشتراک`
                : "اشتراک امروز به پایان می‌رسد"}
            </span>
          </div>
        </div>

        {/* Features */}
        <div className={styles.section}>
          <div className={styles.sectionHeader}>
            <Zap size={20} className={styles.sectionIcon} />
            <h2>ویژگی‌های طرح</h2>
          </div>
          <div className={styles.featureList}>
            {plan.features.map((feature, idx) => (
              <div key={idx} className={styles.featureItem}>
                <span className={styles.featureBullet}>✓</span>
                {feature}
              </div>
            ))}
          </div>
        </div>

        {/* Actions */}
        <div className={styles.actions}>
          <Link href="/dashboard" className={styles.primaryButton}>
            رفتن به داشبورد
            <ArrowLeft size={18} />
          </Link>
          <Link href="/" className={styles.secondaryButton}>
            بازگشت به صفحه اصلی
          </Link>
        </div>
      </div>
    </div>
  );
}

export const getServerSideProps: GetServerSideProps = async (context) => {
  const { ref_id, authority } = context.query;

  if (!ref_id && !authority) {
    return {
      props: {
        subscription: null,
        refId: null,
        error: "کد پیگیری یافت نشد",
      },
    };
  }

  try {
    const apiUrl = process.env.NEXT_PUBLIC_API_URL || "https://accountsara.ir";
    const res = await fetch(
      `${apiUrl}/api/payment/status?ref_id=${ref_id || ""}&authority=${authority || ""}`
    );
    const data = await res.json();

    if (!data.success) {
      return {
        props: {
          subscription: null,
          refId: ref_id || authority || null,
          error: data.message || "اطلاعات پرداخت یافت نشد",
        },
      };
    }

    return {
      props: {
        subscription: data.subscription,
        refId: ref_id || authority || data.subscription.transaction_id,
        error: null,
      },
    };
  } catch (err) {
    return {
      props: {
        subscription: null,
        refId: ref_id || authority || null,
        error: "مشکل در ارتباط با سرور",
      },
    };
  }
};