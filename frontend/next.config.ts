import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  async rewrites() {
    const apiBaseURL = process.env.NEXT_PUBLIC_API_URL || "http://127.0.0.1:8000";
    return [
      {
        source: "/api/:path*",
        destination: `${apiBaseURL}/api/:path*`,
      },
      {
        source: "/sanctum/:path*",
        destination: `${apiBaseURL}/sanctum/:path*`,
      },
      {
        source: "/auth/:path*",
        destination: `${apiBaseURL}/api/auth/:path*`,
      },
      {
        source: "/login",
        destination: `${apiBaseURL}/api/login`,
      },
      {
        source: "/admin/:path*",
        destination: `${apiBaseURL}/api/admin/:path*`,
      },
      {
        source: "/icon/:path*",
        destination: `${apiBaseURL}/api/icon/:path*`,
      },
      {
        source: "/storage/:path*",
        destination: `${apiBaseURL}/api/storage/:path*`,
      },
    ];
  },
};

export default nextConfig;
