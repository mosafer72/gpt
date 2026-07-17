"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import Table from "@/components/admin/Table";
import SearchBar from "@/components/admin/SearchBar";
import { adminApi, LlmProvider } from "@/lib/admin_api";
import styles from "./page.module.css";

type Column<T> = {
  key: keyof T | string;
  label: string;
  render?: (value: any, row: T) => React.ReactNode;
};

export default function ProvidersPage() {
  const [providers, setProviders] = useState<LlmProvider[]>([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState("");
  const [error, setError] = useState<string | null>(null);

  const fetchProviders = async () => {
    try {
      const data = await adminApi.providers.list();
      setProviders(data);
      setLoading(false);
    } catch (err: any) {
      setError(err.message);
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchProviders();
  }, []);

  const toggleStatus = async (id: number, current: boolean) => {
    try {
      await adminApi.providers.update(id, { is_active: !current });
      setProviders(
        providers.map((p) =>
          p.id === id ? { ...p, is_active: !current } : p
        )
      );
    } catch (err: any) {
      alert("خطا در تغییر وضعیت: " + err.message);
    }
  };

  const handleDelete = async (id: number) => {
    if (!confirm("آیا از حذف این ارائه‌دهنده مطمئن هستید؟")) return;
    try {
      await adminApi.providers.delete(id);
      setProviders(providers.filter((p) => p.id !== id));
    } catch (err: any) {
      alert("خطا در حذف ارائه‌دهنده: " + err.message);
    }
  };

  const filteredProviders = providers.filter(
    (p) =>
      (p.name?.toLowerCase().includes(search.toLowerCase()) ?? false) ||
      (p.key?.toLowerCase().includes(search.toLowerCase()) ?? false)
  );

  const columns: Column<LlmProvider>[] = [
    { key: "id", label: "شناسه" },
    { key: "key", label: "کلید داخلی" },
    { key: "name", label: "نام" },
    {
      key: "base_url",
      label: "آدرس پایه",
      render: (value: string | null | undefined) => value || "—",
    },
    {
      key: "is_active",
      label: "وضعیت",
      render: (value: boolean, row: LlmProvider) => (
        <button
          onClick={() => toggleStatus(row.id, value)}
          className={`${styles.statusBtn} ${value ? styles.active : styles.inactive}`}
        >
          {value ? "فعال" : "غیرفعال"}
        </button>
      ),
    },
  ];

  const actions = (row: LlmProvider) => (
    <>
      <Link href={`/admin/providers/${row.id}/edit`} className={styles.btn}>
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
        <h1 className={styles.title}>🔌 مدیریت ارائه‌دهندگان</h1>
        <div className={styles.actionsBar}>
          <SearchBar
            value={search}
            onChange={setSearch}
            placeholder="جستجوی ارائه‌دهنده..."
          />
          <Link href="/admin/providers/create" className={styles.createBtn}>
            + ارائه‌دهنده جدید
          </Link>
        </div>
      </div>

      <Table
        columns={columns}
        data={filteredProviders}
        actions={actions}
        // rowKey حذف شد
      />
    </div>
  );
}