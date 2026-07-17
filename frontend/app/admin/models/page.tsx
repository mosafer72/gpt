"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import Table from "@/components/admin/Table";
import StatusBadge from "@/components/admin/StatusBadge";
import SearchBar from "@/components/admin/SearchBar";
import { adminApi, AIModel } from "@/lib/admin_api";
import styles from "./page.module.css";

export default function ModelsPage() {
  const [models, setModels] = useState<AIModel[]>([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState("");
  const [error, setError] = useState<string | null>(null);

  const fetchModels = async () => {
    try {
      const data = await adminApi.models.list();
      setModels(data);
      setLoading(false);
    } catch (err: any) {
      setError(err.message);
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchModels();
  }, []);

  const toggleStatus = async (id: number, current: boolean) => {
    try {
      await adminApi.models.update(id, { is_active: !current });
      setModels(models.map((m) => (m.id === id ? { ...m, is_active: !current } : m)));
    } catch (err: any) {
      alert("خطا در تغییر وضعیت: " + err.message);
    }
  };

  const handleDelete = async (id: number) => {
    if (!confirm("آیا از حذف این مدل مطمئن هستید؟")) return;
    try {
      await adminApi.models.delete(id);
      setModels(models.filter((m) => m.id !== id));
    } catch (err: any) {
      alert("خطا در حذف مدل: " + err.message);
    }
  };

  const filteredModels = models.filter(
    (model) =>
      model.name.toLowerCase().includes(search.toLowerCase()) ||
      model.key.toLowerCase().includes(search.toLowerCase()) ||
      model.model_name.toLowerCase().includes(search.toLowerCase())
  );

  const columns = [
    { key: "id", label: "شناسه" },
    { key: "key", label: "کلید داخلی" },
    { key: "name", label: "نام نمایشی" },
    { key: "model_name", label: "نام مدل (API)" },
    {
      key: "provider",
      label: "ارائه‌دهنده",
      render: (v: any) => v?.name || "—",
    },
    {
      key: "input_cost",
      label: "هزینه ورودی (به ازای ۱M)",
      render: (v: any) => {
        const num = parseFloat(v);
        return isNaN(num) ? "0" : num.toFixed(4);
      },
    },
    {
      key: "output_cost",
      label: "هزینه خروجی (به ازای ۱M)",
      render: (v: any) => {
        const num = parseFloat(v);
        return isNaN(num) ? "0" : num.toFixed(4);
      },
    },
    {
      key: "is_active",
      label: "وضعیت",
      render: (v: boolean, row: AIModel) => (
        <button
          onClick={() => toggleStatus(row.id, v)}
          className={`${styles.statusBtn} ${v ? styles.active : styles.inactive}`}
        >
          {v ? "فعال" : "غیرفعال"}
        </button>
      ),
    },
  ];

  const actions = (row: AIModel) => (
    <>
      <Link href={`/admin/models/${row.id}/edit`} className={styles.btn}>
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
        <h1 className={styles.title}>🤖 مدیریت مدل‌ها</h1>
        <div className={styles.actionsBar}>
          <SearchBar value={search} onChange={setSearch} placeholder="جستجوی مدل..." />
          <Link href="/admin/models/create" className={styles.createBtn}>
            + مدل جدید
          </Link>
        </div>
      </div>

      <Table columns={columns} data={filteredModels} actions={actions} />
    </div>
  );
}