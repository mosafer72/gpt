'use client';

import { useState, FormEvent } from 'react';
import styles from './LoginForm.module.css';

interface LoginFormProps {
  onSuccess: (phone: string) => void;
}

export default function LoginForm({ onSuccess }: LoginFormProps) {
  const [phone, setPhone] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();
    if (!/^09[0-9]{9}$/.test(phone)) {
      setError('شماره موبایل باید با ۰۹ شروع و ۱۱ رقم باشد.');
      return;
    }
    setError('');
    setLoading(true);
    try {
      const res = await fetch(`${process.env.NEXT_PUBLIC_API_URL}/auth/send-code`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ phone }),
      });
      const data = await res.json();
      if (!res.ok) throw new Error(data.error || 'خطا در ارسال کد');
      sessionStorage.setItem('verify_phone', phone);
      onSuccess(phone);
    } catch (err: any) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className={styles.container}>
      <div className={styles.card}>
        <div className={styles.brand}>
          <span className={styles.logo}>✨</span>
          <span className={styles.brandName}>نابغه</span>
        </div>
        <h1 className={styles.title}>خوش آمدید 👋</h1>
        <p className={styles.subtitle}>شماره موبایل خود را وارد کنید تا کد تایید دریافت کنید.</p>
        <form onSubmit={handleSubmit} className={styles.form}>
          <div className={styles.inputGroup}>
            <label className={styles.label}>شماره موبایل</label>
            <div className={styles.inputWrapper}>
              <span className={styles.icon}>📱</span>
              <input
                type="tel"
                className={styles.input}
                placeholder="۰۹۱۲۳۴۵۶۷۸۹"
                value={phone}
                onChange={(e) => setPhone(e.target.value)}
                maxLength={11}
                disabled={loading}
                autoFocus
              />
            </div>
            {error && <p className={styles.error}>{error}</p>}
          </div>
          <button type="submit" className={styles.button} disabled={loading}>
            {loading ? 'ارسال...' : 'ارسال کد تایید'}
          </button>
        </form>
        <p className={styles.footer}>
          با ورود، شما با <a href="#">شرایط و قوانین</a> موافقت می‌کنید.
        </p>
      </div>
    </div>
  );
}