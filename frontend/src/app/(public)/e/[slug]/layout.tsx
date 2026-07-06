import { PublicEventNav } from "@/components/public/public-event-nav";
import { fetchPublicApi } from "@/lib/server-api";
import type { PublicEventPayload } from "@/types/ticketing";

interface EventPublicLayoutProps {
  children: React.ReactNode;
  params: Promise<{ slug: string }>;
}

export default async function EventPublicLayout({ children, params }: EventPublicLayoutProps) {
  const { slug } = await params;
  const payload = await fetchPublicApi<PublicEventPayload>(`/public/events/${slug}`);

  return (
    <>
      {payload?.event ? (
        <PublicEventNav
          eventName={payload.event.name}
          slug={slug}
          logoUrl={payload.event.theme_config?.logo_url ?? null}
          registrationMode={payload.event.registration_mode}
        />
      ) : null}
      {children}
    </>
  );
}
