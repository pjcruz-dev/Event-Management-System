import { create } from "zustand";
import { persist } from "zustand/middleware";
import { apiClient } from "@/lib/api-client";
import type {
  ExhibitorPortalLoginResponse,
  ExhibitorPortalMeResponse,
} from "@/types/conference";

interface ExhibitorContactSummary {
  id: number;
  name: string;
  email: string;
  exhibitor_id?: number;
  event_id?: number;
}

interface ExhibitorAuthState {
  token: string | null;
  contact: ExhibitorContactSummary | null;
  exhibitor: ExhibitorPortalMeResponse["exhibitor"] | null;
  isHydrated: boolean;
  isLoading: boolean;
  setHydrated: (value: boolean) => void;
  setAuth: (payload: ExhibitorPortalLoginResponse) => void;
  setMe: (payload: ExhibitorPortalMeResponse) => void;
  fetchMe: () => Promise<void>;
  logout: () => Promise<void>;
  clear: () => void;
}

function exhibitorOptions(token: string | null) {
  return token ? { token } : {};
}

export const useExhibitorAuthStore = create<ExhibitorAuthState>()(
  persist(
    (set, get) => ({
      token: null,
      contact: null,
      exhibitor: null,
      isHydrated: false,
      isLoading: false,

      setHydrated: (value) => set({ isHydrated: value }),

      setAuth: ({ token, contact }) => set({ token, contact }),

      setMe: ({ contact, exhibitor }) => set({ contact, exhibitor }),

      fetchMe: async () => {
        const token = get().token;
        if (!token) {
          set({ isHydrated: true });
          return;
        }

        set({ isLoading: true });
        try {
          const me = await apiClient.get<ExhibitorPortalMeResponse>(
            "/exhibitor-portal/me",
            exhibitorOptions(token),
          );
          get().setMe(me);
        } catch {
          get().clear();
        } finally {
          set({ isLoading: false, isHydrated: true });
        }
      },

      logout: async () => {
        const token = get().token;
        if (token) {
          try {
            await apiClient.post(
              "/exhibitor-portal/auth/logout",
              undefined,
              exhibitorOptions(token),
            );
          } catch {
            // Token may already be invalid.
          }
        }
        get().clear();
      },

      clear: () =>
        set({
          token: null,
          contact: null,
          exhibitor: null,
          isLoading: false,
        }),
    }),
    {
      name: "event-saas-exhibitor-auth",
      partialize: (state) => ({ token: state.token }),
      onRehydrateStorage: () => (state) => {
        state?.setHydrated(true);
      },
    },
  ),
);

export function exhibitorApiOptions(token: string | null) {
  return exhibitorOptions(token);
}
