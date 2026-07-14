// hooks/useChatScroll.ts
"use client";

import { useCallback, useEffect, useRef, RefObject } from "react";

interface UseChatScrollOptions {
  inputRef?: RefObject<HTMLElement | null>;
  autoScroll?: boolean; // اضافه کردن گزینه برای کنترل اسکرول خودکار
}

export function useChatScroll(options: UseChatScrollOptions = {}) {
  const { inputRef, autoScroll = false } = options;

  const containerRef = useRef<HTMLDivElement | null>(null);
  const lastMessageRef = useRef<HTMLDivElement | null>(null);

  const userAtBottom = useRef(true);
  const initialScrollDone = useRef(false);

  const checkBottom = useCallback(() => {
    const container = containerRef.current;
    if (!container) return true;
    const distance =
      container.scrollHeight - container.scrollTop - container.clientHeight;
    return distance < 120;
  }, []);

  // تابع اسکرول جدید با استفاده از scrollIntoView
  const scrollToLastMessage = useCallback(
    (behavior: ScrollBehavior = "smooth") => {
      const lastMessage = lastMessageRef.current;
      if (!lastMessage) {
        // اگر ref موجود نبود، از کانتینر استفاده کن
        const container = containerRef.current;
        if (container) {
          container.scrollTo({
            top: container.scrollHeight,
            behavior,
          });
        }
        return;
      }

      // استفاده از scrollIntoView برای دقت بیشتر
      lastMessage.scrollIntoView({
        behavior,
        block: "end",
      });
    },
    []
  );

  const handleScroll = useCallback(() => {
    userAtBottom.current = checkBottom();
  }, [checkBottom]);

  useEffect(() => {
    const container = containerRef.current;
    if (!container) return;

    container.addEventListener("scroll", handleScroll);
    return () => container.removeEventListener("scroll", handleScroll);
  }, [handleScroll]);

  // اسکرول اولیه فقط در صورت فعال بودن autoScroll
  useEffect(() => {
    if (!autoScroll) return;
    if (!containerRef.current || !lastMessageRef.current) return;
    if (!initialScrollDone.current) {
      scrollToLastMessage("auto");
      initialScrollDone.current = true;
    }
  }, [autoScroll, scrollToLastMessage]);

  const onMessagesChanged = useCallback(() => {
    if (!autoScroll) return;
    if (!containerRef.current) return;

    if (!initialScrollDone.current) {
      scrollToLastMessage("auto");
      initialScrollDone.current = true;
      return;
    }

    if (userAtBottom.current) {
      scrollToLastMessage("smooth");
    }
  }, [autoScroll, scrollToLastMessage]);

  const preserveScroll = useCallback(
    async (callback: () => Promise<void>) => {
      const container = containerRef.current;
      if (!container) {
        await callback();
        return;
      }

      const oldHeight = container.scrollHeight;
      const oldTop = container.scrollTop;

      await callback();

      requestAnimationFrame(() => {
        const newHeight = container.scrollHeight;
        container.scrollTop = oldTop + (newHeight - oldHeight);
      });
    },
    []
  );

  return {
    containerRef,
    lastMessageRef,
    scrollToBottom: scrollToLastMessage,
    onMessagesChanged,
    preserveScroll,
    userAtBottom,
  };
}