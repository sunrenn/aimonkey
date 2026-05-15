import { useState } from 'preact/hooks';
import { hydrate, prerender as ssr } from 'preact-iso';
import './style.css';

/**
 * @typedef {Object} AuthUser
 * @property {number|string} id
 * @property {string} username
 * @property {string} email
 * @property {string} avatar_state
 * @property {string} created_at
 * @property {string} last_login_at
 */

/**
 * @typedef {Error & { status?: number, data?: any }} ApiError
 */

const I18N = {
  'zh-CN': {
    'common.ready': '就绪',
    'register.submitting': '正在提交注册...',
    'register.success': '注册成功，密码已发送到您的邮箱！',
    'register.failed': '注册失败',
    'register.usernameRequired': '用户名不能为空。',
    'register.emailRequired': '邮箱不能为空。',
    'register.emailInvalid': '邮箱格式不正确。',
    'register.duplicateUsernameForEmail': '该邮箱已注册过这个用户名，请使用其他用户名。',
    'register.emailLimitReached': '该邮箱最多只能注册 {limit} 个账户，已达到上限。',
    'register.registeredUsers': '已注册用户名: {usernames}',
    'register.smtpFailed': '邮件发送失败，请检查 SMTP 配置后重试。',
    'register.serverError': '注册失败，服务器错误。',
    'login.signingIn': '正在登录...',
    'login.success': '登录成功',
    'login.failed': '登录失败',
    'login.identifierOrPasswordRequired': '用户名/邮箱和密码不能为空。',
    'login.invalidOrAmbiguous': '凭据无效，或匹配到多个账号。',
    'login.serverError': '服务器错误，请稍后重试。',
    'forgot.submitting': '正在发送重置请求...',
    'forgot.success': '如果账号存在，将发送重置邮件。',
    'forgot.failed': '请求失败',
    'forgot.genericSuccess': '如果账号存在，将发送重置邮件。',
    'password.secretMissing': '服务器缺少重置密钥配置。',
    'reset.tokenRequired': '重置令牌不能为空。',
    'reset.passwordTooShort': '新密码长度至少为 {minLength} 位。',
    'reset.passwordWeak': '新密码必须同时包含字母和数字。',
    'reset.tokenInvalidOrExpired': '令牌无效或已过期。',
    'reset.success': '密码重置成功，请使用新密码登录。',
    'reset.serverError': '密码重置失败，服务器错误。',
    'gateway.databaseConnectFailed': '数据库连接失败，请运行 reset 并检查 MySQL 配置。',
    'gateway.invalidJsonBody': '请求体 JSON 格式无效。',
    'admin.mailConfigUpdated': '邮件配置已更新。',
    'auth.headerMissingOrInvalid': '缺少或无效的 Authorization 请求头。',
    'auth.tokenInvalidOrExpired': '登录令牌无效或已过期。',
    'gateway.routeNotFound': '接口不存在。',
    'auth.loggedOut': '已退出登录',
  },
  en: {
    'common.ready': 'Ready',
    'register.submitting': 'Submitting registration...',
    'register.success': 'Registration successful. Password has been sent to your email.',
    'register.failed': 'Registration failed.',
    'register.usernameRequired': 'Username is required.',
    'register.emailRequired': 'Email is required.',
    'register.emailInvalid': 'Email format is invalid.',
    'register.duplicateUsernameForEmail': 'This email already has that username. Please choose another username.',
    'register.emailLimitReached': 'This email has reached the registration limit ({limit}).',
    'register.registeredUsers': 'Registered usernames: {usernames}',
    'register.smtpFailed': 'SMTP delivery failed. Please check mail settings and try again.',
    'register.serverError': 'Registration failed due to server error.',
    'login.signingIn': 'Signing in...',
    'login.success': 'Login successful.',
    'login.failed': 'Login failed.',
    'login.identifierOrPasswordRequired': 'Username/email and password are required.',
    'login.invalidOrAmbiguous': 'Invalid credentials or ambiguous login.',
    'login.serverError': 'Server error. Please try again later.',
    'forgot.submitting': 'Sending reset request...',
    'forgot.success': 'If the account exists, reset instructions were sent.',
    'forgot.failed': 'Request failed.',
    'forgot.genericSuccess': 'If the account exists, reset instructions were sent.',
    'password.secretMissing': 'Server secret is not configured.',
    'reset.tokenRequired': 'Token is required.',
    'reset.passwordTooShort': 'Password must be at least {minLength} characters.',
    'reset.passwordWeak': 'Password must contain letters and numbers.',
    'reset.tokenInvalidOrExpired': 'Token is invalid or expired.',
    'reset.success': 'Password reset successful. Please sign in with your new password.',
    'reset.serverError': 'Password reset failed due to server error.',
    'gateway.databaseConnectFailed': 'Database connection failed. Run reset and verify MySQL settings.',
    'gateway.invalidJsonBody': 'Invalid JSON body.',
    'admin.mailConfigUpdated': 'Mail config updated.',
    'auth.headerMissingOrInvalid': 'Missing or invalid Authorization header.',
    'auth.tokenInvalidOrExpired': 'Invalid or expired token.',
    'gateway.routeNotFound': 'Route not found.',
    'auth.loggedOut': 'Logged out.',
  },
};

