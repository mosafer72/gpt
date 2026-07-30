'use client';

import { useState, useEffect, Suspense } from 'react';
import { useRouter, useSearchParams } from 'next/navigation';
import VerifyForm from '@/components/Auth/VerifyForm';

function VerifyContent() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const [phone, setPhone] = useState('');

  useEffect(() => {
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

export default function VerifyPage() {
  return (
    <Suspense fallback={
      <div style={{ display: 'flex', justifyContent: 'center', alignItems: 'center', height: '100vh' }}>
        <p>در حال بارگذاری...</p>
      </div>
    }>
      <VerifyContent />
    </Suspense>
  );
}