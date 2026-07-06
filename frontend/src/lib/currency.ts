const formatterCache = new Map<string, Intl.NumberFormat>();

export function formatCurrency(amount: number, currency: string = "USD"): string {
  const key = currency.toUpperCase();

  if (!formatterCache.has(key)) {
    formatterCache.set(
      key,
      new Intl.NumberFormat("en-US", {
        style: "currency",
        currency: key,
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      }),
    );
  }

  return formatterCache.get(key)!.format(amount);
}

export const SUPPORTED_CURRENCIES = [
  { code: "USD", label: "USD — US Dollar" },
  { code: "EUR", label: "EUR — Euro" },
  { code: "GBP", label: "GBP — British Pound" },
  { code: "PHP", label: "PHP — Philippine Peso" },
  { code: "JPY", label: "JPY — Japanese Yen" },
  { code: "AUD", label: "AUD — Australian Dollar" },
  { code: "CAD", label: "CAD — Canadian Dollar" },
  { code: "SGD", label: "SGD — Singapore Dollar" },
  { code: "INR", label: "INR — Indian Rupee" },
  { code: "BRL", label: "BRL — Brazilian Real" },
  { code: "MXN", label: "MXN — Mexican Peso" },
  { code: "KRW", label: "KRW — South Korean Won" },
  { code: "THB", label: "THB — Thai Baht" },
  { code: "MYR", label: "MYR — Malaysian Ringgit" },
  { code: "IDR", label: "IDR — Indonesian Rupiah" },
  { code: "CHF", label: "CHF — Swiss Franc" },
  { code: "SEK", label: "SEK — Swedish Krona" },
  { code: "NOK", label: "NOK — Norwegian Krone" },
  { code: "DKK", label: "DKK — Danish Krone" },
  { code: "NZD", label: "NZD — New Zealand Dollar" },
  { code: "HKD", label: "HKD — Hong Kong Dollar" },
  { code: "TWD", label: "TWD — Taiwan Dollar" },
  { code: "ZAR", label: "ZAR — South African Rand" },
  { code: "VND", label: "VND — Vietnamese Dong" },
] as const;
