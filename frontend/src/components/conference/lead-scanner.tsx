"use client";

import { Html5QrcodeScanner } from "html5-qrcode";
import { useEffect, useRef } from "react";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";

interface LeadScannerProps {
  disabled?: boolean;
  onScan: (token: string) => void;
}

export function LeadScanner({ disabled = false, onScan }: LeadScannerProps) {
  const scannerRef = useRef<Html5QrcodeScanner | null>(null);
  const scanPendingRef = useRef(false);

  useEffect(() => {
    const scanner = new Html5QrcodeScanner(
      "lead-qr-reader",
      { fps: 8, qrbox: { width: 240, height: 240 } },
      false,
    );
    scannerRef.current = scanner;

    scanner.render(
      (decoded) => {
        if (scanPendingRef.current || disabled) return;
        scanPendingRef.current = true;
        onScan(decoded);
        window.setTimeout(() => {
          scanPendingRef.current = false;
        }, 1500);
      },
      () => {},
    );

    return () => {
      void scanner.clear().catch(() => {});
      scannerRef.current = null;
    };
  }, [disabled, onScan]);

  return (
    <div className="space-y-4">
      <div id="lead-qr-reader" className="overflow-hidden rounded-md border border-border" />
      <form
        className="flex gap-2"
        onSubmit={(event) => {
          event.preventDefault();
          const form = event.currentTarget;
          const input = form.elements.namedItem("manual-token") as HTMLInputElement;
          if (input.value.trim()) {
            onScan(input.value.trim());
            input.value = "";
          }
        }}
      >
        <Input
          name="manual-token"
          placeholder="Paste QR token manually"
          disabled={disabled}
          aria-label="Manual QR token"
        />
        <Button type="submit" variant="secondary" disabled={disabled}>
          Submit
        </Button>
      </form>
    </div>
  );
}
