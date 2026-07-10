// app/payment/failed/page.tsx
import Link from "next/link";
import { XCircle, AlertTriangle, ArrowLeft, Hash, RefreshCw } from "lucide-react";
import styles from "./PaymentFailed.module.css";

export default async function PaymentFailedPage({
  searchParams,
}: {
  searchParams: Promise<{ [key: string]: string | string[] | undefined }>
}) {
  const params = await searchParams;
  const refId = (params.ref_id as string) || (params.authority as string) || null;
  
  let errorMessage: string;
  if (typeof params.message === "string") {
    errorMessage = params.message;
  } else if (params.status === "canceled") {
    errorMessage = "پرداخت توسط کاربر لغو شد";
  } else {
    errorMessage = "مشکلی در انجام پرداخت پیش آمد";
  }

  const statusCode = typeof params.status === "string" ? params.status : null;

  return (
    <div className={styles.container}>
      <div className={styles.card}>
        <div className={styles.header}>
          <div className={styles.iconWrapper}>
            <XCircle size={72} className={styles.failedIcon} />
          </div>
          <h1 className={styles.title}>😔 پرداخت ناموفق</h1>
          <p className={styles.subtitle}>
            متأسفانه پرداخت شما با مشکل مواجه شد
          </p>
        </div>

        <div className={styles.errorBox}>
          <AlertTriangle size={20} className={styles.errorBoxIcon} />
          <div>
            <div className={styles.errorBoxTitle}>دلیل خطا</div>
            <div className={styles.errorBoxMessage}>{errorMessage}</div>
          </div>
        </div>

        {refId && (
          <div className={styles.trackingBox}>
            <Hash size={18} className={styles.trackingIcon} />
            <span className={styles.trackingLabel}>کد پیگیری:</span>
            <span className={styles.trackingCode}>{refId}</span>
            {statusCode && (
              <span className={styles.statusBadge}>کد وضعیت: {statusCode}</span>
            )}
          </div>
        )}

        <div className={styles.helpBox}>
          <h3>❓ چه کاری می‌توانم انجام دهم؟</h3>
          <ul>
            <li>اطلاعات کارت یا درگاه پرداخت را بررسی کنید</li>
            <li>از اتصال اینترنت خود مطمئن شوید</li>
            <li>در صورت کسر وجه، تا ۴۸ ساعت به حساب شما بازمی‌گردد</li>
            <li>با پشتیبانی تماس بگیرید تا مشکل را بررسی کنیم</li>
          </ul>
        </div>

        <div className={styles.actions}>
          <Link href="/payment" className={styles.primaryButton}>
            <RefreshCw size={18} />
            تلاش مجدد
          </Link>
          <Link href="/support" className={styles.secondaryButton}>
            تماس با پشتیبانی
          </Link>
          <Link href="/" className={styles.ghostButton}>
            بازگشت به صفحه اصلی
          </Link>
        </div>
      </div>
    </div>
  );
}