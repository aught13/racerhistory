export async function startNativeBridge() {
    const mockBridge =
        typeof globalThis !== "undefined"
            ? globalThis.__HOTWIRE_NATIVE_BRIDGE__
            : null;
    if (mockBridge) {
        if (typeof mockBridge.start === "function") {
            mockBridge.start();
        }
        return;
    }

    try {
        const mod = await import("@hotwired/hotwire-native-bridge");

        if (mod && typeof mod.start === "function") {
            mod.start();
        }
    } catch {
        // Optional dependency: safe to ignore if not available.
    }
}
