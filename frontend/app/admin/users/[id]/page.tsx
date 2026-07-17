"use client";

import { useEffect, useState } from "react";
import { useParams, useRouter } from "next/navigation";
import Link from "next/link";
import { adminApi, User } from "@/lib/admin_api";
import StatusBadge from "@/components/admin/StatusBadge";
import styles from "./page.module.css";

type VerificationStatus = "verified" | "unverified";
type SubscriptionStatus = "active" | "inactive";

export default function UserDetailPage() {
  const params = useParams();
  const router = useRouter();

  const idParam = params?.id;
  const userId = typeof idParam === "string" ? Number(idParam) : NaN;

  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (isNaN(userId) || userId <= 0) {
      setError("شناسه کاربر نامعتبر");
      setLoading(false);
      return;
    }

    adminApi.users
      .get(userId)
      .then((data) => {
        setUser(data);
        setLoading(false);
      })
      .catch((err) => {
        setError(err.message);
        setLoading(false);
      });
  }, [userId]);

  if (loading) return <div className={styles.loading}>⏳ در حال بارگذاری...</div>;
  if (error) return <div className={styles.error}>❌ {error}</div>;
  if (!user) return <div className={styles.error}>❌ کاربر یافت نشد</div>;

  const subscriptionStatus: SubscriptionStatus = user.subscription?.is_active
    ? "active"
    : "inactive";

  return (
    <div className={styles.page}>
      <div className={styles.header}>
        <button className={styles.backBtn} onClick={() => router.back()}>
          ← بازگشت
        </button>
        <h1 className={styles.title}>👤 {user.name || "کاربر بدون نام"}</h1>
      </div>

      <div className={styles.infoGrid}>
        <div className={styles.card}>
          <span className={styles.label}>شناسه</span>
          <span className={styles.value}>#{user.id}</span>
        </div>
        <div className={styles.card}>
          <span className={styles.label}>شماره تلفن</span>
          <span className={styles.value}>{user.phone}</span>
        </div>
        <div className={styles.card}>
          <span className={styles.label}>وضعیت</span>
          <StatusBadge status={user.phone_verified_at ? "verified" : "unverified"} />
        </div>
        <div className={styles.card}>
          <span className={styles.label}>تاریخ ثبت</span>
          <span className={styles.value}>
            {new Date(user.created_at).toLocaleDateString("fa-IR")}
          </span>
        </div>
      </div>

      <div className={styles.section}>
        <h3>📦 اشتراک فعلی</h3>
        {user.subscription ? (
          <div className={styles.subscription}>
            <p>
              <strong>پلن:</strong>{" "}
              {user.subscription.plan?.display_name ?? "نامشخص"}
            </p>
            <p>
              <strong>وضعیت:</strong>{" "}
              <StatusBadge status={subscriptionStatus} />
            </p>
            <p>
              <strong>انقضا:</strong>{" "}
              {new Date(user.subscription.expires_at).toLocaleDateString("fa-IR")}
            </p>
          </div>
        ) : (
          <p className={styles.empty}>اشتراک فعالی وجود ندارد</p>
        )}
      </div>

      <div className={styles.section}>
        <h3>📊 آمار</h3>
        <div className={styles.statsRow}>
          <span>چت‌ها: {(user as any).chats_count || 0}</span>
          <span>پیام‌ها: {(user as any).messages_count || 0}</span>
        </div>
      </div>

      <div className={styles.actions}>
        <Link href={`/admin/chats?user_id=${user.id}`} className={styles.actionBtn}>
          💬 مشاهده چت‌ها
        </Link>
        <Link href={`/admin/memories?user_id=${user.id}`} className={styles.actionBtn}>
          🧠 مشاهده حافظه
        </Link>
      </div>
    </div>
  );
}