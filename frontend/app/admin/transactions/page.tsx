"use client";

import { useEffect, useState, ChangeEvent } from "react";
import Link from "next/link";
import Table from "@/components/admin/Table";
import StatusBadge from "@/components/admin/StatusBadge";
import SearchBar from "@/components/admin/SearchBar";
import Pagination from "@/components/admin/Pagination";
import { adminApi, Transaction } from "@/lib/admin_api";
import styles from "./page.module.css";

// تعریف نوع برای ستون‌های جدول (اختیاری)
type Column<T> = {
  key: keyof T | string;
  label: string;
  render?: (value: any, row: T) => React.ReactNode;
};

// نوع وضعیت‌های مجاز برای StatusBadge
type TransactionStatus = "pending" | "paid" | "failed";

export default function TransactionsPage() {
  const [transactions, setTransactions] = useState<Transaction[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  // فیلترها
  const [search, setSearch] = useState("");
  const [statusFilter, setStatusFilter] = useState<string>("all");
  const [page, setPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [totalItems, setTotalItems] = useState(0);

  const fetchTransactions = async () => {
    setLoading(true);
    try {
      const params: Record<string, any> = {
        page,
        per_page: 20,
      };

      if (search) {
        params.search = search;
      }

      if (statusFilter !== "all") {
        params.status = statusFilter;
      }

      const data = await adminApi.transactions.list(params);
      setTransactions(data.data);
      setTotalPages(data.last_page);
      setTotalItems(data.total);
      setLoading(false);
    } catch (err: any) {
      setError(err.message);
      setLoading(false);
    }
  };

  // وابستگی‌ها: هر بار که صفحه یا فیلتر وضعیت یا جستجو تغییر کند، دوباره واکشی کن
  useEffect(() => {
    fetchTransactions();
  }, [page, statusFilter, search]); // اضافه کردن search به دیپندنسی‌ها

  // نیازی به handleSearch جداگانه نیست چون با تغییر search، useEffect اجرا می‌شود
  // اما اگر می‌خواهید با کلیک دکمه جستجو انجام شود، می‌توانید این تابع را保留
  const handleSearch = () => {
    setPage(1);
    // با تنظیم page، useEffect اجرا می‌شود و search فعلی را می‌گیرد
  };

  const handleStatusChange = (e: ChangeEvent<HTMLSelectElement>) => {
    setStatusFilter(e.target.value);
    setPage(1);
  };

  // تعریف ستون‌ها با تایپ دقیق
  const columns: Column<Transaction>[] = [
    { key: "id", label: "شناسه" },
    {
      key: "user",
      label: "کاربر",
      render: (value: any) => value?.name || value?.phone || "ناشناس",
    },
    {
      key: "plan",
      label: "پلن",
      render: (value: any) => value?.display_name || "—",
    },
    {
      key: "amount",
      label: "مبلغ (ریال)",
      render: (value: number) => value.toLocaleString(),
    },
    { key: "authority", label: "کد پیگیری" },
    {
      key: "status",
      label: "وضعیت",
      render: (value: string) => {
        // فقط مقادیر مجاز را به StatusBadge بدهیم
        const statusMap: Record<string, TransactionStatus> = {
          pending: "pending",
          paid: "paid",
          failed: "failed",
        };
        const status = statusMap[value] || "pending";
        return <StatusBadge status={status} />;
      },
    },
    {
      key: "created_at",
      label: "تاریخ",
      render: (value: string) => new Date(value).toLocaleDateString("fa-IR"),
    },
  ];

  if (loading)
    return <div className={styles.loading}>⏳ در حال بارگذاری...</div>;
  if (error) return <div className={styles.error}>❌ {error}</div>;

  return (
    <div className={styles.page}>
      <div className={styles.header}>
        <h1 className={styles.title}>💳 مدیریت تراکنش‌ها</h1>
        <div className={styles.filters}>
          <div className={styles.filterGroup}>
            <label>وضعیت:</label>
            <select
              value={statusFilter}
              onChange={handleStatusChange}
              className={styles.select}
            >
              <option value="all">همه</option>
              <option value="pending">در انتظار</option>
              <option value="paid">پرداخت شده</option>
              <option value="failed">ناموفق</option>
            </select>
          </div>

          <div className={styles.searchWrapper}>
            <SearchBar
              value={search}
              onChange={setSearch}
              placeholder="جستجوی کاربر یا کد پیگیری..."
            />
            <button onClick={handleSearch} className={styles.searchBtn}>
              جستجو
            </button>
          </div>
        </div>
      </div>

      <div className={styles.stats}>
        <span>تعداد کل: {totalItems}</span>
      </div>

      <Table columns={columns} data={transactions} />

      <Pagination
        currentPage={page}
        totalPages={totalPages}
        onPageChange={setPage}
      />
    </div>
  );
}