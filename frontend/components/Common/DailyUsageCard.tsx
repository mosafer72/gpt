// "use client";

// import { useEffect, useState } from "react";

// import { getTodayUsage } from "@/lib/api";
// import type { TodayUsage } from "@/types";

// import styles from "./DailyUsageCard.module.css";

// export default function DailyUsageCard() {
//   const [usage, setUsage] = useState<TodayUsage | null>(null);

//   useEffect(() => {
//     async function load() {
//       try {
//         const data = await getTodayUsage();

//         setUsage(data);
//       } catch {}
//     }

//     load();
//   }, []);

//   if (!usage) return null;

//   const percent =
//     usage.daily_limit > 0
//       ? Math.min(
//           100,

//           Math.round((usage.messages_count / usage.daily_limit) * 100),
//         )
//       : 0;

//   return (
//     <div className={styles.card}>
//       <div className={styles.header}>
//         <div>
//           <h3>📊 مصرف امروز</h3>

//           <p>پیام‌های استفاده شده امروز</p>
//         </div>

//         <strong>
//           {usage.messages_count}/{usage.daily_limit}
//         </strong>
//       </div>

//       <div className={styles.progress}>
//         <div
//           className={styles.bar}
//           style={{
//             width: `${percent}%`,
//           }}
//         />
//       </div>

//       <div className={styles.info}>
//         <span>
//           باقی مانده:
//           {usage.remaining_messages}
//           پیام
//         </span>

//         <span>
//           توکن مصرفی:
//           {usage.tokens_used?.toLocaleString()}
//         </span>
//       </div>
//     </div>
//   );
// }