function resolveLocale() {
  if (typeof navigator !== 'undefined' && /^zh/i.test(navigator.language || '')) {
    return 'zh-CN';
  }
  return 'en';
}

/**
 * @param {string} template
 * @param {Record<string, unknown>} [params]
 * @returns {string}
 */
function formatTemplate(template, params = {}) {
  return String(template).replace(/\{(\w+)\}/g, (_, key) => {
    if (params[key] === undefined || params[key] === null) {
      return '';
    }
    return String(params[key]);
  });
}

/**
 * @param {string} locale
 * @param {string} key
 * @param {Record<string, unknown>} [params]
 * @returns {string}
 */
function translate(locale, key, params = {}) {
  const dictionaries = /** @type {Record<string, Record<string, string>>} */ (I18N);
  const dict = dictionaries[locale] || dictionaries.en;
  const fallback = dictionaries.en[key] || key;
  const template = dict[key] || fallback;
  return formatTemplate(template, params);
}

/**
 * @param {any} payload
 * @param {string} locale
 * @param {string} fallback
 * @returns {string}
 */
function localizedMessageFromPayload(payload, locale, fallback) {
  if (!payload || typeof payload !== 'object') {
    return fallback;
  }

  if (typeof payload.i18n_key === 'string' && payload.i18n_key) {
    return translate(locale, payload.i18n_key, payload.i18n_params || {});
  }

  if (typeof payload.message === 'string' && payload.message) {
    return payload.message;
  }

  if (typeof payload.error === 'string' && payload.error) {
    return payload.error;
  }

  return fallback;
}

/**
 * @param {string} message
 * @param {any} payload
 * @param {string} locale
 * @returns {string}
 */
function appendRegisteredUsers(message, payload, locale) {
  const usernames = Array.isArray(payload?.registered_usernames)
    ? payload.registered_usernames.filter(
      /** @param {unknown} name */
      (name) => typeof name === 'string' && name.trim() !== ''
    )
    : [];

  if (usernames.length === 0) {
    return message;
  }

  const suffix = translate(locale, 'register.registeredUsers', {
    usernames: usernames.join(', '),
  });

  return `${message} ${suffix}`.trim();
}

/**
 * @param {string} url
 * @param {unknown} payload
 * @returns {Promise<any>}
 */
async function postJson(url, payload) {
  const resp = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
  });
  const data = await resp.json().catch(() => ({}));
  if (!resp.ok) {
    const error = /** @type {ApiError} */ (new Error(data.error || data.message || `HTTP ${resp.status}`));
    error.status = resp.status;
    error.data = data;
    throw error;
  }
  return data;
}

