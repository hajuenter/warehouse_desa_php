import { useEffect, useState } from "react";
import { useNavigate, Link } from "react-router-dom";
import { login } from "../services/authService";
import { Eye, EyeOff } from "lucide-react";

function LoginPage() {
  const [username, setUsername] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState(null);
  const [loading, setLoading] = useState(false);
  const [showPassword, setShowPassword] = useState(false);
  const [lockedUntil, setLockedUntil] = useState(null);
  const [remaining, setRemaining] = useState(0);
  const navigate = useNavigate();

  async function handleSubmit(e) {
    e.preventDefault();
    setError(null);
    setLoading(true);

    try {
      const token = await login(username, password);
      localStorage.setItem("auth_token", token);
      navigate("/", {
        state: {
          loginSuccess: true,
        },
      });
    } catch (err) {
      const data = err.response?.data;

      setError(data?.message || "Gagal login");

      if (data?.lockedUntil) {
        localStorage.setItem(
          "loginLockedUntil",
          data.lockedUntil
        );

        setLockedUntil(data.lockedUntil);
      }
    } finally {
      setLoading(false);
    }
  }

  function formatRemaining(seconds) {
    const minutes = Math.floor(seconds / 60);
    const secs = seconds % 60;

    if (minutes > 0) {
      return `${minutes} menit ${secs} detik`;
    }

    return `${secs} detik`;
  }

  useEffect(() => {
    const saved = localStorage.getItem("loginLockedUntil");

    if (!saved) return;

    const timestamp = Number(saved);

    if (timestamp <= Math.floor(Date.now() / 1000)) {
      localStorage.removeItem("loginLockedUntil");
      return;
    }

    setLockedUntil(timestamp);
    setError("Terlalu banyak percobaan login.");
  }, []);

  useEffect(() => {
    if (!lockedUntil) return;

    const updateRemaining = () => {
      const now = Math.floor(Date.now() / 1000);

      const diff = lockedUntil - now;

      if (diff <= 0) {
        setLockedUntil(null);
        setRemaining(0);
        setError(null);

        localStorage.removeItem("loginLockedUntil");

        return;
      }

      setRemaining(diff);
    };

    updateRemaining();

    const interval = setInterval(updateRemaining, 1000);

    return () => clearInterval(interval);
  }, [lockedUntil]);

  return (
    <div className="flex min-h-screen items-center justify-center bg-slate-100 px-4">
      <div className="w-full max-w-sm rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h1 className="mb-1 text-lg font-semibold text-slate-900">Masuk Dashboard</h1>
        <p className="mb-3 lg:mb-2 text-sm text-slate-500">Monitoring Sinkronisasi Data Desa</p>

        {error && (
          <div className="mb-2 lg:mb-1 rounded-md border border-rose-200 bg-rose-50 p-3">
            <p className="text-sm font-medium text-rose-700">
              {error}
            </p>

            {remaining > 0 && (
              <p className="mt-1 text-xs text-rose-600">
                Coba lagi dalam{" "}
                <span className="font-semibold">
                  {formatRemaining(remaining)}
                </span>
              </p>
            )}
          </div>
        )}

        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <label htmlFor="username" className="mb-1 block text-xs font-medium text-slate-500">
              Username
            </label>

            <input
              id="username"
              type="text"
              value={username}
              onChange={(e) => setUsername(e.target.value)}
              className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500"
              required
            />
          </div>

          <div>
            <label htmlFor="password" className="mb-1 block text-xs font-medium text-slate-500">
              Password
            </label>

            <div className="relative">
              <input id="password" type={showPassword ? "text" : "password"}
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                className="w-full rounded-md border border-slate-300 py-2 pr-10 pl-3 text-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500"
                required />

              <button
                type="button"
                onClick={() => setShowPassword((prev) => !prev)}
                className="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 transition-colors hover:text-slate-600" aria-label={showPassword ? "Sembunyikan password" : "Tampilkan password"}
              >
                {showPassword ? <EyeOff size={18} /> : <Eye size={18} />}
              </button>
            </div>
          </div>

          <button
            type="submit"
            disabled={loading || remaining > 0}
            className="w-full cursor-pointer rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700 disabled:cursor-not-allowed disabled:opacity-50"
          >
            {loading
              ? "Memproses..."
              : remaining > 0
                ? `Coba lagi (${formatRemaining(remaining)})`
                : "Masuk"}
          </button>
        </form>

        <Link to="/forgot-password" className="mt-4 block text-center text-xs text-slate-500 hover:text-slate-700">
          Lupa password?
        </Link>
      </div>
    </div>
  );
}

export default LoginPage;