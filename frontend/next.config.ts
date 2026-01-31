import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  async rewrites() {
    return [
      {
        source: "/auth/discord",
        destination: `${process.env.NEXT_PUBLIC_API_URL || "http://api.thezotopia.online"}/auth/discord`,
      },
      {
        source: "/auth/discord/callback",
        destination: `${process.env.NEXT_PUBLIC_API_URL || "http://api.thezotopia.online"}/auth/discord/callback`,
      },
      {
        source: "/login",
        destination: `${process.env.NEXT_PUBLIC_API_URL || "http://api.thezotopia.online"}/login`,
      },
      {
        source: "/admin",
        destination: `${process.env.NEXT_PUBLIC_API_URL || "http://api.thezotopia.online"}/admin`,
      },
      {
        source: "/admin/:path*",
        destination: `${process.env.NEXT_PUBLIC_API_URL || "http://api.thezotopia.online"}/admin/:path*`,
      },
      {
        source: "/icon/:path*",
        destination: `${process.env.NEXT_PUBLIC_API_URL || "http://api.thezotopia.online"}/icon/:path*`,
      },
    ];
  },
};

export default nextConfig;
