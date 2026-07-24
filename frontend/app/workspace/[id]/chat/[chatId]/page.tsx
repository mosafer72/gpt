"use client";

import { useEffect, useState } from "react";
import { useParams } from "next/navigation";

import ChatInterface from "@/components/Chat/ChatInterface";

import { getChat, getWorkspace, getAvailableModels } from "@/lib/api";

export default function ChatByIdPage() {
  const params = useParams();

  const workspaceId = Number(params.id);

  const chatId = Number(params.chatId);

  const [workspace, setWorkspace] = useState<any>(null);

  const [chat, setChat] = useState<any>(null);

  const [models, setModels] = useState<any[]>([]);

  const [loading, setLoading] = useState(true);

  useEffect(() => {
    async function load() {
      try {
        const workspaceResponse = await getWorkspace(workspaceId);

        const chatResponse = await getChat(workspaceId, chatId);

        const modelsResponse = await getAvailableModels(workspaceId);

        setWorkspace(workspaceResponse.workspace);

        const chatData = chatResponse.chat;

        setChat({
          ...chatData,

          messages: [...(chatData.messages ?? [])].sort(
            (a, b) =>
              new Date(a.created_at).getTime() -
              new Date(b.created_at).getTime(),
          ),
        });

        setModels(modelsResponse.models ?? []);
      } catch (error) {
        console.log("Chat Load Error:", error);
      } finally {
        setLoading(false);
      }
    }

    if (workspaceId && chatId) {
      load();
    }
  }, [workspaceId, chatId]);

  if (loading) {
    return <div>در حال بارگذاری چت...</div>;
  }

  if (!chat) {
    return <div>چت پیدا نشد</div>;
  }

  return (
    <ChatInterface
      workspace={workspace}
      workspaceId={workspaceId}
      models={models}
      initialChat={chat}
    />
  );
}
