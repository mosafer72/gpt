'use client';


import { Message } from '@/types';
import styles from './ChatMessage.module.css';

export default function ChatMessage({ message }: { message: Message }) {
  const isUser = message.role === 'user';
  const isImage = message.content.startsWith('data:image/');

  return (
    <div className={`${styles.wrapper} ${isUser ? styles.user : styles.assistant}`}>
      <div className={styles.avatar}>
        {isUser ? 'U' : 'AI'}
      </div>
      <div className={styles.bubble}>
        {isImage ? (
          <img src={message.content} alt="تصویر" className={styles.image} />
        ) : (
          <p className={styles.text}>{message.content}</p>
        )}
      </div>
    </div>
  );
}