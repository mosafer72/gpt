"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import Table from "@/components/admin/Table";
import StatusBadge from "@/components/admin/StatusBadge";
import { adminApi, Plan } from "@/lib/admin_api";
import styles from "./page.module.css";

export default function PlansPage() {
  const [plans, setPlans] = useState<Plan[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const fetchPlans = async () => {
    try {
      const data = await adminApi.plans.list();
      setPlans(data);
      setLoading(false);
    } catch (err: any) {
      setError(err.message);
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchPlans();
  }, []);

  const toggleStatus = async (id: number, current: boolean) => {
    try {
      await adminApi.plans.update(id, { is_active: !current });
      setPlans(plans.map((p) => (p.id === id ? { ...p, is_active: !current } : p)));
    } catch (err: any) {
      alert("خطا در تغییر وضعیت: " + err.message);
    }
  };

  const handleDelete = async (id: number) => {
    if (!confirm("آیا از حذف این پلن مطمئن هستید؟")) return;
    try {
      await adminApi.plans.delete(id);
      setPlans(plans.filter((p) => p.id !== id));
    } catch (err: any) {
      alert("خطا در حذف پلن: " + err.message);
    }
  };

  const columns = [
    { key: "id", label: "شناسه" },
    { key: "name", label: "نام" },
    { key: "display_name", label: "نام نمایشی" },
    {
      key: "price",
      label: "قیمت (ریال)",
      render: (v: number) => v.toLocaleString(),
    },
    { key: "duration_days", label: "مدت (روز)" },
    { key: "daily_message_limit", label: "پیام روزانه" },
    {
      key: "token_limit",
      label: "محدودیت توکن",
      render: (v: number) => v.toLocaleString(),
    },
    {
      key: "is_active",
      label: "وضعیت",
      render: (v: boolean, row: Plan) => (
        <button
          onClick={() => toggleStatus(row.id, v)}
          className={`${styles.statusBtn} ${v ? styles.active : styles.inactive}`}
        >
          {v ? "فعال" : "غیرفعال"}
        </button>
      ),
    },
  ];

  const actions = (row: Plan) => (
    <>
      <Link href={`/admin/plans/${row.id}/edit`} className={styles.btn}>
        ✏️
      </Link>
      <button
        onClick={() => handleDelete(row.id)}
        className={`${styles.btn} ${styles.danger}`}
      >
        🗑️
      </button>
    </>
  );

  if (loading) return <div className={styles.loading}>⏳ در حال بارگذاری...</div>;
  if (error) return <div className={styles.error}>❌ {error}</div>;

  return (
    <div className={styles.page}>
      <div className={styles.header}>
        <h1 className={styles.title}>📦 مدیریت پلن‌ها</h1>
        <Link href="/admin/plans/create" className={styles.createBtn}>
          + پلن جدید
        </Link>
      </div>

      <Table columns={columns} data={plans} actions={actions} />
    </div>
  );
}