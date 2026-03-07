// eslint-disable-next-line no-unused-vars
import { jest } from "@jest/globals";

test("debug admin.js import", async () => {
    const module = await import("../admin.js");
    console.log("Module keys:", Object.keys(module));
    console.log("Module:", module);
    console.log("Module.default:", module.default);
    console.log(
        "Module.default keys:",
        module.default ? Object.keys(module.default) : "undefined",
    );
    console.log("Module.__internals:", module.__internals);
    console.log(
        "Module.default.__internals:",
        module.default ? module.default.__internals : "undefined",
    );
});
