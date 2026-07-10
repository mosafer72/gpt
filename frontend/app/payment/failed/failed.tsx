// pages/payment/failed.tsx
import { GetServerSideProps } from "next";
import Link from "next/link";
import { XCircle, AlertTriangle, ArrowLeft, Hash, RefreshCw } from "lucide-react";
import styles from "./PaymentFailed.module.css";

interface FailedPageProps {
  refId: string | null;
  errorMessage: string | null;
  statusCode?: string;
}

export default function PaymentFailed({ refId, errorMessage, statusCode }: FailedPageProps) {
  return (
    <div className={styles.container}>
      <div className={styles.card}>
        {/* Header */}
        <div className={styles.header}>
          <div className={styles.iconWrapper}>
            <XCircle size={72} className={styles.failedIcon} />
          </div>
          <h1 className={styles.title}>😔 پرداخت ناموفق</h1>
          <p className={styles.subtitle}>
            متأسفانه پرداخت شما با مشکل مواجه شد
          </p>
        </div>

        {/* Error Message */}
        <div className={styles.errorBox}>
          <AlertTriangle size={20} className={styles.errorBoxIcon} />
          <div>
            <div className={styles.errorBoxTitle}>دلیل خطا</div>
            <div className={styles.errorBoxMessage}>
              {errorMessage || "خطای ناشناخته در فرآیند پرداخت"}
            </div>
          </div>
        </div>

        {/* Tracking Code */}
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

        {/* Help Section */}
        <div className={styles.helpBox}>
          <h3>❓ چه کاری می‌توانم انجام دهم؟</h3>
          <ul>
            <li>اطلاعات کارت یا درگاه پرداخت را بررسی کنید</li>
            <li>از اتصال اینترنت خود مطمئن شوید</li>
            <li>در صورت کسر وجه، تا ۴۸ ساعت به حساب شما بازمی‌گردد</li>
            <li>با پشتیبانی تماس بگیرید تا مشکل را بررسی کنیم</li>
          </ul>
        </div>

        {/* Actions */}
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

export const getServerSideProps: GetServerSideProps = async (context) => {
  const { ref_id, authority, status, message } = context.query;

  // اگر هیچ کد پیگیری وجود نداشت، از یک شناسه موقت استفاده کن
  const refId = ref_id || authority || null;

  // پیام خطا را از کوئری یا یک پیام پیش‌فرض بگیر
  const errorMessage =
    typeof message === "string"
      ? message
      : status === "canceled"
      ? "پرداخت توسط کاربر لغو شد"
      : "مشکلی در انجام پرداخت پیش آمد";

  return {
    props: {
      refId: refId,
      errorMessage: errorMessage,
      statusCode: typeof status === "string" ? status : null,
    },
  };
};