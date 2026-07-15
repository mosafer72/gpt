"use client";

import { useEffect, useState } from "react";

import ChatInterface from "@/components/Chat/ChatInterface";
import ModelSelector from "@/components/Chat/ModelSelector";

import { AIModel } from "@/types";
import { getAvailableModels } from "@/lib/api";

export default function ChatPage() {
  const [showModels, setShowModels] = useState(false);

  const [models, setModels] = useState<AIModel[]>([]);

  const [selectedModel, setSelectedModel] = useState<AIModel | null>(null);

  const [loadingModels, setLoadingModels] = useState(false);

  const openModelSelector = async () => {
    try {
      setLoadingModels(true);

      const response = await getAvailableModels();

      setModels(response.models);

      setShowModels(true);
    } catch (error) {
      console.error("Load models failed", error);
    } finally {
      setLoadingModels(false);
    }
  };
  useEffect(() => {
    const saved = localStorage.getItem("selected_model");

    if (saved) {
      setSelectedModel(JSON.parse(saved));
    }
  }, []);

  return (
    <>
      {showModels && (
        <ModelSelector
          models={models}
          onClose={() => {
            setShowModels(false);
          }}
          onSelect={(model) => {
            setSelectedModel(model);

            localStorage.setItem("selected_model", JSON.stringify(model));

            setShowModels(false);
          }}
        />
      )}

      <ChatInterface
        selectedModel={selectedModel}
        onNewChat={openModelSelector}
      />
    </>
  );
}
