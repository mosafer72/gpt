'use client';

import { useState, useEffect } from 'react';
import { useRouter, useSearchParams } from 'next/navigation';
import VerifyForm from '@/components/Auth/VerifyForm';

export default function VerifyPage() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const [phone, setPhone] = useState('');

  useEffect(() => {
    // دریافت شماره از sessionStorage یا پارامتر URL
    const storedPhone = sessionStorage.getItem('verify_phone');
    const urlPhone = searchParams.get('phone');
    const finalPhone = storedPhone || urlPhone || '';
    if (!finalPhone) {
      router.push('/login');
      return;
    }
    setPhone(finalPhone);
  }, [router, searchParams]);

  const handleBack = () => {
    router.push('/login');
  };

  const handleVerifySuccess = () => {
    router.push('/dashboard');
  };

  if (!phone) {
    return (
      <div style={{ display: 'flex', justifyContent: 'center', alignItems: 'center', height: '100vh' }}>
        <p>در حال هدایت...</p>
      </div>
    );
  }

  return (
    <VerifyForm
      phone={phone}
      onBack={handleBack}
      onSuccess={handleVerifySuccess}
    />
  );
}