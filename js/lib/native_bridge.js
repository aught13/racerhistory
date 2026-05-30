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
        const bridgeUrl =
            "https://esm.sh/@hotwired/hotwire-native-bridge@1.0.0";
        const mod = await import(/* @vite-ignore */ bridgeUrl);

        if (mod && typeof mod.start === "function") {
            mod.start();
        }
    } catch {
        // Optional dependency, safe to ignore.
    }
}
