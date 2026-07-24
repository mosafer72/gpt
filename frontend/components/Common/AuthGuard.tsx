"use client";

import { useEffect, useState } from "react";
import { useRouter, usePathname } from "next/navigation";

export default function AuthGuard({
  children,
}: {
  children: React.ReactNode;
}) {
  const router = useRouter();
  const pathname = usePathname();

  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const token = localStorage.getItem("auth_token");

    const user = JSON.parse(localStorage.getItem("user") || "{}");

    const publicPaths = ["/login", "/verify","/"];

    const isPublic = publicPaths.includes(pathname);

    const isAdmin = pathname.startsWith("/admin");

    // صفحات عمومی
    if (isPublic) {
      if (token) {
        router.replace("/dashboard");
        return;
      }

      setLoading(false);
      return;
    }

    // صفحات خصوصی
    if (!token) {
      router.replace("/login");
      return;
    }

    if (isAdmin && user.role !== "admin") {
      router.replace("/dashboard");
      return;
    }

    setLoading(false);
  }, [pathname, router]);

  if (loading) {
    return <div>Loading...</div>;
  }

  return <>{children}</>;
}