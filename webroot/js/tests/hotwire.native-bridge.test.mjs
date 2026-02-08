import { jest } from '@jest/globals';

describe('hotwire/native_bridge', () => {
  beforeEach(() => {
    jest.resetModules();
    jest.clearAllMocks();
  });

  test('startNativeBridge ignores missing module', async () => {
    const { startNativeBridge } = await import('../hotwire/native_bridge.js');
    await expect(startNativeBridge()).resolves.toBeUndefined();
  });

  test('startNativeBridge starts module when available', async () => {
    const start = jest.fn();

    jest.doMock(
      '@hotwired/hotwire-native-bridge',
      () => ({
        start,
      }),
      { virtual: true },
    );

    const { startNativeBridge } = await import('../hotwire/native_bridge.js');

    await startNativeBridge();

    expect(start).toHaveBeenCalledTimes(1);
  });

  test('startNativeBridge ignores missing start function', async () => {
    jest.doMock(
      '@hotwired/hotwire-native-bridge',
      () => ({
        start: 'nope',
      }),
      { virtual: true },
    );

    const { startNativeBridge } = await import('../hotwire/native_bridge.js');

    await expect(startNativeBridge()).resolves.toBeUndefined();
  });

  test('startNativeBridge ignores start errors', async () => {
    const start = jest.fn(() => {
      throw new Error('boom');
    });

    jest.doMock(
      '@hotwired/hotwire-native-bridge',
      () => ({
        start,
      }),
      { virtual: true },
    );

    const { startNativeBridge } = await import('../hotwire/native_bridge.js');

    await expect(startNativeBridge()).resolves.toBeUndefined();
    expect(start).toHaveBeenCalledTimes(1);
  });
});
