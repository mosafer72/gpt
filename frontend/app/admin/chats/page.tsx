"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import Table from "@/components/admin/Table";
import SearchBar from "@/components/admin/SearchBar";
import Pagination from "@/components/admin/Pagination";
import { adminApi, Chat } from "@/lib/admin_api";
import styles from "./page.module.css";

export default function ChatsPage() {
  const [chats, setChats] = useState<Chat[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const [search, setSearch] = useState("");
  const [page, setPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [totalItems, setTotalItems] = useState(0);

  const fetchChats = async () => {
    setLoading(true);
    try {
      const params: Record<string, any> = {
        page,
        per_page: 20,
      };

      if (search) {
        params.search = search;
      }

      const data = await adminApi.chats.list(params);
      console.log(data)
      setChats(data.data);
      setTotalPages(data.last_page);
      setTotalItems(data.total);
      setLoading(false);
    } catch (err: any) {
      setError(err.message);
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchChats();
  }, [page, search]);

  const handleSearch = () => {
    setPage(1);
    fetchChats();
  };

  const handleDelete = async (id: number) => {
    if (!confirm("آیا از حذف این چت مطمئن هستید؟")) return;
    try {
      await adminApi.chats.delete(id);
      setChats(chats.filter((c) => c.id !== id));
      setTotalItems((prev) => prev - 1);
    } catch (err: any) {
      alert("خطا در حذف چت: " + err.message);
    }
  };

  const columns = [
    { key: "id", label: "شناسه" },
    {
      key: "user",
      label: "کاربر",
      render: (v: any) => {
        if (!v) return "کاربر مهمان";
        return v.name || v.phone || "کاربر مهمان";
      },
    },
    {
      key: "title",
      label: "عنوان",
      render: (v: string | null) => (
        <span className={styles.chatTitle}>{v || "چت بدون عنوان"}</span>
      ),
    },
    {
      key: "messages_count",
      label: "تعداد پیام",
      render: (v: number) => (
        <span className={styles.messageCount}>{v || 0}</span>
      ),
    },
    {
      key: "created_at",
      label: "تاریخ ایجاد",
      render: (v: string) => new Date(v).toLocaleDateString("fa-IR"),
    },
    {
      key: "updated_at",
      label: "آخرین فعالیت",
      render: (v: string) => {
        const now = new Date();
        const date = new Date(v);
        const diff = Math.floor((now.getTime() - date.getTime()) / 1000 / 60);
        if (diff < 1) return "چند لحظه پیش";
        if (diff < 60) return `${diff} دقیقه پیش`;
        if (diff < 1440) return `${Math.floor(diff / 60)} ساعت پیش`;
        return date.toLocaleDateString("fa-IR");
      },
    },
  ];

  const actions = (row: Chat) => (
    <>
      <Link href={`/admin/chats/${row.id}`} className={styles.btn} title="مشاهده">
        👁️
      </Link>
      <button
        onClick={() => handleDelete(row.id)}
        className={`${styles.btn} ${styles.danger}`}
        title="حذف"
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
        <h1 className={styles.title}>💬 مدیریت چت‌ها</h1>
        <div className={styles.filters}>
          <div className={styles.searchWrapper}>
            <SearchBar
              value={search}
              onChange={setSearch}
              placeholder="جستجوی عنوان یا کاربر..."
            />
            <button onClick={handleSearch} className={styles.searchBtn}>
              جستجو
            </button>
          </div>
        </div>
      </div>

      <div className={styles.stats}>
        <span>تعداد کل: {totalItems} چت</span>
      </div>

      <Table columns={columns} data={chats} actions={actions} />

      <Pagination
        currentPage={page}
        totalPages={totalPages}
        onPageChange={setPage}
      />
    </div>
  );
}