"use client";

import { useEffect, useState } from "react";
import ChatInterface from "@/components/Chat/ChatInterface";
import ModelSelector from "@/components/Chat/ModelSelector";
import { AIModel } from "@/types";
import { getAvailableModels } from "@/lib/api";
import styles from "./page.module.css";

export default function ChatPage() {
  const [showModels, setShowModels] = useState(true); // مقدار اولیه false
  const [models, setModels] = useState<AIModel[]>([]);
  const [selectedModel, setSelectedModel] = useState<AIModel | null>(null);
  const [newChatTrigger, setNewChatTrigger] = useState(0);
  const [error, setError] = useState<string | null>(null);

  // تابع دریافت مدل‌ها از سرور
  const fetchModels = async () => {
    setError(null);
    try {
      const response = await getAvailableModels();
      if (response?.models?.length) {
        setModels(response.models);
        return response.models;
      } else {
        setError("هیچ مدلی موجود نیست");
        return [];
      }
    } catch (err: any) {
      console.error("Error fetching models:", err);
      setError(err.message || "خطا در دریافت مدل‌ها");
      return [];
    }
  };

  // باز کردن مودال انتخاب مدل
  const openNewChat = async () => {
    setNewChatTrigger((x) => x + 1);
    // اگر مدل‌ها قبلاً دریافت نشده‌اند، دریافت کن
    if (models.length === 0) {
      await fetchModels();
    }
    setShowModels(true);
  };

  // بارگذاری اولیه: دریافت مدل‌ها و بررسی مدل ذخیره‌شده
  useEffect(() => {
    const saved = localStorage.getItem("selected_model");
    let hasSavedModel = false;

    if (saved) {
      try {
        const parsed = JSON.parse(saved);
        setSelectedModel(parsed);
        hasSavedModel = true;
      } catch (e) {
        console.error("Invalid saved model:", e);
      }
    }

    // دریافت مدل‌ها از سرور (همیشه)
    fetchModels().then((fetchedModels) => {
      // اگر مدلی ذخیره نشده یا مدل ذخیره‌شده در لیست موجود نیست، مودال را باز کن
      if (!hasSavedModel) {
        setShowModels(true);
      } else if (selectedModel && fetchedModels.length > 0) {
        // بررسی اینکه مدل ذخیره‌شده هنوز معتبر است
        const stillValid = fetchedModels.some(
          (m) => m.id === selectedModel.id
        );
        if (!stillValid) {
          setShowModels(true);
        }
      }
    });
  }, []); // فقط یک بار اجرا شود

  return (
    <>
      {error && (
        <div
          style={{
            position: "fixed",
            top: 20,
            left: "50%",
            transform: "translateX(-50%)",
            background: "#ff6b6b",
            color: "white",
            padding: "12px 24px",
            borderRadius: "8px",
            zIndex: 9999,
            boxShadow: "0 4px 12px rgba(0,0,0,0.2)",
          }}
        >
          ⚠️ {error}
          <button
            onClick={() => setError(null)}
            style={{
              marginLeft: 12,
              background: "none",
              border: "none",
              color: "white",
              cursor: "pointer",
            }}
          >
            ✕
          </button>
        </div>
      )}

      {/* مودال در یک wrapper که فقط روی بخش چت قرار می‌گیرد */}
      {showModels && (
        <div className={styles.modelModalWrapper}>
          <ModelSelector
            models={models}
            onClose={() => setShowModels(false)}
            onSelect={(model) => {
              setSelectedModel(model);
              localStorage.setItem("selected_model", JSON.stringify(model));
              setShowModels(false);
            }}
          />
        </div>
      )}

      <ChatInterface
        selectedModel={selectedModel}
        onNewChat={openNewChat}
        newChatTrigger={newChatTrigger}
      />
    </>
  );
}