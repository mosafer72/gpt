'use client';

 

import { useEffect, useState } from 'react';
import { useRouter, usePathname } from 'next/navigation';

export default function AuthGuard({ children }: { children: React.ReactNode }) {
  const router = useRouter();
  const pathname = usePathname();
  const [isLoading, setIsLoading] = useState(true);
  const [isAuthenticated, setIsAuthenticated] = useState(false);

  useEffect(() => {
    const token = localStorage.getItem('auth_token');
    const publicPaths = ['/login', '/verify'];
    const isPublicPath = publicPaths.includes(pathname);

    if (!token && !isPublicPath) {
      router.replace('/login');
      setIsAuthenticated(false);
    } else if (token && isPublicPath) {
      router.replace('/dashboard');
      setIsAuthenticated(true);
    } else if (token) {
      setIsAuthenticated(true);
    } else {
      setIsAuthenticated(false);
    }
    setIsLoading(false);
  }, [pathname, router]);

  if (isLoading) {
    return (
      <div className="loading-container">
        <div className="loading-spinner"></div>
        <p style={{ marginTop: 16, color: '#6C63FF', fontWeight: 500 }}>
          در حال بارگذاری...
        </p>
        <style jsx>{`
          .loading-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
            background: #F0F2FF;
          }
          .loading-spinner {
            width: 48px;
            height: 48px;
            border: 4px solid #E0E0FF;
            border-top-color: #6C63FF;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
          }
          @keyframes spin {
            to { transform: rotate(360deg); }
          }
        `}</style>
      </div>
    );
  }

  return <>{children}</>;
}