export function Home() {
  const locale = resolveLocale();
  const [mode, setMode] = useState('register');
  const [msg, setMsg] = useState(translate(locale, 'common.ready'));
  const [loading, setLoading] = useState(false);
  const [user, setUser] = useState(/** @type {AuthUser | null} */ (null));

  const [regUsername, setRegUsername] = useState('');
  const [regEmail, setRegEmail] = useState('');

  const [loginIdentifier, setLoginIdentifier] = useState('');
  const [loginPassword, setLoginPassword] = useState('');

  const [forgotEmail, setForgotEmail] = useState('');
  const [forgotUsername, setForgotUsername] = useState('');

  /** @param {SubmitEvent} e */
  const submitRegister = async (e) => {
    e.preventDefault();
    setLoading(true);
    setMsg(translate(locale, 'register.submitting'));
    try {
      const data = await postJson('/api/register', { username: regUsername, email: regEmail });
      const message = localizedMessageFromPayload(data, locale, translate(locale, 'register.success'));
      setMsg(message);
    } catch (err) {
      const error = /** @type {ApiError | null} */ (err instanceof Error ? err : null);
      const payload = error?.data ?? null;
      const baseMessage = localizedMessageFromPayload(
        payload,
        locale,
        error?.message || translate(locale, 'register.failed')
      );
      setMsg(appendRegisteredUsers(baseMessage, payload, locale));
    } finally {
      setLoading(false);
    }
  };

  /** @param {SubmitEvent} e */
  const submitLogin = async (e) => {
    e.preventDefault();
    setLoading(true);
    setMsg(translate(locale, 'login.signingIn'));
    try {
      const data = await postJson('/api/login', { identifier: loginIdentifier, password: loginPassword, tstmsg: "AIAIAI" });
      setUser(data.user);
      setMsg(localizedMessageFromPayload(data, locale, translate(locale, 'login.success')));
    } catch (err) {
      const error = /** @type {ApiError | null} */ (err instanceof Error ? err : null);
      setMsg(
        localizedMessageFromPayload(
          error?.data ?? null,
          locale,
          error?.message || translate(locale, 'login.failed')
        )
      );
    } finally {
      setLoading(false);
    }
  };

  const handleLogout = async () => {
    try {
      await postJson('/api/logout', {});
    } catch {}
    setUser(null);
    setLoginIdentifier('');
    setLoginPassword('');
    setMsg(translate(locale, 'auth.loggedOut'));
  };

  const handleChangePassword = () => {
    if (!user) {
      return;
    }

    setForgotEmail(user.email);
    setForgotUsername(user.username);
    setMode('forgot');
  };

  /** @param {SubmitEvent} e */
  const submitForgot = async (e) => {
    e.preventDefault();
    setLoading(true);
    setMsg(translate(locale, 'forgot.submitting'));
    try {
      const data = await postJson('/api/password/forgot', { email: forgotEmail, username: forgotUsername });
      setMsg(localizedMessageFromPayload(data, locale, translate(locale, 'forgot.success')));
    } catch (err) {
      const error = /** @type {ApiError | null} */ (err instanceof Error ? err : null);
      setMsg(
        localizedMessageFromPayload(
          error?.data ?? null,
          locale,
          error?.message || translate(locale, 'forgot.failed')
        )
      );
    } finally {
      setLoading(false);
    }
  };

  /** @param {string | undefined | null} dateStr */
  const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleString();
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

      <section style="display:flex;gap:8px;justify-content:center;margin-bottom:24px;">
        <button disabled={mode === 'register'} onClick={() => setMode('register')} > Register </button>
        <button disabled={mode === 'login'} onClick={() => setMode('login')} > Login </button>
        <button disabled={mode === 'forgot'} onClick={() => setMode('forgot')} > Forgot </button>
      </section>


      {mode === 'register' && (
        <form onSubmit={submitRegister}>
          <input value={regUsername} onInput={(e) => setRegUsername(e.currentTarget.value)} placeholder="username" />
          <input value={regEmail} onInput={(e) => setRegEmail(e.currentTarget.value)} placeholder="email" />
          <button disabled={loading}>Submit Register</button>
        </form>
      )}

      {mode === 'login' && (
        <form onSubmit={submitLogin}>
          <input value={loginIdentifier} onInput={(e) => setLoginIdentifier(e.currentTarget.value)} placeholder="username or email" />
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
