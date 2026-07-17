import styles from "./StatusBadge.module.css";

type Status =
  | "active"
  | "inactive"
  | "pending"
  | "paid"
  | "failed"
  | "verified"
  | "unverified"
  | "completed"
  | "cancelled"
  | "explicit"     // ✅ اضافه شد
  | "inferred";    // ✅ اضافه شد

interface StatusBadgeProps {
  status: Status;
  label?: string;
}

const statusMap: Record<Status, { label: string; className: string }> = {
  active: { label: "فعال", className: styles.active },
  inactive: { label: "غیرفعال", className: styles.inactive },
  pending: { label: "در انتظار", className: styles.pending },
  paid: { label: "پرداخت شده", className: styles.paid },
  failed: { label: "ناموفق", className: styles.failed },
  verified: { label: "تایید شده", className: styles.verified },
  unverified: { label: "تایید نشده", className: styles.unverified },
  completed: { label: "تکمیل شده", className: styles.completed },
  cancelled: { label: "لغو شده", className: styles.cancelled },

  // ✅ جدید
  explicit: { label: "صریح", className: styles.verified },
  inferred: { label: "استنباط‌شده", className: styles.pending },
};

export default function StatusBadge({ status, label }: StatusBadgeProps) {
  const config = statusMap[status] || statusMap.inactive;

  return (
    <span className={`${styles.badge} ${config.className}`}>
      {label || config.label}
    </span>
  );
}