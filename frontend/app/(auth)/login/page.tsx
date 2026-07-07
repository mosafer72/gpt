 
'use client';

import { useState } from 'react';
import { useRouter } from 'next/navigation';
import LoginForm from '@/components/Auth/LoginForm';
import VerifyForm from '@/components/Auth/VerifyForm';

export default function LoginPage() {
  const router = useRouter();
  const [step, setStep] = useState<'login' | 'verify'>('login');
  const [phone, setPhone] = useState('');

  const handleLoginSuccess = (phoneNumber: string) => {
    setPhone(phoneNumber);
    setStep('verify');
  };

  const handleVerifySuccess = () => {
    router.push('/dashboard');
  };

  const handleBack = () => {
    setStep('login');
  };

  return (
    <div>
      {step === 'login' ? (
        <LoginForm onSuccess={handleLoginSuccess} />
      ) : (
        <VerifyForm
          phone={phone}
          onBack={handleBack}
          onSuccess={handleVerifySuccess}
        />
      )}
    </div>
  );
}