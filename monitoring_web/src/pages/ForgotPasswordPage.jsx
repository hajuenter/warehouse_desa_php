import { useState } from "react";
import { Link } from "react-router-dom";
import { forgotPassword } from "../services/authService";

function ForgotPasswordPage() {
  const [sent, setSent] = useState(false);
  const [error, setError] = useState(null);
  const [loading, setLoading] = useState(false);

  async function handleSubmit(e) {
    e.preventDefault();
    setError(null);
    setLoading(true);

    try {
      await forgotPassword();
      setSent(true);
    } catch (err) {
      setError(err.response?.data?.message || "Gagal mengirim email reset");
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="flex min-h-screen items-center justify-center bg-slate-100 px-4">
      <div className="w-full max-w-sm rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h1 className="mb-1 text-lg font-semibold text-slate-900">Lupa Password</h1>
        <p className="mb-6 text-sm text-slate-500">
          Tautan reset akan dikirim ke email admin yang terdaftar di server.
        </p>

        {sent ? (
          <p className="rounded-md bg-emerald-50 p-3 text-sm text-emerald-700">
            Email reset sudah dikirim. Silakan cek kotak masuk.
          </p>
        ) : (
          <form onSubmit={handleSubmit} className="space-y-4">
            {error && <p className="text-sm text-rose-600">{error}</p>}

            <button
              type="submit"
              disabled={loading}
              className="w-full cursor-pointer rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700 disabled:opacity-50"
            >
              {loading ? "Mengirim..." : "Kirim Tautan Reset"}
            </button>
          </form>
        )}

        <Link to="/login" className="mt-4 cursor-pointer block text-center text-xs text-slate-500 hover:text-slate-700">
          Kembali ke halaman masuk
        </Link>
      </div>
    </div>
  );
}

export default ForgotPasswordPage;