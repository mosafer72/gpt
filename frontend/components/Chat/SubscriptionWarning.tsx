'use client';

import styles from './SubscriptionWarning.module.css';

interface SubscriptionWarningProps {
  hasSubscription: boolean;
  remainingMessages: number;
  dailyLimit: number;
}

export default function SubscriptionWarning({
  hasSubscription,
  remainingMessages,
  dailyLimit,
}: SubscriptionWarningProps) {
  // اگر اشتراک فعال دارد، هیچ هشداری نشان نده
  if (hasSubscription) return null;

  const isExhausted = remainingMessages <= 0;

  return (
    <div className={`${styles.warning} ${isExhausted ? styles.danger : styles.warning}`}>
      <div className={styles.icon}>
        {isExhausted ? '⛔' : '⚠️'}
      </div>
      <div className={styles.content}>
        {isExhausted ? (
          <>
            <span className={styles.title}>پیام‌های رایگان شما تمام شد!</span>
            <span className={styles.message}>
              برای ادامه‌ی مکالمه، لطفاً یکی از بسته‌های اشتراک را تهیه کنید.
            </span>
            <button
              className={styles.buyBtn}
              onClick={() => window.location.href = '/dashboard'}
            >
              خرید اشتراک
            </button>
          </>
        ) : (
          <>
            <span className={styles.title}>شما اشتراک فعال ندارید</span>
            <span className={styles.message}>
              امروز <strong>{remainingMessages}</strong> پیام رایگان باقی‌مانده است (از {dailyLimit}).
            </span>
          </>
        )}
      </div>
    </div>
  );
}