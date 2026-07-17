"use client";

import { useEffect, useState } from "react";
import Table from "@/components/admin/Table";
import SearchBar from "@/components/admin/SearchBar";
import Pagination from "@/components/admin/Pagination";
import StatusBadge from "@/components/admin/StatusBadge";
import { adminApi, Memory } from "@/lib/admin_api";
import styles from "./page.module.css";

export default function MemoriesPage() {
  const [memories, setMemories] = useState<Memory[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  // فیلترها
  const [search, setSearch] = useState("");
  const [userIdFilter, setUserIdFilter] = useState<string>("");
  const [page, setPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [totalItems, setTotalItems] = useState(0);

  // مودال نمایش مقدار کامل
  const [selectedMemory, setSelectedMemory] = useState<Memory | null>(null);
  const [showModal, setShowModal] = useState(false);

  const fetchMemories = async () => {
    setLoading(true);
    try {
      const params: Record<string, any> = {
        page,
        per_page: 20,
      };

      if (search) {
        params.search = search;
      }

      if (userIdFilter) {
        params.user_id = userIdFilter;
      }

      const data = await adminApi.memories.list(params);
      setMemories(data.data);
      setTotalPages(data.last_page);
      setTotalItems(data.total);
      setLoading(false);
    } catch (err: any) {
      setError(err.message);
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchMemories();
  }, [page, search, userIdFilter]);

  const handleSearch = () => {
    setPage(1);
    fetchMemories();
  };

  const handleDelete = async (id: number) => {
    if (!confirm("آیا از حذف این حافظه مطمئن هستید؟")) return;
    try {
      await adminApi.memories.delete(id);
      setMemories(memories.filter((m) => m.id !== id));
      setTotalItems((prev) => prev - 1);
    } catch (err: any) {
      alert("خطا در حذف حافظه: " + err.message);
    }
  };

  const handleViewValue = (memory: Memory) => {
    setSelectedMemory(memory);
    setShowModal(true);
  };

  const truncateValue = (value: string, maxLength: number = 50) => {
    if (!value) return "—";
    return value.length > maxLength
      ? value.substring(0, maxLength) + "..."
      : value;
  };

  const columns = [
    { key: "id", label: "شناسه" },
    {
      key: "user",
      label: "کاربر",
      render: (v: any) => v?.name || v?.phone || "کاربر حذف‌شده",
    },
    {
      key: "key",
      label: "کلید",
      render: (v: string) => <span className={styles.keyBadge}>{v}</span>,
    },
    {
      key: "value",
      label: "مقدار",
      render: (v: string, row: Memory) => (
        <div className={styles.valueCell}>
          <span>{truncateValue(v)}</span>
          {v && v.length > 50 && (
            <button
              className={styles.viewBtn}
              onClick={() => handleViewValue(row)}
            >
              مشاهده کامل
            </button>
          )}
        </div>
      ),
    },
    {
      key: "source",
      label: "منبع",
      render: (v: string) => (
        <StatusBadge status={v === "explicit" ? "explicit" : "inferred"} />
      ),
    },
    {
      key: "last_used_at",
      label: "آخرین استفاده",
      render: (v: string | null) => {
        if (!v) return "هرگز";
        const now = new Date();
        const date = new Date(v);
        const diff = Math.floor((now.getTime() - date.getTime()) / 1000 / 60);
        if (diff < 1) return "چند لحظه پیش";
        if (diff < 60) return `${diff} دقیقه پیش`;
        if (diff < 1440) return `${Math.floor(diff / 60)} ساعت پیش`;
        return date.toLocaleDateString("fa-IR");
      },
    },
    {
      key: "created_at",
      label: "تاریخ ایجاد",
      render: (v: string) => new Date(v).toLocaleDateString("fa-IR"),
    },
  ];

  const actions = (row: Memory) => (
    <>
      <button
        onClick={() => handleViewValue(row)}
        className={styles.btn}
        title="مشاهده مقدار کامل"
      >
        👁️
      </button>
      <button
        onClick={() => handleDelete(row.id)}
        className={`${styles.btn} ${styles.danger}`}
        title="حذف"
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
        <h1 className={styles.title}>🧠 مدیریت حافظه</h1>
        <div className={styles.filters}>
          <div className={styles.searchWrapper}>
            <SearchBar
              value={search}
              onChange={setSearch}
              placeholder="جستجوی کلید یا کاربر..."
            />
            <button onClick={handleSearch} className={styles.searchBtn}>
              جستجو
            </button>
          </div>
        </div>
      </div>

      <div className={styles.stats}>
        <span>تعداد کل: {totalItems} حافظه</span>
        {userIdFilter && <span> | فیلتر شده بر اساس کاربر</span>}
      </div>

      <Table columns={columns} data={memories} actions={actions} />

      <Pagination
        currentPage={page}
        totalPages={totalPages}
        onPageChange={setPage}
      />

      {/* مودال نمایش مقدار کامل */}
      {showModal && selectedMemory && (
        <div
          className={styles.modalOverlay}
          onClick={() => setShowModal(false)}
        >
          <div className={styles.modal} onClick={(e) => e.stopPropagation()}>
            <div className={styles.modalHeader}>
              <h3>📝 مقدار کامل حافظه</h3>
              <button
                className={styles.modalClose}
                onClick={() => setShowModal(false)}
              >
                ✕
              </button>
            </div>
            <div className={styles.modalBody}>
              <div className={styles.modalField}>
                <label>کلید</label>
                <span className={styles.modalKey}>{selectedMemory.key}</span>
              </div>
              <div className={styles.modalField}>
                <label>کاربر</label>
                <span>
                  {selectedMemory.user?.name ||
                    selectedMemory.user?.phone ||
                    "کاربر حذف‌شده"}
                </span>
              </div>
              <div className={styles.modalField}>
                <label>مقدار</label>
                <pre className={styles.modalValue}>{selectedMemory.value}</pre>
              </div>
              <div className={styles.modalField}>
                <label>منبع</label>
                <span>
                  {selectedMemory.source === "explicit" ? "صراحی" : "استنباطی"}
                </span>
              </div>
              <div className={styles.modalField}>
                <label>آخرین استفاده</label>
                <span>
                  {selectedMemory.last_used_at
                    ? new Date(selectedMemory.last_used_at).toLocaleString(
                        "fa-IR",
                      )
                    : "هرگز"}
                </span>
              </div>
            </div>
            <div className={styles.modalFooter}>
              <button
                className={styles.modalBtn}
                onClick={() => setShowModal(false)}
              >
                بستن
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
