import '@pixi/layout';
import { FancyButton, Input } from '@pixi/ui';
import { Application, Container, Graphics, Text, Texture } from 'pixi.js';
import { getJson, postJson } from './api';
import { palette, textStyles } from './theme';

const MODE_LABELS = {
	register: 'Register',
	login: 'Login',
	forgot: 'Forgot Password',
	reset: 'Reset Password',
};

const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

function clamp(value, min, max) {
	return Math.max(min, Math.min(max, value));
}

function normalizeMode(rawMode) {
	if (rawMode === 'reg') {
		return 'register';
	}

	if (rawMode && MODE_LABELS[rawMode]) {
		return rawMode;
	}

	return 'register';
}

function createHostRoot() {
	const root = document.createElement('div');
	root.id = 'app-root';
	document.body.appendChild(root);
	return root;
}

function createRoundedCard(width, height, color, lineColor) {
	return new Graphics()
		.roundRect(0, 0, width, height, 14)
		.fill({ color, alpha: 1 })
		.stroke({ color: lineColor, width: 1.5, alpha: 0.35 });
}

function createButton(label, tone = 'primary') {
	const isPrimary = tone === 'primary';
	const defaultColor = isPrimary ? palette.actionMain : palette.neutralMain;
	const hoverColor = isPrimary ? palette.actionHover : palette.neutralHover;
	const pressedColor = isPrimary ? palette.actionPressed : palette.neutralPressed;

	return new FancyButton({
		defaultView: createRoundedCard(172, 42, defaultColor, 0x0),
		hoverView: createRoundedCard(172, 42, hoverColor, 0x0),
		pressedView: createRoundedCard(172, 42, pressedColor, 0x0),
		text: new Text({ text: label, style: textStyles.button }),
		animations: {
			pressed: {
				props: {
					scale: { x: 0.98, y: 0.98 },
				},
				duration: 80,
			},
		},
	});
}

function createInputField(label, options = {}) {
	const field = new Container({
		layout: {
			width: '100%',
			flexDirection: 'column',
			gap: 6,
		},
	});

	const labelText = new Text({
		text: label,
		style: textStyles.label,
	});
	labelText.layout = { width: '100%' };

	const input = new Input({
		bg: createRoundedCard(560, 46, palette.fieldBg, palette.fieldLine),
		placeholder: options.placeholder ?? '',
		secure: Boolean(options.secure),
		textStyle: textStyles.input,
		value: options.value ?? '',
		maxLength: options.maxLength,
		padding: [11, 12],
		addMask: true,
	});

	input.layout = {
		width: '100%',
		height: 46,
	};

	field.addChild(labelText, input);

	return {
		container: field,
		input,
	};
}

function createFormCard({
	title,
	fields,
	submitLabel,
	submitTone,
	onSubmit,
}) {
	const card = new Container({
		layout: {
			width: '100%',
			flexDirection: 'column',
			gap: 14,
			padding: 20,
		},
	});

	const titleText = new Text({
		text: title,
		style: textStyles.section,
	});
	titleText.layout = { width: '100%' };

	const fieldState = {};
	const fieldNodes = [];
	for (const fieldConfig of fields) {
		const entry = createInputField(fieldConfig.label, fieldConfig);
		fieldState[fieldConfig.key] = entry.input;
		fieldNodes.push(entry.container);
	}

	const submitButton = createButton(submitLabel, submitTone);
	submitButton.layout = {
		width: 196,
		height: 42,
		alignSelf: 'flex-start',
		marginTop: 4,
	};

	submitButton.onPress.connect(async () => {
		const payload = Object.fromEntries(
			Object.entries(fieldState).map(([key, input]) => [key, input.value.trim()])
		);

		submitButton.enabled = false;
		try {
			await onSubmit(payload);
		} finally {
			submitButton.enabled = true;
		}
	});

	card.addChild(titleText, ...fieldNodes, submitButton);

	return {
		container: card,
		fields: fieldState,
	};
}

function createFloatingMonkeys(app) {
	const monkeys = [];

	for (let i = 0; i < 24; i += 1) {
		const size = 10 + Math.random() * 16;
		const monkey = new Graphics()
			.circle(0, 0, size)
			.fill({ color: 0xb56a2d, alpha: 0.12 })
			.circle(size * 0.68, -size * 0.52, size * 0.48)
			.fill({ color: 0xcf8a54, alpha: 0.2 })
			.circle(-size * 0.68, -size * 0.52, size * 0.48)
			.fill({ color: 0xcf8a54, alpha: 0.2 });

		monkey.x = Math.random() * app.screen.width;
		monkey.y = Math.random() * app.screen.height;

		app.stage.addChild(monkey);

		monkeys.push({
			monkey,
			driftX: (Math.random() - 0.5) * 0.65,
			speedY: 0.15 + Math.random() * 0.35,
		});
	}

	app.ticker.add(() => {
		for (const entry of monkeys) {
			entry.monkey.x += entry.driftX;
			entry.monkey.y -= entry.speedY;

			if (entry.monkey.y < -40) {
				entry.monkey.y = app.screen.height + 40;
				entry.monkey.x = Math.random() * app.screen.width;
			}

			if (entry.monkey.x < -40) entry.monkey.x = app.screen.width + 40;
			if (entry.monkey.x > app.screen.width + 40) entry.monkey.x = -40;
		}
	});
}

