import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  async rewrites() {
    const apiBaseURL = process.env.NEXT_PUBLIC_API_URL || "http://localhost:8000";
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
        destination: `${apiBaseURL}/auth/:path*`,
      },
      {
        source: "/login",
        destination: `${apiBaseURL}/login`,
      },
      {
        source: "/admin/:path*",
        destination: `${apiBaseURL}/admin/:path*`,
      },
      {
        source: "/icon/:path*",
        destination: `${apiBaseURL}/icon/:path*`,
      },
      {
        source: "/storage/:path*",
        destination: `${apiBaseURL}/storage/:path*`,
      },
    ];
  },
};

export default nextConfig;
