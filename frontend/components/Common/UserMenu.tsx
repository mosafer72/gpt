'use client';

import { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import styles from './UserMenu.module.css';

interface User {
  name?: string;
  phone?: string;
  email?: string;
  avatar?: string;
}

export default function UserMenu() {
  const router = useRouter();
  const [user, setUser] = useState<User | null>(null);
  const [isOpen, setIsOpen] = useState(false);

  useEffect(() => {
    const userData = localStorage.getItem('user');
    if (userData) {
      try {
        setUser(JSON.parse(userData));
      } catch (e) {
        // ignore
      }
    }
  }, []);

  const handleLogout = async () => {
    const token = localStorage.getItem('auth_token');
    if (token) {
      try {
        await fetch(`${process.env.NEXT_PUBLIC_API_URL}/auth/logout`, {
          method: 'POST',
          headers: { 'Authorization': `Bearer ${token}` },
        });
      } catch (e) { /* ignore */ }
    }
    localStorage.removeItem('auth_token');
    localStorage.removeItem('user');
    document.cookie = 'auth_token=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT';
    router.push('/login');
  };

  const toggleMenu = () => setIsOpen(!isOpen);

  const handleDashboard = () => {
    router.push('/dashboard');
  };

  return (
    <div className={styles.container}>
      <div className={styles.userInfo} onClick={toggleMenu}>
        <span className={styles.avatar}>
          {user?.name ? user.name.charAt(0).toUpperCase() : 'U'}
        </span>
        <span className={styles.userName}>{user?.name || 'کاربر'}</span>
        <span className={styles.arrow}>{isOpen ? '▲' : '▼'}</span>
      </div>

      {isOpen && (
        <div className={styles.dropdown}>
          <div className={styles.dropdownHeader}>
            <span className={styles.dropdownAvatar}>
              {user?.name ? user.name.charAt(0).toUpperCase() : 'U'}
            </span>
            <div>
              <div className={styles.dropdownName}>{user?.name || 'کاربر مهمان'}</div>
              <div className={styles.dropdownPhone}>{user?.phone || ''}</div>
            </div>
          </div>
          <div className={styles.dropdownDivider} />
          <button className={styles.dropdownItem} onClick={handleDashboard}>
            📊 داشبورد
          </button>
          <button className={styles.dropdownItem} onClick={handleLogout}>
            🚪 خروج
          </button>
        </div>
      )}
    </div>
  );
}