function createAppLayout(app) {
	app.stage.layout = {
		width: app.screen.width,
		height: app.screen.height,
		justifyContent: 'center',
		alignItems: 'center',
		padding: 20,
	};

	const shell = new Container({
		layout: {
			width: 740,
			maxWidth: '100%',
			height: 'auto',
			flexDirection: 'column',
			gap: 12,
		},
	});

	const panelFrame = new Graphics();
	panelFrame.layout = {
		width: '100%',
		height: '100%',
		position: 'absolute',
		top: 0,
		left: 0,
		zIndex: -1,
	};

	const panel = new Container({
		layout: {
			width: '100%',
			flexDirection: 'column',
			gap: 10,
			padding: 22,
		},
	});

	const title = new Text({ text: 'AIMonkey', style: textStyles.title });
	title.layout = { width: '100%' };

	const subtitle = new Text({
		text: 'Register avatar accounts and recover access via email.',
		style: textStyles.subtitle,
	});
	subtitle.layout = { width: '100%' };

	const navRow = new Container({
		layout: {
			width: '100%',
			flexDirection: 'row',
			flexWrap: 'wrap',
			gap: 10,
			marginTop: 4,
			marginBottom: 6,
		},
	});

	const modeHost = new Container({
		layout: {
			width: '100%',
			minHeight: 292,
		},
	});

	const statusFrame = new Graphics();
	statusFrame.layout = {
		width: '100%',
		height: '100%',
		position: 'absolute',
		top: 0,
		left: 0,
		zIndex: -1,
	};

	const statusContainer = new Container({
		layout: {
			width: '100%',
			minHeight: 94,
			padding: 12,
			marginTop: 8,
		},
	});

	const statusText = new Text({
		text: 'Ready.',
		style: textStyles.body,
	});
	statusText.layout = { width: '100%' };

	statusContainer.addChild(statusFrame, statusText);
	panel.addChild(title, subtitle, navRow, modeHost, statusContainer);
	shell.addChild(panelFrame, panel);
	app.stage.addChild(shell);

	const redrawFrames = () => {
		const panelBounds = panel.getLocalBounds();
		panelFrame
			.clear()
			.roundRect(0, 0, panelBounds.width + 2, panelBounds.height + 2, 22)
			.fill({ color: palette.cardBg, alpha: 0.95 })
			.stroke({ color: palette.cardLine, width: 1.2, alpha: 0.3 });

		const statusBounds = statusContainer.getLocalBounds();
		statusFrame
			.clear()
			.roundRect(0, 0, statusBounds.width, statusBounds.height, 12)
			.fill({ color: palette.statusBg, alpha: 0.95 })
			.stroke({ color: palette.statusLine, width: 1.2, alpha: 0.24 });
	};

	return {
		shell,
		navRow,
		modeHost,
		statusText,
		redrawFrames,
	};
}

