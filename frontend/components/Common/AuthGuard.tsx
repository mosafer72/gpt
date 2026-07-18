"use client";

import { useEffect, useState } from "react";
import { useRouter, usePathname } from "next/navigation";

export default function AuthGuard({ children }: { children: React.ReactNode }) {
  const router = useRouter();
  const pathname = usePathname();

  const [isLoading, setIsLoading] = useState(true);
  const [isAuthenticated, setIsAuthenticated] = useState(false);

  useEffect(() => {
    const token = localStorage.getItem("auth_token");

    const user = JSON.parse(localStorage.getItem("user") || "{}");

    const role = user?.role;

    const publicPaths = ["/login", "/verify"];

    const adminPaths = ["/admin"];

    const isPublic = publicPaths.includes(pathname);

    const isAdminRoute = adminPaths.some((path) => pathname.startsWith(path));

    // کاربر لاگین نیست
    if (!token && !isPublic) {
      router.replace("/login");

      setIsAuthenticated(false);
    }

    // کاربر لاگین است
    else if (token) {
      // مسیر ادمین
      if (isAdminRoute) {
        if (role !== "admin") {
          // router.replace("/dashboard");

          setIsAuthenticated(false);

          return;
        }
      }

      // لاگین شده ولی صفحه login را باز کرده
      if (isPublic) {
        router.replace("/dashboard");
      }

      setIsAuthenticated(true);
    }

    setIsLoading(false);
  }, [pathname, router]);

  if (isLoading) {
    return <div>Loading...</div>;
  }

  return <>{isAuthenticated && children}</>;
}
