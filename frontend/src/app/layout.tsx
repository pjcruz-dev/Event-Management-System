import type { Metadata } from "next";
import { QueryProvider } from "@/components/providers/query-provider";
import { ThemeProvider } from "@/components/providers/theme-provider";
import { AuthHydrator } from "@/components/providers/auth-hydrator";
import "@/styles/globals.css";

export const metadata: Metadata = {
  title: "Event SaaS",
  description: "Multi-tenant event management platform",
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="en" suppressHydrationWarning>
      <body className="min-h-screen bg-background font-sans text-foreground">
        <ThemeProvider>
          <QueryProvider>
            <AuthHydrator />
            {children}
          </QueryProvider>
        </ThemeProvider>
      </body>
    </html>
  );
}
