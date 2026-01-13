export async function startNativeBridge() {
    try {
        const mod = await import("@hotwired/hotwire-native-bridge");

        if (mod && typeof mod.start === "function") {
            mod.start();
        }
    } catch {
        // Optional dependency: safe to ignore if not available.
    }
}
