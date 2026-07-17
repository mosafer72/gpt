"use client";

import { useState, useEffect } from "react";
import { useParams, useRouter } from "next/navigation";
import { adminApi, LlmProvider } from "@/lib/admin_api";
import styles from "./page.module.css";

export default function EditModelPage() {
  const params = useParams();
  const router = useRouter();
  const modelId = Number(params.id);

  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [providers, setProviders] = useState<LlmProvider[]>([]);
  const [formData, setFormData] = useState({
    key: "",
    name: "",
    model_name: "",
    provider_id: "",
    input_cost: "",
    output_cost: "",
    is_active: true,
  });

  // دریافت اطلاعات مدل و لیست providers
  useEffect(() => {
    Promise.all([
      adminApi.models.get(modelId),
      adminApi.providers.list(),
    ])
      .then(([model, providersData]) => {
        setProviders(providersData);
        setFormData({
          key: model.key,
          name: model.name,
          model_name: model.model_name,
          provider_id: String(model.provider_id),
          input_cost: String(model.input_cost),
          output_cost: String(model.output_cost),
          is_active: model.is_active,
        });
        setLoading(false);
      })
      .catch((err) => {
        setError(err.message);
        setLoading(false);
      });
  }, [modelId]);

  const handleChange = (
    e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>
  ) => {
    const { name, value, type } = e.target;
    setFormData((prev) => ({
      ...prev,
      [name]:
        type === "checkbox" ? (e.target as HTMLInputElement).checked : value,
    }));
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setSaving(true);
    setError(null);

    try {
      await adminApi.models.update(modelId, {
        ...formData,
        provider_id: Number(formData.provider_id),
        input_cost: parseFloat(formData.input_cost) || 0,
        output_cost: parseFloat(formData.output_cost) || 0,
      });
      router.push("/admin/models");
    } catch (err: any) {
      setError(err.message);
      setSaving(false);
    }
  };

  if (loading) return <div className={styles.loading}>⏳ در حال بارگذاری...</div>;
  if (error) return <div className={styles.error}>❌ {error}</div>;

  return (
    <div className={styles.page}>
      <h1 className={styles.title}>✏️ ویرایش مدل: {formData.name}</h1>

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
            />
          </div>
          <div className={styles.field}>
            <label>نام نمایشی</label>
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
            <label>نام مدل (API)</label>
            <input
              type="text"
              name="model_name"
              value={formData.model_name}
              onChange={handleChange}
              required
            />
          </div>
          <div className={styles.field}>
            <label>ارائه‌دهنده</label>
            <select
              name="provider_id"
              value={formData.provider_id}
              onChange={handleChange}
              required
            >
              <option value="">انتخاب کنید...</option>
              {providers.map((p) => (
                <option key={p.id} value={p.id}>
                  {p.name} ({p.key})
                </option>
              ))}
            </select>
          </div>
        </div>

        <div className={styles.row}>
          <div className={styles.field}>
            <label>هزینه ورودی (به ازای ۱ میلیون توکن)</label>
            <input
              type="number"
              step="0.0001"
              name="input_cost"
              value={formData.input_cost}
              onChange={handleChange}
              placeholder="0"
            />
          </div>
          <div className={styles.field}>
            <label>هزینه خروجی (به ازای ۱ میلیون توکن)</label>
            <input
              type="number"
              step="0.0001"
              name="output_cost"
              value={formData.output_cost}
              onChange={handleChange}
              placeholder="0"
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
            onClick={() => router.push("/admin/models")}
          >
            انصراف
          </button>
        </div>
      </form>
    </div>
  );
}