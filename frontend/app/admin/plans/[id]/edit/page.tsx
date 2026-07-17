"use client";

import { useState, useEffect } from "react";
import { useParams, useRouter } from "next/navigation";
import { adminApi, Plan, AIModel } from "@/lib/admin_api";
import styles from "./page.module.css";

export default function EditPlanPage() {
  const params = useParams();
  const router = useRouter();
  const planId = Number(params.id);

  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [models, setModels] = useState<AIModel[]>([]);
  const [formData, setFormData] = useState({
    name: "",
    display_name: "",
    price: "",
    duration_days: "",
    daily_message_limit: "",
    token_limit: "",
    features: "",
    capabilities: "",
    is_active: true,
    model_ids: [] as number[],
  });

  // دریافت اطلاعات پلن و لیست مدل‌ها
  useEffect(() => {
    const fetchData = async () => {
      try {
        const [plan, modelsData] = await Promise.all([
          adminApi.plans.get(planId),
          adminApi.models.list(),
        ]);

        console.log("✅ Plan data:", plan);
        console.log("✅ Plan models:", plan.models);
        console.log("✅ All models:", modelsData);

        setModels(modelsData);

        // استخراج شناسه‌های مدل‌های مرتبط با پلن
        const planModelIds = plan.models?.map((m) => m.id) || [];

        // پردازش features
        let featuresString = "";
        if (Array.isArray(plan.features)) {
          featuresString = plan.features.join("\n");
        } else if (typeof plan.features === "string") {
          try {
            const parsed = JSON.parse(plan.features);
            featuresString = Array.isArray(parsed) ? parsed.join("\n") : plan.features;
          } catch {
            featuresString = plan.features;
          }
        }

        // پردازش capabilities
        let capabilitiesString = "";
        if (plan.capabilities) {
          if (typeof plan.capabilities === "object") {
            capabilitiesString = JSON.stringify(plan.capabilities, null, 2);
          } else if (typeof plan.capabilities === "string") {
            try {
              const parsed = JSON.parse(plan.capabilities);
              capabilitiesString = JSON.stringify(parsed, null, 2);
            } catch {
              capabilitiesString = plan.capabilities;
            }
          }
        }

        setFormData({
          name: plan.name || "",
          display_name: plan.display_name || "",
          price: String(plan.price ?? 0),
          duration_days: String(plan.duration_days ?? 30),
          daily_message_limit: String(plan.daily_message_limit ?? 10),
          token_limit: String(plan.token_limit ?? 0),
          features: featuresString,
          capabilities: capabilitiesString,
          is_active: plan.is_active ?? true,
          model_ids: planModelIds,
        });

        setLoading(false);
      } catch (err: any) {
        console.error("❌ Error loading data:", err);
        setError(err.message || "خطا در بارگذاری داده");
        setLoading(false);
      }
    };

    if (planId && !isNaN(planId)) {
      fetchData();
    } else {
      setError("شناسه پلن نامعتبر");
      setLoading(false);
    }
  }, [planId]);

  const handleChange = (
    e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>
  ) => {
    const { name, value, type } = e.target;
    setFormData((prev) => ({
      ...prev,
      [name]:
        type === "checkbox" ? (e.target as HTMLInputElement).checked : value,
    }));
  };

  const handleModelToggle = (modelId: number) => {
    setFormData((prev) => {
      const ids = prev.model_ids.includes(modelId)
        ? prev.model_ids.filter((id) => id !== modelId)
        : [...prev.model_ids, modelId];
      return { ...prev, model_ids: ids };
    });
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setSaving(true);
    setError(null);

    try {
      const data = {
        display_name: formData.display_name,
        price: parseFloat(formData.price) || 0,
        duration_days: parseInt(formData.duration_days) || 30,
        daily_message_limit: parseInt(formData.daily_message_limit) || 10,
        token_limit: parseInt(formData.token_limit) || 0,
        features: formData.features
          ? formData.features.split("\n").filter((f) => f.trim())
          : [],
        capabilities: formData.capabilities
          ? JSON.parse(formData.capabilities)
          : {},
        is_active: formData.is_active,
        model_ids: formData.model_ids,
      };

      console.log("📤 Sending data:", data);

      await adminApi.plans.update(planId, data);
      router.push("/admin/plans");
    } catch (err: any) {
      if (err.message.includes("JSON")) {
        setError("خطا در فرمت JSON. لطفاً JSON معتبر وارد کنید.");
      } else {
        setError(err.message);
      }
      setSaving(false);
    }
  };

  if (loading) return <div className={styles.loading}>⏳ در حال بارگذاری...</div>;
  if (error) return <div className={styles.error}>❌ {error}</div>;

  return (
    <div className={styles.page}>
      <h1 className={styles.title}>✏️ ویرایش پلن: {formData.display_name}</h1>

      {error && <div className={styles.error}>{error}</div>}

      <form className={styles.form} onSubmit={handleSubmit}>
        <div className={styles.row}>
          <div className={styles.field}>
            <label>کلید داخلی (شناسه)</label>
            <input
              type="text"
              name="name"
              value={formData.name}
              disabled
            />
            <small>شناسه قابل ویرایش نیست</small>
          </div>
          <div className={styles.field}>
            <label>نام نمایشی <span className={styles.required}>*</span></label>
            <input
              type="text"
              name="display_name"
              value={formData.display_name}
              onChange={handleChange}
              required
            />
          </div>
        </div>

        <div className={styles.row}>
          <div className={styles.field}>
            <label>قیمت (ریال) <span className={styles.required}>*</span></label>
            <input
              type="number"
              name="price"
              value={formData.price}
              onChange={handleChange}
              required
            />
          </div>
          <div className={styles.field}>
            <label>مدت (روز) <span className={styles.required}>*</span></label>
            <input
              type="number"
              name="duration_days"
              value={formData.duration_days}
              onChange={handleChange}
              required
            />
          </div>
        </div>

        <div className={styles.row}>
          <div className={styles.field}>
            <label>محدودیت پیام روزانه <span className={styles.required}>*</span></label>
            <input
              type="number"
              name="daily_message_limit"
              value={formData.daily_message_limit}
              onChange={handleChange}
              required
            />
          </div>
          <div className={styles.field}>
            <label>محدودیت توکن <span className={styles.required}>*</span></label>
            <input
              type="number"
              name="token_limit"
              value={formData.token_limit}
              onChange={handleChange}
              required
            />
          </div>
        </div>

        <div className={styles.field}>
          <label>ویژگی‌ها (هر خط یک ویژگی)</label>
          <textarea
            name="features"
            value={formData.features}
            onChange={handleChange}
            rows={4}
            placeholder="مثلاً:&#10;حافظه گفتگو&#10;مدل‌های پایه&#10;پشتیبانی ۲۴/۷"
          />
        </div>

        <div className={styles.field}>
          <label>قابلیت‌ها (JSON)</label>
          <textarea
            name="capabilities"
            value={formData.capabilities}
            onChange={handleChange}
            rows={6}
            placeholder={`مثلاً:
{
  "memory": true,
  "planner": false,
  "max_tokens": 2000,
  "temperature": 0.7
}`}
          />
          <small>فرمت JSON معتبر وارد کنید</small>
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

        <div className={styles.field}>
          <label>مدل‌های قابل دسترس</label>
          <div className={styles.modelsGrid}>
            {models.map((model) => (
              <label key={model.id} className={styles.modelCheckbox}>
                <input
                  type="checkbox"
                  checked={formData.model_ids.includes(model.id)}
                  onChange={() => handleModelToggle(model.id)}
                />
                <span className={styles.modelName}>{model.name}</span>
                <span className={styles.modelKey}>({model.key})</span>
              </label>
            ))}
          </div>
          {models.length === 0 && (
            <p className={styles.emptyModels}>
              هیچ مدلی یافت نشد. ابتدا مدل‌ها را ایجاد کنید.
            </p>
          )}
          <small>مدل‌هایی که کاربران این پلن به آنها دسترسی دارند</small>
        </div>

        <div className={styles.actions}>
          <button type="submit" className={styles.submitBtn} disabled={saving}>
            {saving ? "در حال ذخیره..." : "ذخیره تغییرات"}
          </button>
          <button
            type="button"
            className={styles.cancelBtn}
            onClick={() => router.push("/admin/plans")}
          >
            انصراف
          </button>
        </div>
      </form>
    </div>
  );
}