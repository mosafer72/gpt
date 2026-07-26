/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    // تلویند فقط کلاس‌های داخل پوشه ادمین را شناسایی و تولید می‌کند
    "./app/admin/**/*.{js,ts,jsx,tsx,mdx}",
    "./components/admin/**/*.{js,ts,jsx,tsx,mdx}", // اگر کامپوننت اختصاصی ادمین دارید
  ],
  theme: {
    extend: {},
  },
  plugins: [],
}
