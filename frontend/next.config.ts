import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  async rewrites() {
    return [
      {
        source: "/auth/discord",
        destination: `${process.env.NEXT_PUBLIC_API_URL || "http://localhost:8000"}/auth/discord`,
      },
      {
        source: "/auth/discord/callback",
        destination: `${process.env.NEXT_PUBLIC_API_URL || "http://localhost:8000"}/auth/discord/callback`,
      },
      {
        source: "/login",
        destination: `${process.env.NEXT_PUBLIC_API_URL || "http://localhost:8000"}/login`,
      },
      {
        source: "/admin",
        destination: `${process.env.NEXT_PUBLIC_API_URL || "http://localhost:8000"}/admin`,
      },
      {
        source: "/admin/:path*",
        destination: `${process.env.NEXT_PUBLIC_API_URL || "http://localhost:8000"}/admin/:path*`,
      },
    ];
  },
};

export default nextConfig;
