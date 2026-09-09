import { Link } from "react-router-dom";
import { Home, TriangleAlert } from "lucide-react";

function NotFoundPage() {
  return (
    <div className="flex min-h-screen items-center justify-center bg-slate-50 px-6">
      <div className="w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-10 text-center shadow-sm">
        <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-red-100">
          <TriangleAlert className="h-8 w-8 text-red-600" />
        </div>

        <p className="mt-6 text-6xl font-extrabold tracking-tight text-slate-900">404</p>

        <h1 className="mt-2 text-2xl font-bold text-slate-900">Halaman Tidak Ditemukan</h1>

        <p className="mt-3 text-sm leading-6 text-slate-600">
          Maaf, halaman yang Anda cari tidak tersedia, telah dipindahkan, atau URL yang dimasukkan tidak valid.
        </p>

        <Link
          to="/"
          className="mt-8 inline-flex items-center gap-2 rounded-lg bg-blue-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-800"
        >
          <Home size={18} />
          Kembali ke Monitoring
        </Link>
      </div>
    </div>
  );
}

export default NotFoundPage;
