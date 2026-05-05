import { TextStyle } from 'pixi.js';

export const palette = {
  appBgTop: 0xf4e2c6,
  appBgBottom: 0xe3c196,
  cardBg: 0xfff7ec,
  cardLine: 0x6d5031,
  fieldBg: 0xffffff,
  fieldLine: 0x9d7f58,
  actionMain: 0xb6682b,
  actionHover: 0xd17d37,
  actionPressed: 0x8d4f1f,
  neutralMain: 0x44556b,
  neutralHover: 0x59718f,
  neutralPressed: 0x354355,
  statusBg: 0xf4ede3,
  statusLine: 0x8a6a47,
  textStrong: 0x2d1f10,
  textBody: 0x4a3622,
  textSoft: 0x72573d,
};

export const textStyles = {
  title: new TextStyle({
    fontFamily: 'Trebuchet MS',
    fontSize: 42,
    fontWeight: '700',
    fill: palette.textStrong,
    letterSpacing: 1.4,
  }),
  subtitle: new TextStyle({
    fontFamily: 'Trebuchet MS',
    fontSize: 16,
    fill: palette.textSoft,
  }),
  section: new TextStyle({
    fontFamily: 'Trebuchet MS',
    fontSize: 20,
    fontWeight: '700',
    fill: palette.textStrong,
  }),
  label: new TextStyle({
    fontFamily: 'Trebuchet MS',
    fontSize: 14,
    fontWeight: '600',
    fill: palette.textBody,
  }),
  body: new TextStyle({
    fontFamily: 'Trebuchet MS',
    fontSize: 15,
    fill: palette.textBody,
    lineHeight: 22,
  }),
  button: new TextStyle({
    fontFamily: 'Trebuchet MS',
    fontSize: 14,
    fontWeight: '700',
    fill: 0xffffff,
    letterSpacing: 0.6,
  }),
  input: {
    fontFamily: 'Trebuchet MS',
    fontSize: 14,
    fill: palette.textStrong,
  },
};
