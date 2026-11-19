import PublicHub from "@/components/PublicHub";
import { getPublicLinks } from "@/lib/data";

export const dynamic = "force-dynamic";

export default async function HomePage() {
  const links = await getPublicLinks();
  return <PublicHub links={links} />;
}
