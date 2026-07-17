"use client";

import { useState, useEffect } from "react";
import { FaBars, FaUser, FaBell } from "react-icons/fa";
import styles from "./Header.module.css";

interface HeaderProps {
  onMenuClick: () => void;
}

export default function Header({ onMenuClick }: HeaderProps) {
  const [adminName, setAdminName] = useState("مدیر");

  useEffect(() => {
    const name = localStorage.getItem("admin_name");
    if (name) setAdminName(name);
  }, []);

  return (
    <header className={styles.header}>
      <div className={styles.left}>
        <button className={styles.menuBtn} onClick={onMenuClick}>
          <FaBars />
        </button>
        <span className={styles.pageTitle}>پنل مدیریت</span>
      </div>

      <div className={styles.right}>
        <button className={styles.iconBtn}>
          <FaBell />
          <span className={styles.badge}>3</span>
        </button>
        <div className={styles.user}>
          <FaUser className={styles.userIcon} />
          <span className={styles.userName}>{adminName}</span>
        </div>
      </div>
    </header>
  );
}