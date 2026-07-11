'use client';

import { Chat } from '@/types';
import styles from './Sidebar.module.css';

export default function Sidebar({
  chats, currentChatId, onSelectChat, onNewChat, onDeleteChat, isLoading
}: any) {
  return (
    <aside className={styles.sidebar}>
      <div className={styles.header}>
        <span className={styles.logo}>✨ اکانتسرا</span>
        <button className={styles.newBtn} onClick={onNewChat}>+ جدید</button>
      </div>
      <ul className={styles.list}>
        {isLoading ? (
          <li className={styles.item}>در حال بارگذاری...</li>
        ) : chats.length === 0 ? (
          <li className={styles.item}>هیچ چتی وجود ندارد</li>
        ) : (
          chats.map((chat: Chat) => (
            <li
              key={chat.id}
              className={`${styles.item} ${currentChatId === chat.id ? styles.active : ''}`}
              onClick={() => onSelectChat(chat)}
            >
              <span>{chat.title || 'چت جدید'}</span>
              <button className={styles.deleteBtn} onClick={(e) => {
                e.stopPropagation();
                onDeleteChat(chat.id);
              }}>✕</button>
            </li>
          ))
        )}
      </ul>
    </aside>
  );
}