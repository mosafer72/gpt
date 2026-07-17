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
  const [chat, setChat] = useState<Chat | null>(null);
  const [selectedModel, setSelectedModel] = useState<AIModel | null>(null);
  const [showModels, setShowModels] = useState(false);
  const [models, setModels] = useState<AIModel[]>([]);
  const [newChatTrigger, setNewChatTrigger] = useState(0);
  const [error, setError] = useState<string | null>(null);

  // ✅ تعریف openNewChat برای صفحه /chat/[id]
  const openNewChat = async () => {
    // اول EmptyState را فعال کن
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
      try {
        const fullChat = await getChat(chatSlug);
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
        console.error("load chat failed", error);
      }
    }
    if (chatSlug) {
      loadChat();
    }
  }, [chatSlug]);

  if (!chat) {
    return (
      <div
        style={{
          height: "100vh",
          display: "flex",
          justifyContent: "center",
          alignItems: "center",
        }}
      >
        Loading...
      </div>
    );
  }

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

      {showModels && (
        <ModelSelector
          models={models}
          onClose={() => setShowModels(false)}
          onSelect={(model) => {
            setSelectedModel(model);
            localStorage.setItem("selected_model", JSON.stringify(model));
            setShowModels(false);
          }}
        />
      )}

      <ChatInterface
        selectedModel={selectedModel}
        initialChat={chat}
        onNewChat={openNewChat} // ✅ پاس داده می‌شود
        newChatTrigger={newChatTrigger}
      />
    </>
  );
}