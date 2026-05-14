import { useState } from 'preact/hooks';
import { hydrate, prerender as ssr } from 'preact-iso';
import './style.less';

/**
 * @param {string} url
 * @param {unknown} payload
 */
async function postJson(url, payload) {
  const resp = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
  });
  const data = await resp.json().catch(() => ({}));
  if (!resp.ok) throw new Error(data.error || data.message || `HTTP ${resp.status}`);
  return data;
}

export function Home() {
  const [mode, setMode] = useState('register');
  const [msg, setMsg] = useState('Ready');
  const [loading, setLoading] = useState(false);
  const [user, setUser] = useState(/** @type {any} */ (null));

  const [regUsername, setRegUsername] = useState('');
  const [regEmail, setRegEmail] = useState('');

  const [loginEmail, setLoginEmail] = useState('');
  const [loginUsername, setLoginUsername] = useState('');
  const [loginPassword, setLoginPassword] = useState('');

  const [forgotEmail, setForgotEmail] = useState('');
  const [forgotUsername, setForgotUsername] = useState('');

  /** @param {Event} e */
  const submitRegister = async (e) => {
    e.preventDefault();
    setLoading(true);
    setMsg('Submitting registration...');
    try {
      const data = await postJson('/api/register', { username: regUsername, email: regEmail });
      setMsg(data.message || '注册成功');
    } catch (err) {
      setMsg(err instanceof Error ? err.message : '注册失败');
    } finally {
      setLoading(false);
    }
  };

  /** @param {Event} e */
  const submitLogin = async (e) => {
    e.preventDefault();
    setLoading(true);
    setMsg('Signing in...');
    try {
      const data = await postJson('/api/login', { email: loginEmail, username: loginUsername, password: loginPassword });
      setUser(data.user);
      setMsg('登录成功');
    } catch (err) {
      setMsg(err instanceof Error ? err.message : '登录失败');
    } finally {
      setLoading(false);
    }
  };

  const handleLogout = async () => {
    try {
      await postJson('/api/logout', {});
    } catch {}
    setUser(null);
    setLoginEmail('');
    setLoginUsername('');
    setLoginPassword('');
    setMsg('已退出登录');
  };

  const handleChangePassword = () => {
    setForgotEmail(user?.email || loginEmail);
    setForgotUsername(user?.username || loginUsername);
    setMode('forgot');
  };

  /** @param {Event} e */
  const submitForgot = async (e) => {
    e.preventDefault();
    setLoading(true);
    setMsg('Sending reset request...');
    try {
      const data = await postJson('/api/password/forgot', { email: forgotEmail, username: forgotUsername });
      setMsg(data.message || '如果账号存在，将发送重置邮件');
    } catch (err) {
      setMsg(err instanceof Error ? err.message : '请求失败');
    } finally {
      setLoading(false);
    }
  };

  /** @param {string | null | undefined} dateStr */
  const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    return d.toLocaleString();
  };

  if (user) {
    return (
      <div class="home">
        <h1>AIMonkey</h1>
        <div class="profile-card">
          <div class="avatar-badge">
            {user.username.charAt(0).toUpperCase()}
          </div>
          <div class="profile-field">
            <span class="field-label">Username</span>
            <span class="field-value">{user.username}</span>
          </div>
          <div class="profile-field">
            <span class="field-label">Email</span>
            <span class="field-value">{user.email}</span>
          </div>
          <div class="profile-field">
            <span class="field-label">Avatar State</span>
            <span class="field-value">{user.avatar_state}</span>
          </div>
          <div class="profile-field">
            <span class="field-label">Created At</span>
            <span class="field-value">{formatDate(user.created_at)}</span>
          </div>
          <div class="profile-field">
            <span class="field-label">Last Login</span>
            <span class="field-value">{formatDate(user.last_login_at)}</span>
          </div>
          <div class="profile-actions">
            <button class="action-btn secondary" onClick={handleChangePassword}>Change Password</button>
            <button class="action-btn danger" onClick={handleLogout}>Logout</button>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div class="home">
      <h1>AIMonkey</h1>

      {mode === 'forgot' && (
        <section style="display:flex;gap:8px;justify-content:center;margin-bottom:24px;">
          <button onClick={() => setMode('register')}>Register</button>
          <button onClick={() => setMode('login')}>Login</button>
          <button disabled>Forgot</button>
        </section>
      )}

      {mode !== 'forgot' && (
        <section style="display:flex;gap:8px;justify-content:center;margin-bottom:24px;">
          <button disabled={mode === 'register'} onClick={() => setMode('register')}>Register</button>
          <button disabled={mode === 'login'} onClick={() => setMode('login')}>Login</button>
          <button onClick={() => setMode('forgot')}>Forgot</button>
        </section>
      )}

      {mode === 'register' && (
        <form onSubmit={submitRegister}>
          <input value={regUsername} onInput={(e) => setRegUsername(e.currentTarget.value)} placeholder="username" />
          <input value={regEmail} onInput={(e) => setRegEmail(e.currentTarget.value)} placeholder="email" />
          <button disabled={loading}>Submit Register</button>
        </form>
      )}

      {mode === 'login' && (
        <form onSubmit={submitLogin}>
          <input value={loginEmail} onInput={(e) => setLoginEmail(e.currentTarget.value)} placeholder="email" />
          <input value={loginUsername} onInput={(e) => setLoginUsername(e.currentTarget.value)} placeholder="username (optional)" />
          <input type="password" value={loginPassword} onInput={(e) => setLoginPassword(e.currentTarget.value)} placeholder="password" />
          <div class="form-actions">
            <button type="button" class="forgot-btn" onClick={() => setMode('forgot')}>Forgot</button>
            <button disabled={loading}>Submit Login</button>
          </div>
        </form>
      )}

      {mode === 'forgot' && (
        <form onSubmit={submitForgot}>
          <input value={forgotEmail} onInput={(e) => setForgotEmail(e.currentTarget.value)} placeholder="email" />
          <input value={forgotUsername} onInput={(e) => setForgotUsername(e.currentTarget.value)} placeholder="username (optional)" />
          <button disabled={loading}>Send Reset</button>
        </form>
      )}

      <p>{msg}</p>
    </div>
  );
}

export function App() {
  return <Home />;
}

if (typeof window !== 'undefined') {
  hydrate(<App />, document.getElementById('app') ?? undefined);
}

export async function prerender() {
  return await ssr(<App />);
}
