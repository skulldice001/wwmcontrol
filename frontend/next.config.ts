import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  async rewrites() {
    return [
      {
        source: "/auth/discord",
        destination: "http://api.thezotopia.online/auth/discord",
      },
      {
        source: "/auth/discord/callback",
        destination: "http://api.thezotopia.online/auth/discord/callback",
      },
      {
        source: "/sanctum/csrf-cookie",
        destination: "http://api.thezotopia.online/sanctum/csrf-cookie",
      },
      {
        source: "/api/:path*",
        destination: "http://api.thezotopia.online/api/:path*",
      },
      {
        source: "/login",
        destination: "http://api.thezotopia.online/login",
      },
      {
        source: "/admin",
        destination: "http://api.thezotopia.online/admin",
      },
      {
        source: "/admin/:path*",
        destination: "http://api.thezotopia.online/admin/:path*",
      },
      {
        source: "/icon/:path*",
        destination: "http://api.thezotopia.online/icon/:path*",
      },
    ];
  },
};

export default nextConfig;
