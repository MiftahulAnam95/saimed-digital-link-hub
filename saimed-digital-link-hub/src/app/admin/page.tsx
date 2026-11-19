import AdminDashboard from "@/components/AdminDashboard";
import AdminLogin from "@/components/AdminLogin";
import { isAdminPageRequest } from "@/lib/auth";

export const dynamic = "force-dynamic";

export default async function AdminPage() {
  const isAdmin = await isAdminPageRequest();
  return isAdmin ? <AdminDashboard /> : <AdminLogin />;
}
