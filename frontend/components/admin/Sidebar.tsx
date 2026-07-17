"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import {
  FaHome,
  FaUsers,
  FaBox,
  FaRobot,
  FaCreditCard,
  FaCommentDots,
  FaServer,
  FaBrain,
  FaCog,
  FaSignOutAlt,
} from "react-icons/fa";
import styles from "./Sidebar.module.css";

interface SidebarProps {
  isOpen: boolean;
  onToggle: () => void;
}

const menuItems = [
  { href: "/admin", icon: FaHome, label: "داشبورد" },
  { href: "/admin/users", icon: FaUsers, label: "کاربران" },
  { href: "/admin/plans", icon: FaBox, label: "پلن‌ها" },
  { href: "/admin/models", icon: FaRobot, label: "مدل‌ها" },
  { href: "/admin/transactions", icon: FaCreditCard, label: "تراکنش‌ها" },
  { href: "/admin/chats", icon: FaCommentDots, label: "چت‌ها" },
  { href: "/admin/llm-requests", icon: FaServer, label: "درخواست‌های LLM" },
  { href: "/admin/providers", icon: FaServer, label: "پروایدر ها" },
  { href: "/admin/memories", icon: FaBrain, label: "حافظه" },
  { href: "/admin/settings", icon: FaCog, label: "تنظیمات" },
];

export default function Sidebar({ isOpen, onToggle }: SidebarProps) {
  const pathname = usePathname();

  const handleLogout = () => {
    document.cookie = "admin_token=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT";
    window.location.href = "/login";
  };

  return (
    <>
      <aside className={`${styles.sidebar} ${!isOpen ? styles.closed : ""}`}>
        <div className={styles.logo}>
          <span>✨</span>
          <span>اکانتسرا</span>
        </div>

        <nav className={styles.nav}>
          {menuItems.map((item) => {
            const isActive = pathname === item.href || pathname.startsWith(item.href + "/");
            const Icon = item.icon;
            return (
              <Link
                key={item.href}
                href={item.href}
                className={`${styles.link} ${isActive ? styles.active : ""}`}
              >
                <Icon className={styles.icon} />
                <span>{item.label}</span>
              </Link>
            );
          })}
        </nav>

        <button className={styles.logout} onClick={handleLogout}>
          <FaSignOutAlt className={styles.icon} />
          <span>خروج</span>
        </button>
      </aside>

      {!isOpen && <div className={styles.overlay} onClick={onToggle} />}
    </>
  );
}