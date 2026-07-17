"use client";

import { useEffect, useState } from "react";
import { useParams } from "next/navigation";
import ChatInterface from "@/components/Chat/ChatInterface";
import ModelSelector from "@/components/Chat/ModelSelector";
import { getChat, getAvailableModels } from "@/lib/api";
import { AIModel, Chat } from "@/types";

export default function ChatByIdPage() {
  const params = useParams();
  const chatSlug = params.id as string;
  const chatId = Number(chatSlug); // تبدیل به عدد

  const [chat, setChat] = useState<Chat | null>(null);
  const [selectedModel, setSelectedModel] = useState<AIModel | null>(null);
  const [showModels, setShowModels] = useState(false);
  const [models, setModels] = useState<AIModel[]>([]);
  const [newChatTrigger, setNewChatTrigger] = useState(0);
  const [error, setError] = useState<string | null>(null);

  const openNewChat = async () => {
    setNewChatTrigger((x) => x + 1);
    setError(null);

    try {
      const response = await getAvailableModels();
      if (response?.models?.length) {
        setModels(response.models);
        setShowModels(true);
      } else {
        setError("هیچ مدلی موجود نیست");
      }
    } catch (err: any) {
      console.error(err);
      setError(err.message || "خطا در دریافت مدل‌ها");
    }
  };

  useEffect(() => {
    async function loadChat() {
      // اعتبارسنجی شناسه
      if (isNaN(chatId) || chatId <= 0) {
        setError("شناسه نامعتبر");
        return;
      }

      try {
        const fullChat = await getChat(chatId); // ارسال عدد
        setChat({
          ...fullChat,
          messages: [...fullChat.messages].sort(
            (a, b) =>
              new Date(a.created_at).getTime() -
              new Date(b.created_at).getTime(),
          ),
        });
        const saved = localStorage.getItem("selected_model");
        if (saved) {
          setSelectedModel(JSON.parse(saved));
        }
      } catch (error) {
        console.error("load chat failed:", error);
        setError("خطا در بارگذاری چت");
      }
    }

    if (chatSlug) {
      loadChat();
    }
  }, [chatSlug, chatId]);

  // اگر خطا یا در حال بارگذاری
  if (error) {
    return (
      <div
        style={{
          height: "100vh",
          display: "flex",
          justifyContent: "center",
          alignItems: "center",
          flexDirection: "column",
          gap: "12px",
        }}
      >
        <span style={{ fontSize: "2rem" }}>⚠️</span>
        <p>{error}</p>
        <button
          onClick={() => (window.location.href = "/chat")}
          style={{
            padding: "8px 24px",
            background: "#007aff",
            color: "white",
            border: "none",
            borderRadius: "8px",
            cursor: "pointer",
          }}
        >
          بازگشت به صفحه اصلی
        </button>
      </div>
    );
  }

  if (!chat) {
    return null; // یا یک اسپینر کوچک
  }

  return (
    <>
      {showModels && (
        <div className="modelModalWrapper">
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
        initialChat={chat}
        onNewChat={openNewChat}
        newChatTrigger={newChatTrigger}
      />
    </>
  );
}