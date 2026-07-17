"use client";

import { useEffect, useState } from "react";
import Table from "@/components/admin/Table";
import SearchBar from "@/components/admin/SearchBar";
import Pagination from "@/components/admin/Pagination";
import StatusBadge from "@/components/admin/StatusBadge";
import { adminApi, LlmRequest } from "@/lib/admin_api";
import styles from "./page.module.css";

export default function LlmRequestsPage() {
  const [requests, setRequests] = useState<LlmRequest[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  // فیلترها
  const [search, setSearch] = useState("");
  const [statusFilter, setStatusFilter] = useState<string>("all");
  const [providerFilter, setProviderFilter] = useState<string>("all");
  const [page, setPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [totalItems, setTotalItems] = useState(0);

  // مودال نمایش جزئیات
  const [selectedRequest, setSelectedRequest] = useState<LlmRequest | null>(null);
  const [showModal, setShowModal] = useState(false);

  // لیست یکتا از providerها و مدل‌ها برای فیلتر
  const [providers, setProviders] = useState<string[]>([]);
  const [models, setModels] = useState<string[]>([]);

  const fetchRequests = async () => {
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

      if (providerFilter !== "all") {
        params.provider = providerFilter;
      }

      const data = await adminApi.llmRequests.list(params);
      setRequests(data.data);
      setTotalPages(data.last_page);
      setTotalItems(data.total);

      // استخراج providerها و مدل‌های یکتا
      const uniqueProviders = [...new Set(data.data.map((r: LlmRequest) => r.provider).filter(Boolean))];
      const uniqueModels = [...new Set(data.data.map((r: LlmRequest) => r.model).filter(Boolean))];
      setProviders(uniqueProviders);
      setModels(uniqueModels);

      setLoading(false);
    } catch (err: any) {
      setError(err.message);
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchRequests();
  }, [page, statusFilter, providerFilter]);

  const handleSearch = () => {
    setPage(1);
    fetchRequests();
  };

  const handleViewDetails = (request: LlmRequest) => {
    setSelectedRequest(request);
    setShowModal(true);
  };

  const formatLatency = (ms: number | null) => {
    if (ms === null || ms === undefined) return "—";
    if (ms < 1000) return `${ms}ms`;
    return `${(ms / 1000).toFixed(2)}s`;
  };

  const formatUsage = (usage: any) => {
    if (!usage) return "—";
    const total = usage.total_tokens || usage.prompt_tokens + usage.completion_tokens || 0;
    return total.toLocaleString();
  };

  const truncateContent = (content: string, maxLength: number = 80) => {
    if (!content) return "—";
    return content.length > maxLength ? content.substring(0, maxLength) + "..." : content;
  };

  const columns = [
    { key: "id", label: "شناسه" },
    {
      key: "user",
      label: "کاربر",
      render: (v: any) => v?.name || v?.phone || "کاربر حذف‌شده",
    },
    {
      key: "provider",
      label: "Provider",
      render: (v: string) => <span className={styles.providerBadge}>{v}</span>,
    },
    {
      key: "model",
      label: "مدل",
      render: (v: string) => <span className={styles.modelBadge}>{v}</span>,
    },
    {
      key: "status",
      label: "وضعیت",
      render: (v: string) => {
        const statusMap: Record<string, "pending" | "completed" | "failed" | "cancelled"> = {
          pending: "pending",
          completed: "completed",
          failed: "failed",
          cancelled: "cancelled",
        };
        return <StatusBadge status={statusMap[v] || "pending"} />;
      },
    },
    {
      key: "usage",
      label: "توکن‌ها",
      render: (v: any) => formatUsage(v),
    },
    {
      key: "latency_ms",
      label: "زمان پاسخ",
      render: (v: number | null) => formatLatency(v),
    },
    {
      key: "created_at",
      label: "تاریخ",
      render: (v: string) => new Date(v).toLocaleString("fa-IR"),
    },
  ];

  const actions = (row: LlmRequest) => (
    <button
      onClick={() => handleViewDetails(row)}
      className={styles.btn}
      title="مشاهده جزئیات"
    >
      👁️
    </button>
  );

  if (loading) return <div className={styles.loading}>⏳ در حال بارگذاری...</div>;
  if (error) return <div className={styles.error}>❌ {error}</div>;

  return (
    <div className={styles.page}>
      <div className={styles.header}>
        <h1 className={styles.title}>🌐 مدیریت درخواست‌های LLM</h1>
        <div className={styles.filters}>
          <div className={styles.filterGroup}>
            <label>وضعیت:</label>
            <select
              value={statusFilter}
              onChange={(e) => setStatusFilter(e.target.value)}
              className={styles.select}
            >
              <option value="all">همه</option>
              <option value="pending">در انتظار</option>
              <option value="completed">تکمیل شده</option>
              <option value="failed">ناموفق</option>
              <option value="cancelled">لغو شده</option>
            </select>
          </div>

          <div className={styles.filterGroup}>
            <label>Provider:</label>
            <select
              value={providerFilter}
              onChange={(e) => setProviderFilter(e.target.value)}
              className={styles.select}
            >
              <option value="all">همه</option>
              {providers.map((p) => (
                <option key={p} value={p}>
                  {p}
                </option>
              ))}
            </select>
          </div>

          <div className={styles.searchWrapper}>
            <SearchBar
              value={search}
              onChange={setSearch}
              placeholder="جستجوی کاربر، provider یا مدل..."
            />
            <button onClick={handleSearch} className={styles.searchBtn}>
              جستجو
            </button>
          </div>
        </div>
      </div>

      <div className={styles.stats}>
        <span>تعداد کل: {totalItems} درخواست</span>
        {statusFilter !== "all" && <span> | وضعیت: {statusFilter}</span>}
        {providerFilter !== "all" && <span> | Provider: {providerFilter}</span>}
      </div>

      <Table columns={columns} data={requests} actions={actions} />

      <Pagination
        currentPage={page}
        totalPages={totalPages}
        onPageChange={setPage}
      />

      {/* مودال نمایش جزئیات کامل */}
      {showModal && selectedRequest && (
        <div className={styles.modalOverlay} onClick={() => setShowModal(false)}>
          <div className={styles.modal} onClick={(e) => e.stopPropagation()}>
            <div className={styles.modalHeader}>
              <h3>📋 جزئیات درخواست #{selectedRequest.id}</h3>
              <button className={styles.modalClose} onClick={() => setShowModal(false)}>
                ✕
              </button>
            </div>
            <div className={styles.modalBody}>
              <div className={styles.modalGrid}>
                <div className={styles.modalField}>
                  <label>شناسه</label>
                  <span>#{selectedRequest.id}</span>
                </div>
                <div className={styles.modalField}>
                  <label>کاربر</label>
                  <span>{selectedRequest.user?.name || selectedRequest.user?.phone || "کاربر حذف‌شده"}</span>
                </div>
                <div className={styles.modalField}>
                  <label>Provider</label>
                  <span className={styles.providerBadge}>{selectedRequest.provider}</span>
                </div>
                <div className={styles.modalField}>
                  <label>مدل</label>
                  <span className={styles.modelBadge}>{selectedRequest.model}</span>
                </div>
                <div className={styles.modalField}>
                  <label>وضعیت</label>
                  <StatusBadge
                    status={
                      selectedRequest.status as "pending" | "completed" | "failed" | "cancelled"
                    }
                  />
                </div>
                <div className={styles.modalField}>
                  <label>زمان پاسخ</label>
                  <span>{formatLatency(selectedRequest.latency_ms)}</span>
                </div>
                <div className={styles.modalField}>
                  <label>تاریخ ایجاد</label>
                  <span>{new Date(selectedRequest.created_at).toLocaleString("fa-IR")}</span>
                </div>
                <div className={styles.modalField}>
                  <label>آخرین بروزرسانی</label>
                  <span>{new Date(selectedRequest.updated_at).toLocaleString("fa-IR")}</span>
                </div>
              </div>

              {selectedRequest.usage && (
                <div className={styles.section}>
                  <h4>📊 مصرف توکن</h4>
                  <div className={styles.usageGrid}>
                    <div className={styles.usageItem}>
                      <span className={styles.usageLabel}>توکن ورودی</span>
                      <span className={styles.usageValue}>
                        {selectedRequest.usage.prompt_tokens || 0}
                      </span>
                    </div>
                    <div className={styles.usageItem}>
                      <span className={styles.usageLabel}>توکن خروجی</span>
                      <span className={styles.usageValue}>
                        {selectedRequest.usage.completion_tokens || 0}
                      </span>
                    </div>
                    <div className={styles.usageItem}>
                      <span className={styles.usageLabel}>مجموع</span>
                      <span className={styles.usageValue}>
                        {selectedRequest.usage.total_tokens ||
                          selectedRequest.usage.prompt_tokens + selectedRequest.usage.completion_tokens ||
                          0}
                      </span>
                    </div>
                  </div>
                </div>
              )}

              {selectedRequest.error && (
                <div className={`${styles.section} ${styles.errorSection}`}>
                  <h4>❌ خطا</h4>
                  <pre className={styles.errorValue}>{selectedRequest.error}</pre>
                </div>
              )}

              <div className={styles.section}>
                <h4>📤 درخواست ارسال‌شده</h4>
                <pre className={styles.jsonValue}>
                  {JSON.stringify(selectedRequest.messages, null, 2)}
                </pre>
              </div>

              {selectedRequest.response && (
                <div className={styles.section}>
                  <h4>📥 پاسخ دریافت‌شده</h4>
                  <pre className={styles.jsonValue}>
                    {typeof selectedRequest.response === "string"
                      ? selectedRequest.response
                      : JSON.stringify(selectedRequest.response, null, 2)}
                  </pre>
                </div>
              )}

              {selectedRequest.request_options && (
                <div className={styles.section}>
                  <h4>⚙️ تنظیمات درخواست</h4>
                  <pre className={styles.jsonValue}>
                    {JSON.stringify(selectedRequest.request_options, null, 2)}
                  </pre>
                </div>
              )}
            </div>
            <div className={styles.modalFooter}>
              <button className={styles.modalBtn} onClick={() => setShowModal(false)}>
                بستن
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}