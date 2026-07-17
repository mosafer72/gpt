"use client";

import { useState, useEffect } from "react";
import { useRouter } from "next/navigation";
import { adminApi, AIModel } from "@/lib/admin_api";
import styles from "./page.module.css";

export default function CreatePlanPage() {
  const router = useRouter();
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [models, setModels] = useState<AIModel[]>([]);
  const [loadingModels, setLoadingModels] = useState(true);

  const [formData, setFormData] = useState({
    name: "",
    display_name: "",
    price: "",
    duration_days: "30",
    daily_message_limit: "10",
    token_limit: "0",
    features: "",
    capabilities: "",
    is_active: true,
    model_ids: [] as number[],
  });

  // دریافت لیست مدل‌ها
  useEffect(() => {
    adminApi.models
      .list()
      .then((data) => {
        setModels(data);
        setLoadingModels(false);
      })
      .catch((err) => {
        console.error("Error loading models:", err);
        setLoadingModels(false);
      });
  }, []);

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
    setLoading(true);
    setError(null);

    try {
      const data = {
        name: formData.name,
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

      await adminApi.plans.create(data);
      router.push("/admin/plans");
    } catch (err: any) {
      if (err.message.includes("JSON")) {
        setError("خطا در فرمت JSON. لطفاً JSON معتبر وارد کنید.");
      } else {
        setError(err.message);
      }
      setLoading(false);
    }
  };

  return (
    <div className={styles.page}>
      <h1 className={styles.title}>➕ ایجاد پلن جدید</h1>

      {error && <div className={styles.error}>{error}</div>}

      <form className={styles.form} onSubmit={handleSubmit}>
        <div className={styles.row}>
          <div className={styles.field}>
            <label>
              کلید داخلی (شناسه) <span className={styles.required}>*</span>
            </label>
            <input
              type="text"
              name="name"
              value={formData.name}
              onChange={handleChange}
              placeholder="مثلاً: basic"
              required
            />
            <small>یک شناسه یکتا برای این پلن (حروف کوچک انگلیسی)</small>
          </div>
          <div className={styles.field}>
            <label>
              نام نمایشی <span className={styles.required}>*</span>
            </label>
            <input
              type="text"
              name="display_name"
              value={formData.display_name}
              onChange={handleChange}
              placeholder="مثلاً: پایه"
              required
            />
          </div>
        </div>

        <div className={styles.row}>
          <div className={styles.field}>
            <label>
              قیمت (ریال) <span className={styles.required}>*</span>
            </label>
            <input
              type="number"
              name="price"
              value={formData.price}
              onChange={handleChange}
              placeholder="0"
              required
            />
            <small>برای پلن رایگان مقدار ۰ وارد کنید</small>
          </div>
          <div className={styles.field}>
            <label>
              مدت (روز) <span className={styles.required}>*</span>
            </label>
            <input
              type="number"
              name="duration_days"
              value={formData.duration_days}
              onChange={handleChange}
              required
            />
            <small>مدت اعتبار اشتراک به روز</small>
          </div>
        </div>

        <div className={styles.row}>
          <div className={styles.field}>
            <label>
              محدودیت پیام روزانه <span className={styles.required}>*</span>
            </label>
            <input
              type="number"
              name="daily_message_limit"
              value={formData.daily_message_limit}
              onChange={handleChange}
              required
            />
          </div>
          <div className={styles.field}>
            <label>
              محدودیت توکن <span className={styles.required}>*</span>
            </label>
            <input
              type="number"
              name="token_limit"
              value={formData.token_limit}
              onChange={handleChange}
              required
            />
            <small>مجموع توکن‌های مجاز برای این پلن</small>
          </div>
        </div>

        <div className={styles.field}>
          <label>ویژگی‌ها (هر خط یک ویژگی)</label>
          <textarea
            name="features"
            value={formData.features}
            onChange={handleChange}
            rows={4}
            placeholder={`مثلاً:
حافظه گفتگو
مدل‌های پایه
پشتیبانی ۲۴/۷`}
          />
          <small>هر ویژگی را در یک خط جداگانه وارد کنید</small>
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
          {loadingModels ? (
            <div className={styles.loadingModels}>⏳ در حال بارگذاری مدل‌ها...</div>
          ) : (
            <div className={styles.modelsGrid}>
              {models.length === 0 ? (
                <div className={styles.emptyModels}>
                  هیچ مدلی یافت نشد. ابتدا مدل‌ها را ایجاد کنید.
                </div>
              ) : (
                models.map((model) => (
                  <label key={model.id} className={styles.modelCheckbox}>
                    <input
                      type="checkbox"
                      checked={formData.model_ids.includes(model.id)}
                      onChange={() => handleModelToggle(model.id)}
                    />
                    <span className={styles.modelName}>{model.name}</span>
                    <span className={styles.modelKey}>({model.key})</span>
                  </label>
                ))
              )}
            </div>
          )}
          <small>مدل‌هایی که کاربران این پلن به آنها دسترسی دارند</small>
        </div>

        <div className={styles.actions}>
          <button type="submit" className={styles.submitBtn} disabled={loading}>
            {loading ? "در حال ذخیره..." : "ذخیره پلن"}
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