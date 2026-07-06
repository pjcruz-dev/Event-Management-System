import {
  DM_Sans,
  Inter,
  Lato,
  Merriweather,
  Montserrat,
  Nunito,
  Open_Sans,
  Playfair_Display,
  Poppins,
  Raleway,
  Roboto,
  Source_Sans_3,
} from "next/font/google";
import type { ThemeConfigFormValues } from "@/features/events/schemas";

const inter = Inter({
  subsets: ["latin"],
  display: "swap",
  fallback: ["system-ui", "sans-serif"],
});

const roboto = Roboto({
  subsets: ["latin"],
  weight: ["400", "500", "700"],
  display: "swap",
  fallback: ["system-ui", "sans-serif"],
});

const openSans = Open_Sans({
  subsets: ["latin"],
  display: "swap",
  fallback: ["system-ui", "sans-serif"],
});

const lato = Lato({
  subsets: ["latin"],
  weight: ["400", "700"],
  display: "swap",
  fallback: ["system-ui", "sans-serif"],
});

const merriweather = Merriweather({
  subsets: ["latin"],
  weight: ["400", "700"],
  display: "swap",
  fallback: ["Georgia", "serif"],
});

const poppins = Poppins({
  subsets: ["latin"],
  weight: ["400", "500", "600", "700"],
  display: "swap",
  fallback: ["system-ui", "sans-serif"],
});

const montserrat = Montserrat({
  subsets: ["latin"],
  display: "swap",
  fallback: ["system-ui", "sans-serif"],
});

const playfairDisplay = Playfair_Display({
  subsets: ["latin"],
  display: "swap",
  fallback: ["Georgia", "serif"],
});

const sourceSans3 = Source_Sans_3({
  subsets: ["latin"],
  display: "swap",
  fallback: ["system-ui", "sans-serif"],
});

const raleway = Raleway({
  subsets: ["latin"],
  display: "swap",
  fallback: ["system-ui", "sans-serif"],
});

const nunito = Nunito({
  subsets: ["latin"],
  display: "swap",
  fallback: ["system-ui", "sans-serif"],
});

const dmSans = DM_Sans({
  subsets: ["latin"],
  display: "swap",
  fallback: ["system-ui", "sans-serif"],
});

export const EVENT_THEME_FONT_CLASS: Record<ThemeConfigFormValues["font"], string> = {
  Inter: inter.className,
  Roboto: roboto.className,
  "Open Sans": openSans.className,
  Lato: lato.className,
  Merriweather: merriweather.className,
  Poppins: poppins.className,
  Montserrat: montserrat.className,
  "Playfair Display": playfairDisplay.className,
  "Source Sans 3": sourceSans3.className,
  Raleway: raleway.className,
  Nunito: nunito.className,
  "DM Sans": dmSans.className,
};

export function getEventThemeFontClassName(font: string): string {
  if (font in EVENT_THEME_FONT_CLASS) {
    return EVENT_THEME_FONT_CLASS[font as ThemeConfigFormValues["font"]];
  }

  return EVENT_THEME_FONT_CLASS.Inter;
}
