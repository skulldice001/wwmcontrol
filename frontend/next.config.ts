import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  async rewrites() {
    return [
      {
        source: "/auth/discord",
        destination: `${process.env.NEXT_PUBLIC_API_URL || "http://61.14.234.57:8000"}/auth/discord`,
      },
      {
        source: "/auth/discord/callback",
        destination: `${process.env.NEXT_PUBLIC_API_URL || "http://61.14.234.57:8000"}/auth/discord/callback`,
      },
      {
        source: "/login",
        destination: `${process.env.NEXT_PUBLIC_API_URL || "http://61.14.234.57:8000"}/login`,
      },
      {
        source: "/admin",
        destination: `${process.env.NEXT_PUBLIC_API_URL || "http://61.14.234.57:8000"}/admin`,
      },
      {
        source: "/admin/:path*",
        destination: `${process.env.NEXT_PUBLIC_API_URL || "http://61.14.234.57:8000"}/admin/:path*`,
      },
      {
        source: "/icon/:path*",
        destination: `${process.env.NEXT_PUBLIC_API_URL || "http://61.14.234.57:8000"}/icon/:path*`,
      },
    ];
  },
};

export default nextConfig;
