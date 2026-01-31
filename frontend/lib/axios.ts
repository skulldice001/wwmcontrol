import axios from "axios";

const apiBaseURL = process.env.NEXT_PUBLIC_API_URL || "http://61.14.234.57:8000";

const api = axios.create({
  baseURL: `${apiBaseURL}/api`,
  withCredentials: true,
  headers: {
    "Content-Type": "application/json",
    Accept: "application/json",
  },
});

const isDebug = process.env.NEXT_PUBLIC_DEBUG === 'true';

// Request interceptor to add CSRF token
api.interceptors.request.use(
  async (config) => {
    if (isDebug) {
      console.log(`[API Request] ${config.method?.toUpperCase()} ${config.url}`, config.data || '');
    }

    // Get CSRF cookie before making requests to initialize session
    if (["post", "put", "patch", "delete"].includes(config.method || "")) {
      // For Sanctum, we MUST hit this at least once to get the XSRF-TOKEN
      // and ensure the session is initialized.
      if (!document.cookie.includes('XSRF-TOKEN')) {
        if (isDebug) console.log('[API] Initializing CSRF cookie...');
        try {
          await axios.get(
            `${apiBaseURL}/sanctum/csrf-cookie`,
            {
              withCredentials: true,
            }
          );
        } catch (csrfError) {
          if (isDebug) console.error('[API] Failed to initialize CSRF cookie', csrfError);
        }
      }
    }

    // Manually add X-XSRF-TOKEN header if cookie exists
    const xsrfCookie = document.cookie
      .split('; ')
      .find(row => row.startsWith('XSRF-TOKEN='))
      ?.split('=')[1];

    if (xsrfCookie) {
      config.headers['X-XSRF-TOKEN'] = decodeURIComponent(xsrfCookie);
    }

    return config;
  },
  (error) => {
    if (isDebug) {
      console.error('[API Request Error]', error);
    }
    return Promise.reject(error);
  }
);

// Response interceptor for error handling
api.interceptors.response.use(
  (response) => {
    if (isDebug) {
      console.log(`[API Response] ${response.status} ${response.config.url}`, response.data);
    }
    return response;
  },
  (error) => {
    if (isDebug) {
      console.error(`[API Response Error] ${error.response?.status} ${error.config?.url}`, error.response?.data || error.message);
    }
    if (error.response?.status === 401) {
      // Redirect to login if unauthorized
      if (typeof window !== "undefined") {
        window.location.href = "/";
      }
    }
    return Promise.reject(error);
  }
);

export default api;
