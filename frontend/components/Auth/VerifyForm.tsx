'use client';

import { useState, FormEvent, useEffect, useRef } from 'react';
import { useRouter } from 'next/navigation';
import styles from './VerifyForm.module.css';

interface VerifyFormProps {
  phone: string;
  onBack: () => void;
  onSuccess: () => void;
}

export default function VerifyForm({ phone, onBack, onSuccess }: VerifyFormProps) {
  const [code, setCode] = useState(['', '', '', '', '', '']);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [timer, setTimer] = useState(120);
  const [canResend, setCanResend] = useState(false);
  const inputRefs = useRef<(HTMLInputElement | null)[]>([]);
  const router = useRouter();

  useEffect(() => {
    if (timer <= 0) { setCanResend(true); return; }
    const interval = setInterval(() => setTimer(prev => prev - 1), 1000);
    return () => clearInterval(interval);
  }, [timer]);

  useEffect(() => {
    inputRefs.current[0]?.focus();
  }, []);

  const handleChange = (index: number, value: string) => {
    if (value.length > 1) return;
    const newCode = [...code];
    newCode[index] = value;
    setCode(newCode);
    if (value && index < 5) inputRefs.current[index + 1]?.focus();
    if (error) setError('');
  };

  const handleKeyDown = (index: number, e: React.KeyboardEvent<HTMLInputElement>) => {
    if (e.key === 'Backspace' && !code[index] && index > 0) {
      inputRefs.current[index - 1]?.focus();
    }
    if (e.key === 'Enter') handleSubmit(e);
  };

  const handlePaste = (e: React.ClipboardEvent) => {
    e.preventDefault();
    const pasted = e.clipboardData.getData('text');
    if (!/^\d{6}$/.test(pasted)) return;
    setCode(pasted.split(''));
    inputRefs.current[5]?.focus();
  };

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();
    const fullCode = code.join('');
    if (fullCode.length !== 6) {
      setError('لطفاً کد ۶ رقمی را کامل وارد کنید.');
      return;
    }
    setError('');
    setLoading(true);
    try {
      const res = await fetch(`${process.env.NEXT_PUBLIC_API_URL}/auth/verify-code`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ phone, code: fullCode }),
      });
      const data = await res.json();
      if (!res.ok) throw new Error(data.error || 'کد اشتباه است.');
      localStorage.setItem('auth_token', data.token);
      localStorage.setItem('user', JSON.stringify(data.user));
      document.cookie = `auth_token=${data.token}; path=/; max-age=86400; SameSite=Lax`;
      onSuccess();
    } catch (err: any) {
      setError(err.message);
      setCode(['', '', '', '', '', '']);
      inputRefs.current[0]?.focus();
    } finally {
      setLoading(false);
    }
  };

  const handleResend = async () => {
    setLoading(true);
    try {
      const res = await fetch(`${process.env.NEXT_PUBLIC_API_URL}/auth/resend-code`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ phone }),
      });
      if (!res.ok) throw new Error('خطا در ارسال مجدد');
      setTimer(120);
      setCanResend(false);
      setCode(['', '', '', '', '', '']);
      inputRefs.current[0]?.focus();
    } catch (err: any) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  const formatTime = (s: number) => {
    const m = Math.floor(s / 60);
    const sec = s % 60;
    return `${m}:${sec.toString().padStart(2, '0')}`;
  };

  return (
    <div className={styles.container}>
      <div className={styles.card}>
        <button className={styles.backBtn} onClick={onBack}>← برگشت</button>
        <div className={styles.header}>
          <span className={styles.logo}>📲</span>
          <h1 className={styles.title}>تایید شماره</h1>
          <p className={styles.subtitle}>کد ارسال شده به <strong>{phone}</strong> را وارد کنید.</p>
        </div>
        <form onSubmit={handleSubmit} className={styles.form}>
          <div className={styles.codeInputs}>
            {code.map((digit, index) => (
              <input
                key={index}
                ref={(el) => { inputRefs.current[index] = el; }}
                type="text"
                inputMode="numeric"
                maxLength={1}
                className={styles.codeInput}
                value={digit}
                onChange={(e) => handleChange(index, e.target.value)}
                onKeyDown={(e) => handleKeyDown(index, e)}
                onPaste={handlePaste}
                disabled={loading}
                autoComplete="off"
              />
            ))}
          </div>
          {error && <p className={styles.error}>{error}</p>}
          <button type="submit" className={styles.button} disabled={loading || code.some(d => !d)}>
            {loading ? 'در حال بررسی...' : 'تایید و ورود'}
          </button>
          <div className={styles.resendSection}>
            {canResend ? (
              <button type="button" className={styles.resendBtn} onClick={handleResend} disabled={loading}>
                ارسال مجدد کد
              </button>
            ) : (
              <span className={styles.timer}>⏳ {formatTime(timer)}</span>
            )}
          </div>
        </form>
      </div>
    </div>
  );
}