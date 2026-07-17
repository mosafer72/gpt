'use client';

import { Chat } from '@/types';
import styles from './Sidebar.module.css';

interface SidebarProps {
  chats: Chat[];
  currentChatId?: number;
  onSelectChat: (chat: Chat) => void;
  onNewChat: () => void;
  onDeleteChat: (id: number) => void;
  isLoading: boolean;
  chatStreamStates?: Record<number, { isComplete: boolean; loading: boolean }>;
}

export default function Sidebar({
  chats,
  currentChatId,
  onSelectChat,
  onNewChat,
  onDeleteChat,
  isLoading,
  chatStreamStates = {},
}: SidebarProps) {
  return (
    <aside className={styles.sidebar}>
      <div className={styles.header}>
        <span className={styles.logo}>✨ اکانتسرا</span>
        <button className={styles.newBtn} onClick={onNewChat}>
          + جدید
        </button>
      </div>
      <ul className={styles.list}>
        {isLoading ? (
          <li className={styles.item}>در حال بارگذاری...</li>
        ) : chats.length === 0 ? (
          <li className={styles.item}>هیچ چتی وجود ندارد</li>
        ) : (
          chats.map((chat: Chat) => {
            const state = chatStreamStates[chat.id];
            const isComplete = state?.isComplete ?? true;
            const isStreaming = state?.loading ?? false;
            return (
              <li
                key={chat.id}
                className={`${styles.item} ${
                  currentChatId === chat.id ? styles.active : ''
                } ${isComplete ? styles.completed : ''} ${
                  isStreaming ? styles.streaming : ''
                }`}
                onClick={() => onSelectChat(chat)}
              >
                <span className={styles.chatTitle}>
                  {chat.title || 'چت جدید'}
                  {isStreaming && <span className={styles.streamingDot}>●</span>}
                  {isComplete && !isStreaming && (
                    <span className={styles.completedCheck}>✓</span>
                  )}
                </span>
                <button
                  className={styles.deleteBtn}
                  onClick={(e) => {
                    e.stopPropagation();
                    onDeleteChat(chat.id);
                  }}
                >
                  ✕
                </button>
              </li>
            );
          })
        )}
      </ul>
    </aside>
  );
}