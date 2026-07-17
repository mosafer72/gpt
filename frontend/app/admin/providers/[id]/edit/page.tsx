"use client";

import { useState, useEffect, ChangeEvent, FormEvent } from "react";
import { useParams, useRouter } from "next/navigation";
import { adminApi } from "@/lib/admin_api"; // LlmProvider استفاده نشده، حذف شد
import styles from "./page.module.css";
import { LlmProviderForm } from "@/types/admin_types";

export default function EditProviderPage() {
  const params = useParams();
  const router = useRouter();

  // 🔹 بررسی وجود id و تبدیل به عدد
  const idParam = params?.id;
  const providerId = typeof idParam === "string" ? Number(idParam) : NaN;

  // اگر id معتبر نبود، می‌توان خطا نشان داد یا به صفحه قبل برگشت
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [formData, setFormData] = useState<LlmProviderForm>({
    key: "",
    name: "",
    api_key: "",
    base_url: "",
    is_active: true,
  });

  useEffect(() => {
    // اگر id معتبر نیست، خطا بده
    if (isNaN(providerId)) {
      setError("شناسه ارائه‌دهنده نامعتبر است");
      setLoading(false);
      return;
    }

    adminApi.providers
      .get(providerId)
      .then((data) => {
        setFormData({
          key: data.key || "",
          name: data.name || "",
          api_key: data.api_key || "",
          base_url: data.base_url || "",
          is_active: data.is_active,
        });
        setLoading(false);
      })
      .catch((err) => {
        setError(err.message);
        setLoading(false);
      });
  }, [providerId]);

  // 🔹 تایپ دقیق‌تر برای event handler
  const handleChange = (
    e: ChangeEvent<HTMLInputElement | HTMLSelectElement>
  ) => {
    const { name, value, type } = e.target;

    // اگر checkbox است، مقدار checked را بگیریم
    const newValue =
      type === "checkbox" ? (e.target as HTMLInputElement).checked : value;

    // 🔹 با استفاده از type assertion به TypeScript می‌گوییم name یکی از کلیدهای LlmProviderForm است
    setFormData((prev) => ({
      ...prev,
      [name as keyof LlmProviderForm]: newValue,
    }));
  };

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setSaving(true);
    setError(null);

    try {
      // برای ارسال، از نوع Partial استفاده می‌کنیم تا مجاز به حذف api_key باشیم
      const dataToSend: Partial<LlmProviderForm> = { ...formData };

      // اگر api_key خالی بود، آن را حذف می‌کنیم (تا در بک‌اند تغییر نکند)
      if (!dataToSend.api_key) {
        delete dataToSend.api_key;
      }

      await adminApi.providers.update(providerId, dataToSend);
      router.push("/admin/providers");
    } catch (err: any) {
      setError(err.message);
      setSaving(false);
    }
  };

  // نمایش بارگذاری یا خطا
  if (loading) return <div className={styles.loading}>⏳ در حال بارگذاری...</div>;
  if (error) return <div className={styles.error}>❌ {error}</div>;

  return (
    <div className={styles.page}>
      <h1 className={styles.title}>✏️ ویرایش ارائه‌دهنده: {formData.name}</h1>

      {error && <div className={styles.error}>{error}</div>}

      <form className={styles.form} onSubmit={handleSubmit}>
        <div className={styles.row}>
          <div className={styles.field}>
            <label>کلید داخلی (شناسه)</label>
            <input
              type="text"
              name="key"
              value={formData.key}
              onChange={handleChange}
              required
              disabled
            />
            <small>شناسه قابل ویرایش نیست</small>
          </div>
          <div className={styles.field}>
            <label>
              نام نمایشی <span className={styles.required}>*</span>
            </label>
            <input
              type="text"
              name="name"
              value={formData.name}
              onChange={handleChange}
              required
            />
          </div>
        </div>

        <div className={styles.row}>
          <div className={styles.field}>
            <label>کلید API</label>
            <input
              type="password"
              name="api_key"
              value={formData.api_key}
              onChange={handleChange}
              placeholder="کلید جدید (اختیاری)"
            />
            <small>اگر خالی بگذارید، کلید قبلی حفظ می‌شود</small>
          </div>
          <div className={styles.field}>
            <label>آدرس پایه (Base URL)</label>
            <input
              type="url"
              name="base_url"
              value={formData.base_url}
              onChange={handleChange}
              placeholder="https://api.openai.com/v1"
            />
          </div>
        </div>

        <div className={styles.field}>
          <label className={styles.checkboxLabel}>
            <input
              type="checkbox"
              name="is_active"
              checked={formData.is_active}
              onChange={handleChange}
            />
            فعال
          </label>
        </div>

        <div className={styles.actions}>
          <button type="submit" className={styles.submitBtn} disabled={saving}>
            {saving ? "در حال ذخیره..." : "ذخیره تغییرات"}
          </button>
          <button
            type="button"
            className={styles.cancelBtn}
            onClick={() => router.push("/admin/providers")}
          >
            انصراف
          </button>
        </div>
      </form>
    </div>
  );
}