export async function bootstrapApp() {
	const host = createHostRoot();

	const app = new Application();
	await app.init({
		resizeTo: host,
		antialias: true,
		background: '#f4dfc4',
		textureGCActive: true,
	});

	host.appendChild(app.canvas);

	const background = new Graphics();
	background.layout = {
		width: app.screen.width,
		height: app.screen.height,
		position: 'absolute',
		top: 0,
		left: 0,
		zIndex: -5,
	};
	app.stage.addChild(background);

	const drawBackground = () => {
		background
			.clear()
			.rect(0, 0, app.screen.width, app.screen.height)
			.fill({
				texture: Texture.WHITE,
				color: palette.appBgTop,
				alpha: 1,
			});

		background
			.circle(app.screen.width * 0.82, app.screen.height * 0.2, app.screen.width * 0.24)
			.fill({ color: 0xf2c995, alpha: 0.38 });

		background
			.circle(app.screen.width * 0.18, app.screen.height * 0.75, app.screen.width * 0.2)
			.fill({ color: 0xe8b47f, alpha: 0.28 });

		background
			.rect(0, app.screen.height * 0.52, app.screen.width, app.screen.height * 0.48)
			.fill({ color: palette.appBgBottom, alpha: 0.35 });
	};

	createFloatingMonkeys(app);

	const ui = createAppLayout(app);
	const setStatus = (message) => {
		ui.statusText.text = message;
		ui.redrawFrames();
	};

	const registerForm = createFormCard({
		title: 'Create Avatar Account',
		submitLabel: 'Register',
		submitTone: 'primary',
		fields: [
			{ key: 'username', label: 'Username', placeholder: 'Input username' },
			{ key: 'email', label: 'Email', placeholder: 'user@email.com' },
		],
		onSubmit: async ({ username, email }) => {
			if (!username) {
				setStatus('Username is required.');
				return;
			}
			if (!emailPattern.test(email)) {
				setStatus('Please use a valid email address.');
				return;
			}

			setStatus('Submitting registration...');
			try {
				const data = await postJson('/api/register', { username, email });
				setStatus(data.message || 'Registration complete. Please check your email.');
				registerForm.fields.username.value = '';
				registerForm.fields.email.value = '';
			} catch (error) {
				setStatus(error instanceof Error ? error.message : 'Registration failed.');
			}
		},
	});

	const loginForm = createFormCard({
		title: 'Sign In',
		submitLabel: 'Login',
		submitTone: 'secondary',
		fields: [
			{ key: 'email', label: 'Email', placeholder: 'user@email.com' },
			{ key: 'password', label: 'Password', placeholder: 'Input password', secure: true },
		],
		onSubmit: async ({ email, password }) => {
			if (!emailPattern.test(email) || !password) {
				setStatus('Email and password are required for login.');
				return;
			}

			setStatus('Signing in...');
			try {
				const data = await postJson('/api/login', { email, password });
				setStatus(data.message || 'Login successful.');
			} catch (error) {
				setStatus(error instanceof Error ? error.message : 'Login failed.');
			}
		},
	});

	const forgotForm = createFormCard({
		title: 'Forgot Password',
		submitLabel: 'Send Reset Link',
		submitTone: 'secondary',
		fields: [
			{ key: 'email', label: 'Email', placeholder: 'user@email.com' },
			{ key: 'username', label: 'Username (optional)', placeholder: 'Input username' },
		],
		onSubmit: async ({ email, username }) => {
			if (!emailPattern.test(email)) {
				setStatus('Please use a valid email address.');
				return;
			}

			setStatus('Sending reset link request...');
			try {
				const data = await postJson('/api/password/forgot', { email, username });
				setStatus(data.message || 'If the account exists, reset instructions were sent.');
			} catch (error) {
				setStatus(error instanceof Error ? error.message : 'Request failed.');
			}
		},
	});

	const query = new URLSearchParams(window.location.search);

	const resetForm = createFormCard({
		title: 'Reset Password',
		submitLabel: 'Reset Password',
		submitTone: 'primary',
		fields: [
			{
				key: 'token',
				label: 'Token',
				placeholder: 'Paste reset token',
				value: query.get('token') || '',
			},
			{
				key: 'newPassword',
				label: 'New Password',
				placeholder: 'At least 8 chars with numbers',
				secure: true,
			},
		],
		onSubmit: async ({ token, newPassword }) => {
			if (!token) {
				setStatus('Token is required.');
				return;
			}
			if (newPassword.length < 8) {
				setStatus('Password must be at least 8 characters.');
				return;
			}

			setStatus('Resetting password...');
			try {
				const data = await postJson('/api/password/reset', { token, newPassword });
				setStatus(data.message || 'Password reset succeeded.');
				resetForm.fields.newPassword.value = '';
			} catch (error) {
				setStatus(error instanceof Error ? error.message : 'Password reset failed.');
			}
		},
	});

	const forms = {
		register: registerForm.container,
		login: loginForm.container,
		forgot: forgotForm.container,
		reset: resetForm.container,
	};

	const setMode = (mode, syncUrl = true) => {
		const safeMode = normalizeMode(mode);

		ui.modeHost.removeChildren();
		ui.modeHost.addChild(forms[safeMode]);

		if (syncUrl) {
			const url = new URL(window.location.href);
			url.searchParams.set('mode', safeMode);
			if (safeMode !== 'reset') {
				url.searchParams.delete('token');
			}
			window.history.replaceState(null, '', url);
		}

		setStatus(`Ready: ${MODE_LABELS[safeMode]}.`);
		ui.redrawFrames();
	};

	for (const [mode, label] of Object.entries(MODE_LABELS)) {
		const button = createButton(
			label,
			mode === 'register' || mode === 'reset' ? 'primary' : 'secondary'
		);
		button.layout = { width: 170, height: 42 };
		button.onPress.connect(() => setMode(mode));
		ui.navRow.addChild(button);
	}

	const onResize = () => {
		app.stage.layout = {
			width: app.screen.width,
			height: app.screen.height,
			justifyContent: 'center',
			alignItems: 'center',
			padding: 20,
		};

		ui.shell.layout = {
			width: clamp(app.screen.width * 0.92, 320, 760),
			maxWidth: '100%',
			height: 'auto',
			flexDirection: 'column',
			gap: 12,
		};

		drawBackground();
		ui.redrawFrames();
	};

	window.addEventListener('resize', onResize);

	try {
		const config = await getJson('/api/config');
		if (!config?.mail?.smtpEnabled) {
			setStatus('SMTP is disabled. Registration and password reset emails cannot be sent.');
		}
	} catch {
		setStatus('Warning: failed to load runtime config from backend.');
	}

	const initialMode = normalizeMode(query.get('mode') || 'register');
	setMode(initialMode, false);
	onResize();
}
