'use client';

import { useState, FormEvent } from 'react';
import styles from './LoginForm.module.css';

interface LoginFormProps {
  onSuccess: (phone: string) => void;
}

export default function LoginForm({ onSuccess }: LoginFormProps) {
  const [mode, setMode] = useState<'login' | 'register'>('register');
  const [name, setName] = useState('');
  const [phone, setPhone] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  // تابع ورود
  const handleLogin = async (e: FormEvent) => {
    e.preventDefault();
    if (!/^09[0-9]{9}$/.test(phone)) {
      setError('شماره موبایل معتبر نیست.');
      return;
    }
    setError('');
    setLoading(true);

    try {
      const res = await fetch(
        `${process.env.NEXT_PUBLIC_API_URL}/auth/send-login-code`,
        {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ phone }),
        }
      );
      const data = await res.json();
      if (!res.ok) throw new Error(data.error || 'خطا در ارسال کد ورود');

      sessionStorage.setItem('verify_phone', phone);
      onSuccess(phone);
    } catch (err: any) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  // تابع ثبت‌نام
  const handleRegister = async (e: FormEvent) => {
    e.preventDefault();
    if (name.trim().length < 2) {
      setError('نام باید حداقل ۲ کاراکتر باشد.');
      return;
    }
    if (!/^09[0-9]{9}$/.test(phone)) {
      setError('شماره موبایل معتبر نیست.');
      return;
    }
    setError('');
    setLoading(true);

    try {
      const res = await fetch(
        `${process.env.NEXT_PUBLIC_API_URL}/auth/send-register-code`,
        {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            phone,
            name: name.trim(),
          }),
        }
      );
      const data = await res.json();
      if (!res.ok) throw new Error(data.error || 'خطا در ارسال کد ثبت‌نام');

      sessionStorage.setItem('verify_phone', phone);
      sessionStorage.setItem('verify_name', name.trim());
      onSuccess(phone);
    } catch (err: any) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  // انتخاب تابع مناسب بر اساس حالت
  const handleSubmit = (e: FormEvent) => {
    if (mode === 'login') {
      handleLogin(e);
    } else {
      handleRegister(e);
    }
  };

  return (
    <div className={styles.page}>
      <div className={styles.glow1} />
      <div className={styles.glow2} />

      <div className={styles.card}>
        {/* تب‌ها */}
        <div className={styles.tabs}>
          <button
            type="button"
            className={`${styles.tab} ${mode === 'login' ? styles.activeTab : ''}`}
            onClick={() => setMode('login')}
            disabled={loading}
          >
            ورود
          </button>
          <button
            type="button"
            className={`${styles.tab} ${mode === 'register' ? styles.activeTab : ''}`}
            onClick={() => setMode('register')}
            disabled={loading}
          >
            ثبت‌نام
          </button>
        </div>

        <div className={styles.header}>
          <div className={styles.logo}>✨</div>
          <h1>اکانتسرا</h1>
          <p>
            {mode === 'login'
              ? 'ورود به پلتفرم هوش مصنوعی'
              : 'ثبت‌نام در پلتفرم هوش مصنوعی'}
          </p>
        </div>

        <div className={styles.features}>
          <div>🤖 ChatGPT</div>
          <div>⚡ پاسخ سریع</div>
          <div>🔒 امن و مطمئن</div>
        </div>

        <form onSubmit={handleSubmit} className={styles.form}>
          {mode === 'register' && (
            <div className={styles.field}>
              <label>نام و نام خانوادگی</label>
              <div className={styles.inputWrapper}>
                <span>👤</span>
                <input
                  type="text"
                  placeholder="محمدرضا احمدی"
                  value={name}
                  onChange={(e) => setName(e.target.value)}
                  disabled={loading}
                />
              </div>
            </div>
          )}

          <div className={styles.field}>
            <label>شماره موبایل</label>
            <div className={styles.inputWrapper}>
              <span>📱</span>
              <input
                type="tel"
                placeholder="۰۹۱۲۳۴۵۶۷۸۹"
                value={phone}
                onChange={(e) => setPhone(e.target.value)}
                maxLength={11}
                disabled={loading}
              />
            </div>
          </div>

          {error && <div className={styles.error}>{error}</div>}

          <button type="submit" className={styles.button} disabled={loading}>
            {loading
              ? 'در حال ارسال...'
              : mode === 'login'
              ? 'ورود'
              : 'ثبت‌نام و دریافت کد'}
          </button>
        </form>

        <p className={styles.footer}>
          با ورود، شما با شرایط و قوانین موافقت می‌کنید.
        </p>
      </div>
    </div>
  );
}