import type { OfflineScanRecord } from "@/types/checkin";

const DB_NAME = "event-saas-checkin";
const STORE_NAME = "pending-scans";
const DB_VERSION = 1;

function openDb(): Promise<IDBDatabase> {
  return new Promise((resolve, reject) => {
    const request = indexedDB.open(DB_NAME, DB_VERSION);

    request.onerror = () => reject(request.error);
    request.onsuccess = () => resolve(request.result);
    request.onupgradeneeded = () => {
      const db = request.result;
      if (!db.objectStoreNames.contains(STORE_NAME)) {
        db.createObjectStore(STORE_NAME, { keyPath: "idempotency_key" });
      }
    };
  });
}

export async function queueOfflineScan(
  eventId: number,
  record: OfflineScanRecord,
): Promise<void> {
  const db = await openDb();
  const tx = db.transaction(STORE_NAME, "readwrite");
  tx.objectStore(STORE_NAME).put({ ...record, event_id: eventId });
  await new Promise<void>((resolve, reject) => {
    tx.oncomplete = () => resolve();
    tx.onerror = () => reject(tx.error);
  });
  db.close();
}

export async function listOfflineScans(eventId: number): Promise<OfflineScanRecord[]> {
  const db = await openDb();
  const tx = db.transaction(STORE_NAME, "readonly");
  const request = tx.objectStore(STORE_NAME).getAll();

  const rows = await new Promise<Array<OfflineScanRecord & { event_id: number }>>((resolve, reject) => {
    request.onsuccess = () => resolve(request.result as Array<OfflineScanRecord & { event_id: number }>);
    request.onerror = () => reject(request.error);
  });

  db.close();

  return rows
    .filter((row) => row.event_id === eventId)
    .map(({ idempotency_key, token, scanned_at, device_id, gate }) => ({
      idempotency_key,
      token,
      scanned_at,
      device_id,
      gate,
    }));
}

export async function removeOfflineScans(keys: string[]): Promise<void> {
  if (keys.length === 0) return;

  const db = await openDb();
  const tx = db.transaction(STORE_NAME, "readwrite");
  const store = tx.objectStore(STORE_NAME);
  keys.forEach((key) => store.delete(key));
  await new Promise<void>((resolve, reject) => {
    tx.oncomplete = () => resolve();
    tx.onerror = () => reject(tx.error);
  });
  db.close();
}
