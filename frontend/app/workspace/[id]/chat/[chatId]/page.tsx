"use client";

import { useEffect, useState } from "react";
import { useParams } from "next/navigation";
import ChatInterface from "@/components/Chat/ChatInterface";
import { getWorkspace, getAvailableModels } from "@/lib/api";

export default function ChatByIdPage() {
  const params = useParams();
  const workspaceId = Number(params.id);
  const chatId = Number(params.chatId);

  const [workspace, setWorkspace] = useState<any>(null);
  const [models, setModels] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    async function load() {
      try {
        const workspaceResponse = await getWorkspace(workspaceId);
        const modelsResponse = await getAvailableModels(workspaceId);

        setWorkspace(workspaceResponse.workspace);
        setModels(modelsResponse.models ?? []);
      } catch (error) {
        console.log("Workspace Load Error:", error);
      } finally {
        setLoading(false);
      }
    }

    if (workspaceId) {
      load();
    }
  }, [workspaceId]);

  if (loading || !workspace) {
    return (
      <div style={{
        display: "flex",
        flexDirection: "column",
        justifyContent: "center",
        alignItems: "center",
        height: "100vh",
        background: "#0f172a",
        color: "#94a3b8",
        gap: "16px"
      }}>
        <div style={{
          width: "40px",
          height: "40px",
          border: "3px solid rgba(59, 130, 246, 0.2)",
          borderTopColor: "#3b82f6",
          borderRadius: "50%",
          animation: "spin 0.8s linear infinite"
        }}></div>
        <span>در حال بارگذاری...</span>
        <style jsx>{`
          @keyframes spin { to { transform: rotate(360deg); } }
        `}</style>
      </div>
    );
  }

  return (
    <ChatInterface
      workspace={workspace}
      workspaceId={workspaceId}
      models={models}
      initialChat={null} // اجازه می‌دهیم خود ChatInterface چت مربوط به URL را مدیریت کند
    />
  );
}