"use client";

import { useState, ChangeEvent, FormEvent } from "react";
import { useRouter } from "next/navigation";
import { adminApi } from "@/lib/admin_api";
import styles from "./page.module.css";

// تعریف نوع داده فرم برای جلوگیری از خطاهای TypeScript
interface ProviderFormData {
  key: string;
  name: string;
  api_key: string;
  base_url: string;
  is_active: boolean;
}

export default function CreateProviderPage() {
  const router = useRouter();
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [formData, setFormData] = useState<ProviderFormData>({
    key: "",
    name: "",
    api_key: "",
    base_url: "",
    is_active: true,
  });

  const handleChange = (e: ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
    const { name, value, type } = e.target;
    // برای checkbox، مقدار checked را می‌گیریم
    const newValue =
      type === "checkbox" ? (e.target as HTMLInputElement).checked : value;

    // با استفاده از keyof به TypeScript می‌گوییم name یکی از کلیدهای ProviderFormData است
    setFormData((prev) => ({
      ...prev,
      [name as keyof ProviderFormData]: newValue,
    }));
  };

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setError(null);

    try {
      await adminApi.providers.create(formData);
      router.push("/admin/providers");
    } catch (err: any) {
      setError(err.message);
      setLoading(false);
    }
  };

  return (
    <div className={styles.page}>
      <h1 className={styles.title}>➕ ایجاد ارائه‌دهنده جدید</h1>

      {error && <div className={styles.error}>{error}</div>}

      <form className={styles.form} onSubmit={handleSubmit}>
        <div className={styles.row}>
          <div className={styles.field}>
            <label>
              کلید داخلی (شناسه) <span className={styles.required}>*</span>
            </label>
            <input
              type="text"
              name="key"
              value={formData.key}
              onChange={handleChange}
              placeholder="مثلاً: openai"
              required
            />
            <small>یک شناسه یکتا برای این ارائه‌دهنده</small>
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
              placeholder="مثلاً: OpenAI"
              required
            />
          </div>
        </div>

        <div className={styles.row}>
          <div className={styles.field}>
            <label>
              کلید API <span className={styles.required}>*</span>
            </label>
            <input
              type="password"
              name="api_key"
              value={formData.api_key}
              onChange={handleChange}
              placeholder="sk-..."
              required
            />
            <small>کلید API برای ارتباط با این ارائه‌دهنده</small>
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
            <small>
              اختیاری - در صورت خالی بودن از مقدار پیش‌فرض استفاده می‌شود
            </small>
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
          <button type="submit" className={styles.submitBtn} disabled={loading}>
            {loading ? "در حال ذخیره..." : "ذخیره ارائه‌دهنده"}
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