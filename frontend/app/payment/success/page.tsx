// app/payment/success/page.tsx
import Link from "next/link";
import { CheckCircle, Calendar, Clock, Zap, ArrowLeft, Hash } from "lucide-react";
import styles from "./PaymentSuccess.module.css";

// تعریف تایپ‌ها (همون قبلی)
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

// کامپوننت اصلی - توجه به searchParams
export default async function PaymentSuccessPage({
  searchParams,
}: {
  searchParams: Promise<{ [key: string]: string | string[] | undefined }>
}) {
  // منتظر می‌مونیم تا پارامترها بیایند
  const params = await searchParams;
  const refId = params.ref_id as string;
  const authority = params.authority as string;

  // اگر کد پیگیری وجود نداشت
  if (!refId && !authority) {
    return (
      <div className={styles.container}>
        <div className={styles.card}>
          <div className={styles.errorIcon}>⚠️</div>
          <h1 className={styles.errorTitle}>خطا</h1>
          <p className={styles.errorMessage}>کد پیگیری یافت نشد</p>
          <Link href="/" className={styles.secondaryButton}>بازگشت به سایت</Link>
        </div>
      </div>
    );
  }

  // دریافت اطلاعات از API
  const apiUrl = process.env.NEXT_PUBLIC_API_URL || "https://accountsara.ir";
  let subscription = null;
  let error = null;

  try {
    const res = await fetch(
      `${apiUrl}/api/payment/status?ref_id=${refId || ''}&authority=${authority || ''}`,
      { cache: 'no-store' } // این باعث می‌شه همیشه اطلاعات جدید گرفته بشه
    );
    const data = await res.json();

    if (!data.success) {
      error = data.message || "اطلاعات پرداخت یافت نشد";
    } else {
      subscription = data.subscription;
    }
  } catch (err) {
    error = "مشکلی در ارتباط با سرور پیش آمد.";
  }

  // اگر خطا داشتیم
  if (error) {
    return (
      <div className={styles.container}>
        <div className={styles.card}>
          <div className={styles.errorIcon}>⚠️</div>
          <h1 className={styles.errorTitle}>خطا</h1>
          <p className={styles.errorMessage}>{error}</p>
          <Link href="/" className={styles.secondaryButton}>بازگشت به سایت</Link>
        </div>
      </div>
    );
  }

  // اگر اطلاعات نیامد
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

  // ---------- نمایش اطلاعات موفقیت (همون کد قبلی) ----------
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
        {/* هدر */}
        <div className={styles.header}>
          <div className={styles.iconWrapper}>
            <CheckCircle size={72} className={styles.successIcon} />
          </div>
          <h1 className={styles.title}>🎉 پرداخت موفق</h1>
          <p className={styles.subtitle}>اشتراک شما با موفقیت فعال شد</p>
        </div>

        {/* کد پیگیری */}
        <div className={styles.trackingBox}>
          <Hash size={18} className={styles.trackingIcon} />
          <span className={styles.trackingLabel}>کد پیگیری:</span>
          <span className={styles.trackingCode}>{refId || subscription.transaction_id}</span>
        </div>

        {/* اطلاعات اشتراک */}
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

        {/* تاریخ‌ها */}
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

        {/* ویژگی‌ها */}
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

        {/* دکمه‌ها */}
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