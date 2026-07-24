 
import type { Metadata } from 'next';
import localFont from 'next/font/local';
import './globals.css';
import AuthGuard from '@/components/Common/AuthGuard';
import 'katex/dist/katex.min.css';

const iranSans = localFont({
  src: [
    {
      path: '../public/fonts/Vazirmatn-Regular.woff2',
      weight: '400',
      style: 'normal',
    },
    {
      path: '../public/fonts/Vazirmatn-Bold.woff2',
      weight: '700',
      style: 'normal',
    },
    {
      path: '../public/fonts/Vazirmatn-Medium.woff2',
      weight: '500',
      style: 'normal',
    },
  ],
  variable: '--font-iran-sans',
  display: 'swap',
});

export const metadata: Metadata = {
  title: 'اکانتسرا‌ی هوش مصنوعی | ChatGPT Clone',
  description: 'دستیار هوشمند برای دانشجویان، محققان و علاقه‌مندان به AI',
  keywords: 'هوش مصنوعی, ChatGPT, دانشجویان, یادگیری ماشین',
  authors: [{ name: 'تیم توسعه' }],
  viewport: 'width=device-width, initial-scale=1, maximum-scale=1',
};

export default function RootLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <html lang="fa" dir="rtl" className={iranSans.variable}>
      <body style={{ fontFamily: 'var(--font-iran-sans)' }}>
        {/* <AuthGuard>{children}</AuthGuard> */}
        <AuthGuard>{children}</AuthGuard>
      </body>
    </html>
  );
}