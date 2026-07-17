"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import Table from "@/components/admin/Table";
import StatusBadge from "@/components/admin/StatusBadge";
import SearchBar from "@/components/admin/SearchBar";
import { adminApi, User } from "@/lib/admin_api";
import styles from "./page.module.css";

// نوع کمکی برای ستون‌های جدول
type Column<T> = {
  key: keyof T | string;
  label: string;
  render?: (value: any, row: T) => React.ReactNode;
};

// نوع وضعیت‌های مجاز برای StatusBadge
type VerificationStatus = "verified" | "unverified";

export default function UsersPage() {
  const [users, setUsers] = useState<User[]>([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState("");
  const [error, setError] = useState<string | null>(null);

  const fetchUsers = async () => {
    try {
      const data = await adminApi.users.list();
      setUsers(data);
      setLoading(false);
    } catch (err: any) {
      setError(err.message);
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchUsers();
  }, []);

  const handleDelete = async (id: number) => {
    if (!confirm("آیا از حذف این کاربر مطمئن هستید؟")) return;
    try {
      await adminApi.users.delete(id);
      setUsers(users.filter((u) => u.id !== id));
    } catch (err: any) {
      alert("خطا در حذف کاربر: " + err.message);
    }
  };

  // فیلتر کردن بر اساس نام یا شماره
  const filteredUsers = users.filter((user) => {
    const nameMatch = user.name?.toLowerCase().includes(search.toLowerCase()) ?? false;
    const phoneMatch = user.phone.includes(search);
    return nameMatch || phoneMatch;
  });

  // تعریف ستون‌ها با تایپ دقیق
  const columns: Column<User>[] = [
    { key: "id", label: "شناسه" },
    {
      key: "name",
      label: "نام",
      render: (value: string | null) => value || "—",
    },
    { key: "phone", label: "شماره" },
    {
      key: "phone_verified_at",
      label: "وضعیت",
      render: (value: string | null) => {
        const status: VerificationStatus = value ? "verified" : "unverified";
        return <StatusBadge status={status} />;
      },
    },
    {
      key: "subscription",
      label: "پلن",
      render: (value: any) => value?.plan?.display_name || "رایگان",
    },
    {
      key: "created_at",
      label: "تاریخ ثبت",
      render: (value: string) => new Date(value).toLocaleDateString("fa-IR"),
    },
  ];

  const actions = (row: User) => (
    <>
      <Link href={`/admin/users/${row.id}`} className={styles.btn}>
        👁️
      </Link>
      <button
        onClick={() => handleDelete(row.id)}
        className={`${styles.btn} ${styles.danger}`}
      >
        🗑️
      </button>
    </>
  );

  if (loading)
    return <div className={styles.loading}>⏳ در حال بارگذاری...</div>;
  if (error) return <div className={styles.error}>❌ {error}</div>;

  return (
    <div className={styles.page}>
      <div className={styles.header}>
        <h1 className={styles.title}>👥 مدیریت کاربران</h1>
        <SearchBar
          value={search}
          onChange={setSearch}
          placeholder="جستجوی نام یا شماره..."
        />
      </div>

      <Table
        columns={columns}
        data={filteredUsers}
        actions={actions}
        // کلید یکتا برای هر ردیف
      />
    </div>
  );
}